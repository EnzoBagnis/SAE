/**
 * iaViz.js — Module de visualisation IA (Macro / Micro) avec Plotly.js
 *
 * Niveau 1 (Macro) : t-SNE global, centroïdes par TD, clic → zoom Micro
 * Niveau 2 (Micro) : K-Means + t-SNE par exercice, trajectoires par étudiant
 */
const IaViz = (function () {
    'use strict';

    const PLOTLY_CDN = 'https://cdn.plot.ly/plotly-2.32.0.min.js';
    const COLORS_10  = [
        '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6',
        '#1abc9c', '#e67e22', '#34495e', '#d35400', '#7f8c8d'
    ];

    let _baseUrl = '';
    let _currentMacroData = null;   // Données macro en cache
    let _currentMicroData = null;   // Données micro en cache

    // ── Initialisation ──────────────────────────────────────────────────────
    function init(baseUrl) {
        _baseUrl = baseUrl || '';
        _loadPlotly().then(() => {
            console.log('[IaViz] Plotly.js chargé.');
        });
    }

    // ── Chargement dynamique de Plotly ──────────────────────────────────────
    function _loadPlotly() {
        return new Promise((resolve, reject) => {
            if (window.Plotly) { resolve(); return; }
            const s = document.createElement('script');
            s.src = PLOTLY_CDN;
            s.onload = resolve;
            s.onerror = () => reject(new Error('Impossible de charger Plotly.js'));
            document.head.appendChild(s);
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  NIVEAU 1 — VUE MACRO
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Lance l'analyse Macro (POST /api/ia/macro).
     * @param {Object} opts  { resource_id?, perplexity? }
     */
    function loadMacro(opts) {
        opts = opts || {};
        const perplexity = parseInt(opts.perplexity) || 30;
        const resourceId = opts.resource_id || null;

        _showLoading('macroLoading');
        _hideEl('macroError');
        _hideEl('macroResult');

        const body = { perplexity: perplexity };
        if (resourceId) body.resource_id = parseInt(resourceId);

        fetch(_baseUrl + '/api/ia/macro', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        })
        .then(r => _parseJsonResponse(r))
        .then(data => {
            _hideLoading('macroLoading');
            if (data.success) {
                _currentMacroData = data;
                _renderMacro(data);
                _showEl('macroResult');
            } else {
                _showError('macroError', data.message || data.error || 'Erreur inconnue');
            }
        })
        .catch(err => {
            _hideLoading('macroLoading');
            _showError('macroError', 'Erreur réseau : ' + err.message);
        });
    }

    /**
     * Rendu Plotly de la vue Macro (centroïdes + nuage de fond).
     */
    function _renderMacro(data) {
        const container = document.getElementById('macroPlot');
        if (!container) return;

        const traces = [];

        // --- Nuage de fond (tous les points, gris transparent) ---
        if (data.all_points && data.all_points.length > 0) {
            // Regrouper les all_points par exercise_name pour la couleur
            const byExo = {};
            data.all_points.forEach(p => {
                if (!byExo[p.exercise_name]) byExo[p.exercise_name] = { x: [], y: [], ids: [] };
                byExo[p.exercise_name].x.push(p.x);
                byExo[p.exercise_name].y.push(p.y);
                byExo[p.exercise_name].ids.push(p.exercice_id);
            });

            let colorIdx = 0;
            Object.keys(byExo).forEach(exName => {
                const grp = byExo[exName];
                const col = COLORS_10[colorIdx % COLORS_10.length];
                traces.push({
                    x: grp.x,
                    y: grp.y,
                    mode: 'markers',
                    type: 'scatter',
                    name: exName,
                    marker: {
                        size: 5,
                        color: col,
                        opacity: 0.25,
                    },
                    hoverinfo: 'text',
                    text: grp.x.map(() => exName),
                    showlegend: false,
                    customdata: grp.ids,
                });
                colorIdx++;
            });
        }

        // --- Centroïdes (gros points cliquables avec labels) ---
        if (data.centroids && data.centroids.length > 0) {
            const cx = data.centroids.map(c => c.x);
            const cy = data.centroids.map(c => c.y);
            const labels = data.centroids.map(c => c.exercise_name);
            const sizes  = data.centroids.map(c => Math.max(14, Math.min(40, 8 + Math.sqrt(c.n_attempts) * 3)));
            const colors = data.centroids.map((_, i) => COLORS_10[i % COLORS_10.length]);
            const ids    = data.centroids.map(c => c.exercice_id);
            const hovers = data.centroids.map(c =>
                `<b>${_esc(c.exercise_name)}</b><br>${c.n_attempts} tentatives<br><i>Cliquer pour détailler</i>`
            );

            traces.push({
                x: cx,
                y: cy,
                mode: 'markers+text',
                type: 'scatter',
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

        const layout = {
            title: {
                text: 'Vue Macro — Cartographie globale des TDs',
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

        Plotly.newPlot(container, traces, layout, { responsive: true }).then(() => {
            // Clic sur un centroïde → vue micro
            container.on('plotly_click', function (evtData) {
                if (!evtData || !evtData.points || evtData.points.length === 0) return;
                const pt = evtData.points[0];
                const exerciseId = pt.customdata;
                if (exerciseId && typeof exerciseId === 'number' && exerciseId > 0) {
                    // Trouver le nom de l'exercice
                    const centroid = data.centroids.find(c => c.exercice_id === exerciseId);
                    _onMacroClick(exerciseId, centroid ? centroid.exercise_name : '');
                }
            });
        });

        // Métadonnées
        const metaEl = document.getElementById('macroMeta');
        if (metaEl) {
            metaEl.innerHTML =
                `<span class="meta-item"><strong>${data.n_points}</strong> tentatives analysées</span>` +
                `<span class="meta-item"><strong>${data.n_exercises}</strong> exercices (TDs)</span>`;
        }
    }

    /**
     * Callback quand on clique sur un centroïde macro → passage en vue micro.
     */
    function _onMacroClick(exerciseId, exerciseName) {
        // Basculer vers l'onglet Micro
        document.querySelectorAll('.ia-tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.ia-tab-btn').forEach(b => b.classList.remove('active'));

        const microTab = document.getElementById('tab-micro');
        if (microTab) microTab.classList.add('active');
        const btns = document.querySelectorAll('.ia-tab-btn');
        if (btns.length >= 3) btns[2].classList.add('active');

        // Pré-remplir le sélecteur d'exercice
        const sel = document.getElementById('microExercise');
        if (sel) {
            sel.value = exerciseId;
            // Si l'option n'existe pas, la créer
            if (sel.value != exerciseId) {
                const opt = document.createElement('option');
                opt.value = exerciseId;
                opt.textContent = exerciseName || 'Exercice #' + exerciseId;
                sel.appendChild(opt);
                sel.value = exerciseId;
            }
        }

        // Pré-remplir l'info
        const infoEl = document.getElementById('microSelectedExo');
        if (infoEl) {
            infoEl.textContent = exerciseName || 'Exercice #' + exerciseId;
        }

        // Lancer automatiquement la vue micro
        loadMicro({
            exercise_id: exerciseId,
            n_clusters: parseInt(document.getElementById('microK')?.value) || 8,
            perplexity: parseInt(document.getElementById('microPerplexity')?.value) || 30,
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  NIVEAU 2 — VUE MICRO (+ TRAJECTOIRES)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Lance l'analyse Micro (POST /api/ia/micro).
     * @param {Object} opts  { exercise_id, n_clusters?, perplexity? }
     */
    function loadMicro(opts) {
        if (!opts || !opts.exercise_id) {
            _showError('microError', 'Veuillez sélectionner un exercice.');
            return;
        }

        _showLoading('microLoading');
        _hideEl('microError');
        _hideEl('microResult');

        fetch(_baseUrl + '/api/ia/micro', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                exercise_id: parseInt(opts.exercise_id),
                n_clusters:  parseInt(opts.n_clusters) || 8,
                perplexity:  parseInt(opts.perplexity) || 30,
            }),
        })
        .then(r => _parseJsonResponse(r))
        .then(data => {
            _hideLoading('microLoading');
            if (data.success) {
                _currentMicroData = data;
                _renderMicro(data);
                _showEl('microResult');
            } else {
                _showError('microError', data.message || data.error || 'Erreur inconnue');
            }
        })
        .catch(err => {
            _hideLoading('microLoading');
            _showError('microError', 'Erreur réseau : ' + err.message);
        });
    }

    /**
     * Rendu Plotly de la vue Micro (clusters + trajectoires).
     */
    function _renderMicro(data) {
        const container = document.getElementById('microPlot');
        if (!container) return;

        const points = data.points || [];
        if (points.length === 0) return;

        const nClusters = data.n_clusters || 8;
        const traces = [];

        // --- 1) Points colorés par cluster ---
        for (let c = 0; c < nClusters; c++) {
            const clusterPts = points.filter(p => p.cluster === c);
            if (clusterPts.length === 0) continue;

            const col = COLORS_10[c % COLORS_10.length];
            traces.push({
                x: clusterPts.map(p => p.x),
                y: clusterPts.map(p => p.y),
                mode: 'markers',
                type: 'scatter',
                name: `Cluster ${c}`,
                marker: {
                    size: 8,
                    color: col,
                    opacity: 0.75,
                    line: { width: 1, color: '#fff' },
                    symbol: clusterPts.map(p => p.correct ? 'circle' : 'x'),
                },
                hoverinfo: 'text',
                text: clusterPts.map(p =>
                    `<b>Étudiant:</b> ${_esc(p.user_id)}<br>` +
                    `<b>Cluster:</b> ${p.cluster}<br>` +
                    `<b>Correct:</b> ${p.correct ? '✓ Oui' : '✗ Non'}<br>` +
                    `<b>Date:</b> ${p.date || '—'}<br>` +
                    `<b>Tentative:</b> #${p.attempt_id}`
                ),
            });
        }

        // --- 2) Trajectoires par étudiant (lignes directionnelles) ---
        const showTrajectories = document.getElementById('microShowTrajectories')?.checked !== false;
        if (showTrajectories) {
            const trajectoryTraces = _buildTrajectoryTraces(points);
            trajectoryTraces.forEach(t => traces.push(t));
        }

        const exName = data.exercise_name || '';
        const layout = {
            title: {
                text: `Vue Micro — ${exName}<br><sup>${data.n_points} tentatives, ${nClusters} clusters</sup>`,
                font: { size: 15, color: '#2c3e50' },
            },
            xaxis: { title: 't-SNE dim. 1', zeroline: false, showgrid: true, gridcolor: '#ecf0f1' },
            yaxis: { title: 't-SNE dim. 2', zeroline: false, showgrid: true, gridcolor: '#ecf0f1' },
            hovermode: 'closest',
            plot_bgcolor: '#fafbfc',
            paper_bgcolor: '#fff',
            margin: { t: 70, b: 50, l: 60, r: 30 },
            legend: { orientation: 'h', y: -0.18 },
            showlegend: true,
        };

        Plotly.newPlot(container, traces, layout, { responsive: true });

        // Métadonnées
        const metaEl = document.getElementById('microMeta');
        if (metaEl) {
            const uniqueStudents = new Set(points.map(p => p.user_id)).size;
            metaEl.innerHTML =
                `<span class="meta-item"><strong>${data.n_points}</strong> tentatives</span>` +
                `<span class="meta-item"><strong>${nClusters}</strong> clusters</span>` +
                `<span class="meta-item"><strong>${uniqueStudents}</strong> étudiants</span>` +
                `<span class="meta-item">Exercice : <strong>${_esc(exName)}</strong></span>`;
        }
    }

    /**
     * Construit les traces Plotly pour les trajectoires étudiantes.
     * Pour chaque user_id, trie les tentatives chronologiquement et
     * trace des segments avec des flèches (annotations).
     */
    function _buildTrajectoryTraces(points) {
        const traces = [];

        // Regrouper par user_id
        const byUser = {};
        points.forEach(p => {
            if (!byUser[p.user_id]) byUser[p.user_id] = [];
            byUser[p.user_id].push(p);
        });

        // Palette de couleurs pour les trajectoires (plus subtile)
        const trajColors = [
            'rgba(52,73,94,0.4)', 'rgba(142,68,173,0.4)', 'rgba(41,128,185,0.4)',
            'rgba(39,174,96,0.4)', 'rgba(243,156,18,0.4)', 'rgba(192,57,43,0.4)',
            'rgba(22,160,133,0.4)', 'rgba(127,140,141,0.4)',
        ];

        let colIdx = 0;
        const annotations = [];

        Object.keys(byUser).forEach(userId => {
            let userPts = byUser[userId];
            if (userPts.length < 2) return; // Pas de trajectoire pour un seul point

            // Trier par date puis par attempt_id
            userPts.sort((a, b) => {
                if (a.date && b.date && a.date !== b.date) return a.date.localeCompare(b.date);
                return (a.attempt_id || 0) - (b.attempt_id || 0);
            });

            const col = trajColors[colIdx % trajColors.length];
            colIdx++;

            // Trace ligne
            traces.push({
                x: userPts.map(p => p.x),
                y: userPts.map(p => p.y),
                mode: 'lines',
                type: 'scatter',
                name: `Traj. ${userId}`,
                line: {
                    color: col,
                    width: 1.5,
                    dash: 'dot',
                },
                hoverinfo: 'skip',
                showlegend: false,
            });

            // Flèches (annotations Plotly) du point N-1 vers N
            for (let i = 0; i < userPts.length - 1; i++) {
                const fromPt = userPts[i];
                const toPt   = userPts[i + 1];
                // N'ajouter des flèches que si les points sont suffisamment éloignés
                const dx = toPt.x - fromPt.x;
                const dy = toPt.y - fromPt.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 0.5) continue; // sauter les points très proches

                annotations.push({
                    x: toPt.x,
                    y: toPt.y,
                    ax: fromPt.x,
                    ay: fromPt.y,
                    xref: 'x',
                    yref: 'y',
                    axref: 'x',
                    ayref: 'y',
                    showarrow: true,
                    arrowhead: 3,
                    arrowsize: 1.2,
                    arrowwidth: 1.5,
                    arrowcolor: col.replace('0.4', '0.6'),
                    standoff: 4,
                    startstandoff: 4,
                });
            }
        });

        // Stocker les annotations dans un attribut spécial pour les appliquer au layout
        if (annotations.length > 0) {
            // On va les injecter via relayout après le rendu
            setTimeout(() => {
                const container = document.getElementById('microPlot');
                if (container && window.Plotly) {
                    // Limiter les flèches si trop nombreuses pour la perf
                    const maxAnnotations = 200;
                    const annots = annotations.length > maxAnnotations
                        ? annotations.slice(0, maxAnnotations)
                        : annotations;
                    Plotly.relayout(container, { annotations: annots });
                }
            }, 100);
        }

        return traces;
    }

    /**
     * Re-render micro avec/sans trajectoires (toggle).
     */
    function toggleTrajectories() {
        if (_currentMicroData) {
            _renderMicro(_currentMicroData);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    function _parseJsonResponse(r) {
        const ct = r.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            return r.text().then(txt => {
                throw new Error('Réponse non-JSON (HTTP ' + r.status + ')');
            });
        }
        return r.json();
    }

    function _showLoading(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('visible');
    }
    function _hideLoading(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('visible');
    }
    function _showEl(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('visible');
    }
    function _hideEl(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('visible');
    }
    function _showError(id, msg) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = '❌ ' + msg;
            el.classList.add('visible');
        }
    }
    function _esc(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── API publique ────────────────────────────────────────────────────────
    return {
        init: init,
        loadMacro: loadMacro,
        loadMicro: loadMicro,
        toggleTrajectories: toggleTrajectories,
    };

})();

