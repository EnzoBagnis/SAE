/**
 * Clustering Module — Cartographie des codes
 * Gère l'onglet clustering dans le dashboard :
 *   - Chargement de la liste des exercices
 *   - Appel API POST /api/clustering/generate
 *   - Affichage du graphique t-SNE + KMeans et des statistiques
 */

(function () {
    'use strict';

    const BASE_URL = window.BASE_URL || '';
    const RESOURCE_ID = window.RESOURCE_ID || null;

    let clusteringActive = false;

    // ─── Charger les exercices dans le select ────────────────────────────────
    function loadExercisesForClustering() {
        const select = document.getElementById('clusteringExerciseSelect');
        if (!select) return;

        let url = BASE_URL + '/api/dashboard/exercises';
        if (RESOURCE_ID) {
            url += '?resource_id=' + RESOURCE_ID;
        }

        fetch(url)
            .then(r => r.json())
            .then(json => {
                if (!json.success) return;
                const exercises = json.data.exercises || [];
                select.innerHTML = '<option value="">-- Tous les exercices de la ressource --</option>';
                exercises.forEach(ex => {
                    const opt = document.createElement('option');
                    opt.value = ex.exercise_id;
                    opt.textContent = (ex.funcname || ex.exo_name) +
                        ' (' + ex.total_attempts + ' tentatives)';
                    select.appendChild(opt);
                });
            })
            .catch(err => console.error('[Clustering] Erreur chargement exercices:', err));
    }

    // ─── Basculer la vue vers le clustering ──────────────────────────────────
    function showClusteringView() {
        clusteringActive = true;

        // Masquer la data-zone classique
        const dataZone = document.querySelector('.data-zone');
        if (dataZone) dataZone.style.display = 'none';

        // Masquer la sidebar-list (pas besoin de liste latérale en mode clustering)
        const sidebarList = document.getElementById('sidebar-list');
        if (sidebarList) sidebarList.style.display = 'none';

        // Afficher la zone clustering
        const clusteringZone = document.getElementById('clusteringZone');
        if (clusteringZone) clusteringZone.style.display = 'block';

        // Mettre à jour les onglets
        document.querySelectorAll('.view-tab').forEach(btn => btn.classList.remove('active'));
        const btnClustering = document.getElementById('btnClustering');
        if (btnClustering) btnClustering.classList.add('active');

        // Charger la liste des exercices
        loadExercisesForClustering();
    }

    // ─── Masquer la vue clustering ───────────────────────────────────────────
    function hideClusteringView() {
        clusteringActive = false;

        const dataZone = document.querySelector('.data-zone');
        if (dataZone) dataZone.style.display = '';

        const sidebarList = document.getElementById('sidebar-list');
        if (sidebarList) sidebarList.style.display = '';

        const clusteringZone = document.getElementById('clusteringZone');
        if (clusteringZone) clusteringZone.style.display = 'none';
    }

    // ─── Générer le clustering (appel API) ───────────────────────────────────
    function generateClustering() {
        const exerciseSelect = document.getElementById('clusteringExerciseSelect');
        const nClustersInput = document.getElementById('clusteringNClusters');
        const btnGenerate = document.getElementById('btnGenerateClusters');
        const loadingEl = document.getElementById('clusteringLoading');
        const errorEl = document.getElementById('clusteringError');
        const resultEl = document.getElementById('clusteringResult');

        const exerciseId = exerciseSelect ? exerciseSelect.value : '';
        const nClusters = nClustersInput ? parseInt(nClustersInput.value, 10) : 8;

        // Construire le body
        const body = { n_clusters: nClusters };
        if (exerciseId) {
            body.exercise_id = parseInt(exerciseId, 10);
        } else if (RESOURCE_ID) {
            body.resource_id = RESOURCE_ID;
        } else {
            showError('Veuillez sélectionner un exercice ou accéder à une ressource.');
            return;
        }

        // UI: loading
        if (btnGenerate) btnGenerate.disabled = true;
        if (loadingEl) loadingEl.style.display = 'flex';
        if (errorEl) errorEl.style.display = 'none';
        if (resultEl) resultEl.style.display = 'none';

        fetch(BASE_URL + '/api/clustering/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        })
            .then(r => r.json())
            .then(json => {
                if (loadingEl) loadingEl.style.display = 'none';
                if (btnGenerate) btnGenerate.disabled = false;

                if (!json.success) {
                    showError(json.message || 'Erreur lors de la génération.');
                    return;
                }

                displayResult(json.data);
            })
            .catch(err => {
                console.error('[Clustering] Erreur:', err);
                if (loadingEl) loadingEl.style.display = 'none';
                if (btnGenerate) btnGenerate.disabled = false;
                showError('Erreur de communication avec le serveur : ' + err.message);
            });
    }

    // ─── Afficher une erreur ─────────────────────────────────────────────────
    function showError(msg) {
        const errorEl = document.getElementById('clusteringError');
        const errorMsg = document.getElementById('clusteringErrorMsg');
        if (errorEl) errorEl.style.display = 'block';
        if (errorMsg) errorMsg.textContent = msg;
    }

    // ─── Afficher le résultat ────────────────────────────────────────────────
    function displayResult(data) {
        const resultEl = document.getElementById('clusteringResult');
        const statsEl = document.getElementById('clusteringStats');
        const imgEl = document.getElementById('clusteringImage');
        const detailsEl = document.getElementById('clusteringClusterDetails');

        if (!resultEl) return;
        resultEl.style.display = 'block';

        // Image
        if (imgEl && data.image) {
            imgEl.src = 'data:image/png;base64,' + data.image;
            imgEl.style.display = 'block';
        }

        // Stats globales
        if (statsEl) {
            statsEl.innerHTML =
                '<div class="clustering-stat-card">' +
                    '<span class="stat-number">' + (data.total_attempts || 0) + '</span>' +
                    '<span class="stat-label">Tentatives analysées</span>' +
                '</div>' +
                '<div class="clustering-stat-card">' +
                    '<span class="stat-number">' + (data.n_clusters || 0) + '</span>' +
                    '<span class="stat-label">Clusters identifiés</span>' +
                '</div>';
        }

        // Détails par cluster
        if (detailsEl && data.cluster_stats) {
            var html = '<h3>Détails des clusters</h3><div class="cluster-cards">';
            data.cluster_stats.forEach(function (cluster) {
                var usersHtml = (cluster.users || [])
                    .map(function (u) { return '<span class="cluster-user">' + escapeHtml(u) + '</span>'; })
                    .join('');
                html +=
                    '<div class="cluster-card" style="border-left: 4px solid ' + getClusterColor(cluster.cluster_id) + ';">' +
                        '<div class="cluster-card-header">' +
                            '<strong>Cluster ' + cluster.cluster_id + '</strong>' +
                            '<span class="cluster-count">' + cluster.count + ' tentatives</span>' +
                        '</div>' +
                        '<div class="cluster-card-body">' +
                            '<div class="cluster-success-rate">' +
                                'Taux de réussite : <strong>' + cluster.success_rate + '%</strong>' +
                            '</div>' +
                            '<div class="cluster-users-list">' +
                                (usersHtml || '<em>Aucun utilisateur</em>') +
                            '</div>' +
                        '</div>' +
                    '</div>';
            });
            html += '</div>';
            detailsEl.innerHTML = html;
        }
    }

    // ─── Couleurs de cluster ─────────────────────────────────────────────────
    var CLUSTER_COLORS = [
        '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728',
        '#9467bd', '#8c564b', '#e377c2', '#7f7f7f',
        '#bcbd22', '#17becf', '#aec7e8', '#ffbb78',
        '#98df8a', '#ff9896', '#c5b0d5', '#c49c94',
        '#f7b6d2', '#c7c7c7', '#dbdb8d', '#9edae5'
    ];

    function getClusterColor(id) {
        return CLUSTER_COLORS[id % CLUSTER_COLORS.length];
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ─── Intercepter switchListView (robuste au timing des modules ES) ──────
    // dashboard-main.js (module ES) peut s'exécuter après ce script.
    // On utilise un Proxy sur window pour capturer toute future assignation.
    function patchSwitchListView() {
        var _dashboardSwitchListView = window.switchListView || null;

        // Wrapper qui gère le cas "clustering"
        function wrappedSwitchListView(view) {
            if (view === 'clustering') {
                showClusteringView();
            } else {
                hideClusteringView();
                if (typeof _dashboardSwitchListView === 'function') {
                    _dashboardSwitchListView(view);
                }
            }
        }

        // Définir immédiatement
        window.switchListView = wrappedSwitchListView;

        // Intercepter les futures redéfinitions par dashboard-main.js
        // en utilisant un setter sur la propriété
        var _currentFn = wrappedSwitchListView;

        try {
            Object.defineProperty(window, 'switchListView', {
                get: function () { return _currentFn; },
                set: function (newFn) {
                    // Quand dashboard-main.js redéfinit switchListView,
                    // on capture sa version et on la wrappe
                    _dashboardSwitchListView = newFn;
                    _currentFn = function (view) {
                        if (view === 'clustering') {
                            showClusteringView();
                        } else {
                            hideClusteringView();
                            if (typeof _dashboardSwitchListView === 'function') {
                                _dashboardSwitchListView(view);
                            }
                        }
                    };
                },
                configurable: true,
                enumerable: true
            });
        } catch (e) {
            // Fallback: si defineProperty échoue, simple remplacement
            console.warn('[Clustering] defineProperty fallback:', e);
        }
    }

    patchSwitchListView();

    // ─── Exposer la fonction de génération globalement ───────────────────────
    window.generateClustering = generateClustering;

})();
