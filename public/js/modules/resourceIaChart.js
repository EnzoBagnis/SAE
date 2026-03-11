/**
 * resourceIaChart.js — Module de visualisation IA pour la Vue Ressource (Macro)
 *
 * Responsabilités :
 *   - Bouton "Générer la cartographie IA" avec spinner de chargement
 *   - Appel POST /api/dashboard/ia/macro pour lancer le pipeline Python
 *   - Rendu Plotly du graphe t-SNE global (1 cluster = 1 TD)
 *   - Clic sur un cluster → window.location.href vers la vue du TD (Micro)
 *
 * Ce module est chargé UNIQUEMENT sur la page resources/details.php.
 */
const ResourceIaChart = (function () {
    'use strict';

    // ── Constantes ──────────────────────────────────────────────────────────
    const COLORS_10 = [
        '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6',
        '#1abc9c', '#e67e22', '#34495e', '#d35400', '#7f8c8d'
    ];

    // ── État interne ────────────────────────────────────────────────────────
    let _baseUrl    = '';
    let _resourceId = null;
    let _loading    = false;
    let _generated  = false;

    // ══════════════════════════════════════════════════════════════════════════
    //  INITIALISATION
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Initialise le module. Appelé au DOMContentLoaded.
     */
    function init() {
        _baseUrl    = window.BASE_URL || '';
        _resourceId = window.RESOURCE_ID || null;

        const generateBtn = document.getElementById('ia-generate-btn');
        if (generateBtn) {
            generateBtn.addEventListener('click', generate);
        }

        // Vérifier si des données AES existent pour activer/désactiver le bouton
        _checkDataAvailability();

        console.log('[ResourceIaChart] Initialisé pour ressource #' + _resourceId);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  VÉRIFICATION DES DONNÉES
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Vérifie si des données AES existent (sans lancer Python).
     * Met à jour l'UI en conséquence.
     */
    function _checkDataAvailability() {
        const url = _baseUrl + '/api/dashboard/ia/check-data'
            + (_resourceId ? '?resource_id=' + _resourceId : '');

        fetch(url)
            .then(r => r.json())
            .then(data => {
                const btn      = document.getElementById('ia-generate-btn');
                const infoEl   = document.getElementById('ia-data-info');

                if (data.success && data.has_data) {
                    if (btn) {
                        btn.disabled = false;
                        btn.title = data.total_aes + ' tentatives avec AES disponibles';
                    }
                    if (infoEl) {
                        infoEl.innerHTML =
                            '<span class="ia-info-badge ia-info-ready">✅ ' +
                            data.total_aes + ' tentatives analysables · ' +
                            data.exercises.length + ' exercices</span>';
                    }
                } else {
                    if (btn) {
                        btn.disabled = true;
                        btn.title = 'Pas assez de données AES (minimum 5 tentatives requises)';
                    }
                    if (infoEl) {
                        const count = (data && data.total_aes) ? data.total_aes : 0;
                        infoEl.innerHTML =
                            '<span class="ia-info-badge ia-info-missing">⚠️ Données insuffisantes : ' +
                            count + ' tentatives AES (minimum 5 requises). ' +
                            'Importez des données avec les vecteurs AES pour activer l\'analyse IA.</span>';
                    }
                }
            })
            .catch(err => {
                console.error('[ResourceIaChart] Erreur check-data:', err);
            });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  GÉNÉRATION (LANCEMENT DU PIPELINE PYTHON)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Lance le pipeline Python pour générer la cartographie Macro.
     */
    function generate() {
        if (_loading) return;
        _loading = true;

        const btn     = document.getElementById('ia-generate-btn');
        const spinner = document.getElementById('ia-generate-spinner');
        const status  = document.getElementById('ia-macro-status');
        const plotEl  = document.getElementById('ia-macro-plot');

        // UI : état de chargement
        if (btn)     { btn.disabled = true; btn.classList.add('loading'); }
        if (spinner) { spinner.style.display = 'inline-block'; }
        if (status)  { status.textContent = '⏳ Génération en cours… Cela peut prendre quelques instants.'; status.style.display = 'block'; }
        if (plotEl)  { plotEl.innerHTML = ''; }

        const body = { perplexity: 30 };
        if (_resourceId) body.resource_id = parseInt(_resourceId);

        fetch(_baseUrl + '/api/dashboard/ia/macro', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(data => {
            _loading = false;
            if (btn)     { btn.disabled = false; btn.classList.remove('loading'); }
            if (spinner) { spinner.style.display = 'none'; }

            if (data.success) {
                _generated = true;
                if (status) { status.textContent = ''; status.style.display = 'none'; }
                _renderMacro(data);
            } else {
                if (status) {
                    status.textContent = '⚠️ ' + (data.message || data.error || 'Erreur lors de la génération.');
                    status.style.display = 'block';
                }
            }
        })
        .catch(err => {
            _loading = false;
            if (btn)     { btn.disabled = false; btn.classList.remove('loading'); }
            if (spinner) { spinner.style.display = 'none'; }
            if (status) {
                status.textContent = '❌ Erreur réseau : ' + err.message;
                status.style.display = 'block';
            }
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  RENDU PLOTLY — VUE MACRO
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Affiche le graphe t-SNE global avec les centroïdes par exercice (TD).
     * Un clic sur un centroïde redirige vers la vue du TD.
     */
    function _renderMacro(data) {
        const container = document.getElementById('ia-macro-plot');
        if (!container) return;

        // Afficher le conteneur
        container.style.display = 'block';
        container.style.minHeight = '500px';

        const metaEl = document.getElementById('ia-macro-meta');

        const traces = [];

        // 1) Nuage de fond — tous les points, colorés par exercice, faible opacité
        if (data.all_points && data.all_points.length > 0) {
            const byExo = {};
            data.all_points.forEach(function (p) {
                if (!byExo[p.exercise_name]) byExo[p.exercise_name] = { x: [], y: [], ids: [] };
                byExo[p.exercise_name].x.push(p.x);
                byExo[p.exercise_name].y.push(p.y);
                byExo[p.exercise_name].ids.push(p.exercice_id);
            });

            var colorIdx = 0;
            Object.keys(byExo).forEach(function (exName) {
                var grp = byExo[exName];
                var col = COLORS_10[colorIdx % COLORS_10.length];
                traces.push({
                    x: grp.x, y: grp.y,
                    mode: 'markers', type: 'scatter',
                    name: exName,
                    marker: { size: 5, color: col, opacity: 0.2 },
                    hoverinfo: 'text',
                    text: grp.x.map(function () { return exName; }),
                    showlegend: false,
                    customdata: grp.ids,
                });
                colorIdx++;
            });
        }

        // 2) Centroïdes cliquables — gros marqueurs avec labels
        if (data.centroids && data.centroids.length > 0) {
            var cx     = data.centroids.map(function (c) { return c.x; });
            var cy     = data.centroids.map(function (c) { return c.y; });
            var labels = data.centroids.map(function (c) { return c.exercise_name; });
            var sizes  = data.centroids.map(function (c) {
                return Math.max(14, Math.min(40, 8 + Math.sqrt(c.n_attempts) * 3));
            });
            var colors = data.centroids.map(function (_, i) {
                return COLORS_10[i % COLORS_10.length];
            });
            var ids    = data.centroids.map(function (c) { return c.exercice_id; });
            var hovers = data.centroids.map(function (c) {
                return '<b>' + _esc(c.exercise_name) + '</b><br>' +
                       c.n_attempts + ' tentatives<br>' +
                       '<i>Cliquer pour ouvrir ce TD</i>';
            });

            traces.push({
                x: cx, y: cy,
                mode: 'markers+text', type: 'scatter',
                name: 'Centroïdes TDs',
                marker: {
                    size: sizes,
                    color: colors,
                    line: { width: 2, color: '#fff' },
                    opacity: 0.9,
                },
                text: labels,
                textposition: 'top center',
                textfont: { size: 11, color: '#2c3e50', family: 'sans-serif' },
                hoverinfo: 'text',
                hovertext: hovers,
                customdata: ids,
                showlegend: true,
            });
        }

        var layout = {
            title: {
                text: 'Cartographie IA — Vue globale des TDs',
                font: { size: 16, color: '#2c3e50' },
            },
            xaxis: { title: 't-SNE dim. 1', zeroline: false, showgrid: true, gridcolor: '#ecf0f1' },
            yaxis: { title: 't-SNE dim. 2', zeroline: false, showgrid: true, gridcolor: '#ecf0f1' },
            hovermode: 'closest',
            plot_bgcolor: '#fafbfc',
            paper_bgcolor: '#fff',
            margin: { t: 60, b: 50, l: 60, r: 30 },
            legend: { orientation: 'h', y: -0.15 },
        };

        Plotly.newPlot(container, traces, layout, { responsive: true }).then(function () {
            // ── Clic sur un centroïde → redirection vers le TD ──
            container.on('plotly_click', function (evtData) {
                if (!evtData || !evtData.points || evtData.points.length === 0) return;
                var pt = evtData.points[0];
                var exerciseId = pt.customdata;
                if (exerciseId && typeof exerciseId === 'number' && exerciseId > 0) {
                    // Redirection vers la vue du TD (exercises/{id} qui affiche le dashboard Micro)
                    window.location.href = _baseUrl + '/exercises/' + exerciseId;
                }
            });
        });

        // Métadonnées
        if (metaEl) {
            metaEl.innerHTML =
                '<span><strong>' + (data.n_points || 0) + '</strong> tentatives analysées</span>' +
                '<span style="margin-left:1.5rem;"><strong>' + (data.n_exercises || 0) + '</strong> exercices (TDs)</span>';
            metaEl.style.display = 'block';
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    function _esc(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Auto-init ───────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // S'initialiser seulement si le conteneur Macro existe (page ressource)
        if (document.getElementById('ia-macro-section')) {
            init();
        }
    });

    // ── API publique ────────────────────────────────────────────────────────
    return {
        init:      init,
        generate:  generate,
    };

})();

