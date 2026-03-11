/**
 * dashboardIaChart.js — Module de visualisation IA intégré au Dashboard
 *
 * Deux modes :
 *   - Macro (Global)  : t-SNE global, centroïdes par exercice, clic → zoom Micro
 *   - Micro (Détail)  : K-Means + t-SNE pour un exercice, trajectoires par étudiant
 *
 * Se synchronise avec le reste du dashboard via des événements custom.
 */
const DashboardIaChart = (function () {
    'use strict';

    // ── Constantes ──────────────────────────────────────────────────────────
    const COLORS_10 = [
        '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6',
        '#1abc9c', '#e67e22', '#34495e', '#d35400', '#7f8c8d'
    ];
    const DIM_OPACITY     = 0.15;
    const BRIGHT_OPACITY  = 1.0;

    // ── État interne ────────────────────────────────────────────────────────
    let _baseUrl          = '';
    let _resourceId       = null;
    let _currentMode      = 'macro';   // 'macro' | 'micro'
    let _currentExerciseId = null;
    let _macroCache       = null;
    let _microCache       = null;
    let _loading          = false;

    // Références aux traces pour le hover-focus micro
    let _microTraces         = [];
    let _microNClusterTraces = 0;
    let _microAnnotations    = [];
    let _microLayout         = {};

    // ══════════════════════════════════════════════════════════════════════════
    //  INITIALISATION
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Initialise le module. Appelé automatiquement au DOMContentLoaded.
     */
    function init() {
        _baseUrl    = window.BASE_URL || '';
        _resourceId = window.RESOURCE_ID || null;

        // Charger la vue macro au démarrage
        loadMacro();

        // Écouter les événements de sélection d'exercice depuis le dashboard
        document.addEventListener('exercise-chart-click', function (e) {
            const exerciseId = e.detail && e.detail.exerciseId;
            if (exerciseId) {
                loadMicro(exerciseId);
            }
        });

        window.addEventListener('exerciseSelected', function (e) {
            const exerciseId = e.detail;
            if (exerciseId) {
                loadMicro(exerciseId);
            }
        });

        // Écouter le retour à la vue globale (quand l'utilisateur quitte le détail)
        window.addEventListener('dashboardBackToGlobal', function () {
            backToMacro();
        });

        console.log('[DashboardIaChart] Initialisé.');
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  VUE MACRO (Global)
    // ══════════════════════════════════════════════════════════════════════════

    function loadMacro() {
        if (_loading) return;
        _currentMode = 'macro';
        _currentExerciseId = null;
        _loading = true;

        _showStatus('⏳ Chargement de la cartographie globale…');
        _hideBackBtn();

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
            if (data.success) {
                _macroCache = data;
                _renderMacro(data);
                _showStatus('');
            } else {
                _showStatus('⚠️ ' + (data.message || data.error || 'Impossible de charger la vue globale.'));
            }
        })
        .catch(err => {
            _loading = false;
            _showStatus('❌ Erreur réseau : ' + err.message);
        });
    }

    function _renderMacro(data) {
        const container = document.getElementById('ai-clustering-plot');
        if (!container) return;

        const traces = [];

        // Nuage de fond (tous les points, colorés par exercice, faible opacité)
        if (data.all_points && data.all_points.length > 0) {
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
                    x: grp.x, y: grp.y,
                    mode: 'markers', type: 'scatter',
                    name: exName,
                    marker: { size: 5, color: col, opacity: 0.2 },
                    hoverinfo: 'text',
                    text: grp.x.map(() => exName),
                    showlegend: false,
                    customdata: grp.ids,
                });
                colorIdx++;
            });
        }

        // Centroïdes cliquables
        if (data.centroids && data.centroids.length > 0) {
            const cx     = data.centroids.map(c => c.x);
            const cy     = data.centroids.map(c => c.y);
            const labels = data.centroids.map(c => c.exercise_name);
            const sizes  = data.centroids.map(c => Math.max(14, Math.min(40, 8 + Math.sqrt(c.n_attempts) * 3)));
            const colors = data.centroids.map((_, i) => COLORS_10[i % COLORS_10.length]);
            const ids    = data.centroids.map(c => c.exercice_id);
            const hovers = data.centroids.map(c =>
                `<b>${_esc(c.exercise_name)}</b><br>${c.n_attempts} tentatives<br><i>Cliquer pour détailler</i>`
            );

            traces.push({
                x: cx, y: cy,
                mode: 'markers+text', type: 'scatter',
                name: 'Centroïdes TDs',
                marker: { size: sizes, color: colors, line: { width: 2, color: '#fff' }, opacity: 0.9 },
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
                text: 'Vue Macro — Cartographie globale des exercices',
                font: { size: 15, color: '#2c3e50' },
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
            container.on('plotly_click', function (evtData) {
                if (!evtData || !evtData.points || evtData.points.length === 0) return;
                const pt = evtData.points[0];
                const exerciseId = pt.customdata;
                if (exerciseId && typeof exerciseId === 'number' && exerciseId > 0) {
                    loadMicro(exerciseId);

                    // Émettre un événement pour synchroniser le reste du dashboard
                    document.dispatchEvent(new CustomEvent('exercise-chart-click', {
                        detail: { exerciseId: exerciseId }
                    }));
                }
            });
        });

        // Métadonnées
        _setMeta(
            `<span><strong>${data.n_points || 0}</strong> tentatives analysées</span>` +
            `<span style="margin-left:1.5rem;"><strong>${data.n_exercises || 0}</strong> exercices</span>`
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  VUE MICRO (Détail d'un exercice)
    // ══════════════════════════════════════════════════════════════════════════

    function loadMicro(exerciseId) {
        if (_loading) return;
        if (!exerciseId || exerciseId <= 0) return;

        _currentMode = 'micro';
        _currentExerciseId = exerciseId;
        _loading = true;

        _showStatus('⏳ Chargement du clustering pour l\'exercice…');
        _showBackBtn();

        fetch(_baseUrl + '/api/dashboard/ia/micro', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                exercise_id: parseInt(exerciseId),
                n_clusters: 8,
                perplexity: 30,
            }),
        })
        .then(r => r.json())
        .then(data => {
            _loading = false;
            if (data.success) {
                _microCache = data;
                _renderMicro(data);
                _showStatus('');
            } else {
                _showStatus('⚠️ ' + (data.message || data.error || 'Impossible de charger le clustering.'));
            }
        })
        .catch(err => {
            _loading = false;
            _showStatus('❌ Erreur réseau : ' + err.message);
        });
    }

    function _renderMicro(data) {
        const container = document.getElementById('ai-clustering-plot');
        if (!container) return;

        const points = data.points || [];
        if (points.length === 0) {
            _showStatus('⚠️ Aucun point à afficher.');
            return;
        }

        const nClusters = data.n_clusters || 8;
        const traces = [];

        // 1) Points colorés par cluster (forme étoile si correct)
        for (let c = 0; c < nClusters; c++) {
            const clusterPts = points.filter(p => p.cluster === c);
            if (clusterPts.length === 0) continue;

            const col = COLORS_10[c % COLORS_10.length];

            traces.push({
                x: clusterPts.map(p => p.x),
                y: clusterPts.map(p => p.y),
                mode: 'markers', type: 'scatter',
                name: `Cluster ${c}`,
                marker: {
                    size:    clusterPts.map(p => p.correct ? 14 : 8),
                    color:   col,
                    opacity: DIM_OPACITY,
                    symbol:  clusterPts.map(p => p.correct ? 'star' : 'circle'),
                    line: {
                        width: clusterPts.map(p => p.correct ? 2.5 : 1),
                        color: clusterPts.map(p => p.correct ? '#FFD700' : '#fff'),
                    },
                },
                hoverinfo: 'text',
                text: clusterPts.map(p => {
                    const dateStr = p.date && p.date !== '' && p.date !== 'None' ? p.date : '—';
                    return `<b>👤 ${_esc(p.user_id)}</b><br>` +
                           `Cluster ${p.cluster} · ${p.correct ? '✅ Correct' : '❌ Incorrect'}<br>` +
                           `Tentative #${p.attempt_id}<br>` +
                           `Date : ${dateStr}`;
                }),
                customdata: clusterPts.map(p => p.user_id),
                hoverlabel: { bgcolor: '#2c3e50', font: { color: '#fff', size: 12 } },
            });
        }

        const nClusterTraces = traces.length;

        // 2) Trajectoires par étudiant (lignes + flèches)
        const trajectoryAnnotations = [];
        const byUser = {};
        points.forEach(p => {
            if (!byUser[p.user_id]) byUser[p.user_id] = [];
            byUser[p.user_id].push(p);
        });

        const trajColors = [
            'rgba(52,73,94,{a})',  'rgba(142,68,173,{a})', 'rgba(41,128,185,{a})',
            'rgba(39,174,96,{a})', 'rgba(243,156,18,{a})', 'rgba(192,57,43,{a})',
            'rgba(22,160,133,{a})', 'rgba(127,140,141,{a})',
        ];
        let colIdx = 0;

        Object.keys(byUser).forEach(userId => {
            let userPts = byUser[userId];
            if (userPts.length < 2) return;

            userPts.sort((a, b) => {
                if (a.date && b.date && a.date !== b.date) return a.date.localeCompare(b.date);
                return (a.attempt_id || 0) - (b.attempt_id || 0);
            });

            const colTemplate = trajColors[colIdx % trajColors.length];
            const dimCol = colTemplate.replace('{a}', String(DIM_OPACITY));
            colIdx++;

            traces.push({
                x: userPts.map(p => p.x),
                y: userPts.map(p => p.y),
                mode: 'lines', type: 'scatter',
                name: `Traj. ${userId}`,
                line: { color: dimCol, width: 1, dash: 'dot' },
                hoverinfo: 'skip',
                showlegend: false,
                customdata: userPts.map(() => userId),
                _userId: userId,
                _colTemplate: colTemplate,
            });

            // Flèches
            for (let i = 0; i < userPts.length - 1; i++) {
                const from = userPts[i], to = userPts[i + 1];
                const dist = Math.sqrt(Math.pow(to.x - from.x, 2) + Math.pow(to.y - from.y, 2));
                if (dist < 0.3) continue;

                trajectoryAnnotations.push({
                    x: to.x, y: to.y,
                    ax: from.x, ay: from.y,
                    xref: 'x', yref: 'y', axref: 'x', ayref: 'y',
                    showarrow: true, arrowhead: 3, arrowsize: 1.4,
                    arrowwidth: 1.5,
                    arrowcolor: colTemplate.replace('{a}', String(DIM_OPACITY)),
                    standoff: 5, startstandoff: 5,
                    opacity: DIM_OPACITY,
                    _userId: userId,
                    _colTemplate: colTemplate,
                });
            }
        });

        const exName = data.exercise_name || '';
        const layout = {
            title: {
                text: `Vue Micro — ${exName}<br><sup>${data.n_points} tentatives, ${nClusters} clusters · ★ = réussite · Survolez pour isoler une trajectoire</sup>`,
                font: { size: 14, color: '#2c3e50' },
            },
            xaxis: { title: 't-SNE dim. 1', zeroline: false, showgrid: true, gridcolor: '#ecf0f1' },
            yaxis: { title: 't-SNE dim. 2', zeroline: false, showgrid: true, gridcolor: '#ecf0f1' },
            hovermode: 'closest',
            plot_bgcolor: '#fafbfc',
            paper_bgcolor: '#fff',
            margin: { t: 80, b: 50, l: 60, r: 30 },
            legend: { orientation: 'h', y: -0.18 },
            showlegend: true,
            annotations: trajectoryAnnotations.length > 300
                ? trajectoryAnnotations.slice(0, 300)
                : trajectoryAnnotations,
        };

        // Stocker les refs pour le hover-focus
        _microTraces         = traces;
        _microNClusterTraces = nClusterTraces;
        _microAnnotations    = trajectoryAnnotations;
        _microLayout         = layout;

        Plotly.newPlot(container, traces, layout, { responsive: true }).then(() => {
            // Hover focus
            container.on('plotly_hover', function (evtData) {
                if (!evtData || !evtData.points || !evtData.points.length) return;
                const userId = evtData.points[0].customdata;
                if (userId) _highlightUser(container, userId);
            });
            container.on('plotly_unhover', function () {
                _resetHighlight(container);
            });
        });

        // Métadonnées
        const uniqueStudents = new Set(points.map(p => p.user_id)).size;
        const correctCount = points.filter(p => p.correct).length;
        _setMeta(
            `<span><strong>${data.n_points}</strong> tentatives</span>` +
            `<span style="margin-left:1rem;"><strong>${nClusters}</strong> clusters</span>` +
            `<span style="margin-left:1rem;"><strong>${uniqueStudents}</strong> étudiants</span>` +
            `<span style="margin-left:1rem;">✅ <strong>${correctCount}</strong> réussies</span>` +
            `<span style="margin-left:1rem;">Exercice : <strong>${_esc(exName)}</strong></span>`
        );
    }

    // ── Hover Focus : surbrillance d'une trajectoire ────────────────────────

    function _highlightUser(container, userId) {
        const traces = _microTraces;
        const nClusterTraces = _microNClusterTraces;

        // Points de clusters
        for (let i = 0; i < nClusterTraces; i++) {
            const trace = traces[i];
            if (!trace.customdata) continue;
            const opacities = trace.customdata.map(uid => uid === userId ? BRIGHT_OPACITY : 0.06);
            const sizes = [];
            if (trace.marker && trace.marker.symbol) {
                for (let j = 0; j < trace.customdata.length; j++) {
                    const isHovered = trace.customdata[j] === userId;
                    const isStar = trace.marker.symbol[j] === 'star';
                    sizes.push(isHovered ? (isStar ? 20 : 12) : (isStar ? 14 : 8));
                }
            }
            const upd = { 'marker.opacity': opacities };
            if (sizes.length > 0) upd['marker.size'] = sizes;
            Plotly.restyle(container, upd, [i]);
        }

        // Lignes de trajectoires
        for (let i = nClusterTraces; i < traces.length; i++) {
            const trace = traces[i];
            const isHL = trace._userId === userId;
            Plotly.restyle(container, {
                'line.color': isHL ? trace._colTemplate.replace('{a}', '0.9') : trace._colTemplate.replace('{a}', '0.04'),
                'line.width': isHL ? 3 : 0.5,
                'line.dash':  isHL ? 'solid' : 'dot',
            }, [i]);
        }

        // Annotations (flèches)
        if (_microAnnotations.length > 0) {
            const updated = _microAnnotations.map(ann => ({
                ...ann,
                arrowcolor: ann._colTemplate.replace('{a}', ann._userId === userId ? '0.9' : '0.04'),
                arrowwidth: ann._userId === userId ? 2.5 : 0.8,
                opacity:    ann._userId === userId ? 1.0 : 0.04,
            }));
            Plotly.relayout(container, {
                annotations: updated.length > 300 ? updated.slice(0, 300) : updated,
            });
        }
    }

    function _resetHighlight(container) {
        const traces = _microTraces;
        const nClusterTraces = _microNClusterTraces;

        for (let i = 0; i < nClusterTraces; i++) {
            const trace = traces[i];
            if (!trace.customdata) continue;
            const opacities = trace.customdata.map(() => DIM_OPACITY);
            const sizes = [];
            if (trace.marker && trace.marker.symbol) {
                for (let j = 0; j < trace.customdata.length; j++) {
                    sizes.push(trace.marker.symbol[j] === 'star' ? 14 : 8);
                }
            }
            const upd = { 'marker.opacity': opacities };
            if (sizes.length > 0) upd['marker.size'] = sizes;
            Plotly.restyle(container, upd, [i]);
        }

        for (let i = nClusterTraces; i < traces.length; i++) {
            const trace = traces[i];
            Plotly.restyle(container, {
                'line.color': trace._colTemplate.replace('{a}', String(DIM_OPACITY)),
                'line.width': 1,
                'line.dash': 'dot',
            }, [i]);
        }

        if (_microAnnotations.length > 0) {
            const reset = _microAnnotations.map(ann => ({
                ...ann,
                arrowcolor: ann._colTemplate.replace('{a}', String(DIM_OPACITY)),
                arrowwidth: 1.5,
                opacity: DIM_OPACITY,
            }));
            Plotly.relayout(container, {
                annotations: reset.length > 300 ? reset.slice(0, 300) : reset,
            });
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  NAVIGATION & SYNCHRONISATION
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Retour à la vue macro depuis la vue micro.
     */
    function backToMacro() {
        _currentMode = 'macro';
        _currentExerciseId = null;
        if (_macroCache) {
            _renderMacro(_macroCache);
            _hideBackBtn();
            _showStatus('');
        } else {
            loadMacro();
        }
    }

    /**
     * Rafraîchir la vue courante.
     */
    function refresh() {
        _macroCache = null;
        _microCache = null;
        if (_currentMode === 'micro' && _currentExerciseId) {
            loadMicro(_currentExerciseId);
        } else {
            loadMacro();
        }
    }

    /**
     * Appelé par le dashboard quand on change de vue exercice.
     * Permet la synchronisation externe.
     *
     * @param {number|null} exerciseId - ID exercice ou null pour revenir en macro
     */
    function syncWithDashboard(exerciseId) {
        if (exerciseId && exerciseId > 0) {
            loadMicro(exerciseId);
        } else {
            backToMacro();
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS DOM
    // ══════════════════════════════════════════════════════════════════════════

    function _showStatus(msg) {
        const el = document.getElementById('ai-clustering-status');
        if (el) {
            el.textContent = msg;
            el.style.display = msg ? 'block' : 'none';
        }
    }

    function _setMeta(html) {
        const el = document.getElementById('ai-clustering-meta');
        if (el) el.innerHTML = html;
    }

    function _showBackBtn() {
        const el = document.getElementById('ai-clustering-back-btn');
        if (el) el.style.display = 'inline-flex';
    }

    function _hideBackBtn() {
        const el = document.getElementById('ai-clustering-back-btn');
        if (el) el.style.display = 'none';
    }

    function _esc(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Auto-init ───────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // Petit délai pour laisser le dashboard se charger d'abord
        setTimeout(init, 500);
    });

    // ── API publique ────────────────────────────────────────────────────────
    return {
        init:               init,
        loadMacro:          loadMacro,
        loadMicro:          loadMicro,
        backToMacro:        backToMacro,
        refresh:            refresh,
        syncWithDashboard:  syncWithDashboard,
    };

})();

