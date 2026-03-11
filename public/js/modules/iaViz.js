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
     * Rendu Plotly de la vue Micro (clusters + trajectoires + hover focus).
     */
    function _renderMicro(data) {
        const container = document.getElementById('microPlot');
        if (!container) return;

        const points = data.points || [];
        if (points.length === 0) return;

        const nClusters = data.n_clusters || 8;
        const traces = [];

        // ── Palette moderne cohérente avec le reste de l'UI ──
        const PALETTE = [
            '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6',
            '#1abc9c', '#e67e22', '#34495e', '#d35400', '#16a085'
        ];
        const APP_FONT = "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif";

        // ── Opacités par défaut (visibilité améliorée) ──
        const DIM_OPACITY = 0.65;
        const DIM_LINE_WIDTH = 1.5;

        // ── Calcul des statistiques par cluster ──
        const clusterStats = {};
        points.forEach(p => {
            const c = p.cluster;
            if (!clusterStats[c]) clusterStats[c] = { total: 0, correct: 0 };
            clusterStats[c].total++;
            if (p.correct) clusterStats[c].correct++;
        });

        // --- 1) Points colorés par cluster avec légende intelligente ---
        for (let c = 0; c < nClusters; c++) {
            const clusterPts = points.filter(p => p.cluster === c);
            if (clusterPts.length === 0) continue;

            const col = PALETTE[c % PALETTE.length];
            const stats = clusterStats[c] || { total: 0, correct: 0 };
            const successRate = stats.total > 0 ? (stats.correct / stats.total) : 0;

            // Nom intelligent de la légende
            const clusterLabel = successRate > 0.5
                ? `Cluster ${c} : Solutions validées (${stats.total} tentatives)`
                : `Cluster ${c} : Stratégie alternative (${stats.total} tentatives)`;

            traces.push({
                x: clusterPts.map(p => p.x),
                y: clusterPts.map(p => p.y),
                mode: 'markers',
                type: 'scatter',
                name: clusterLabel,
                marker: {
                    size: clusterPts.map(p => p.correct ? 14 : 8),
                    color: col,
                    opacity: DIM_OPACITY,
                    line: {
                        width: clusterPts.map(p => p.correct ? 2.5 : 1),
                        color: clusterPts.map(p => p.correct ? '#FFD700' : 'rgba(255,255,255,0.6)'),
                    },
                    symbol: clusterPts.map(p => p.correct ? 'star' : 'circle'),
                },
                hoverinfo: 'text',
                text: clusterPts.map(p => {
                    const dateStr = p.date && p.date !== '' && p.date !== 'None'
                        ? _formatDate(p.date)
                        : '—';
                    return `<b>👤 Étudiant :</b> ${_esc(p.user_id)}<br>` +
                           `<b>🎯 Cluster :</b> ${p.cluster}<br>` +
                           `<b>${p.correct ? '✅' : '❌'} Correct :</b> ${p.correct ? '<span style="color:#2ecc71;font-weight:bold">Oui</span>' : '<span style="color:#e74c3c">Non</span>'}<br>` +
                           `<b>📝 Tentative :</b> #${p.attempt_id}<br>` +
                           `<b>📅 Date :</b> ${dateStr}<br>` +
                           `<b>📍 Position :</b> (${p.x.toFixed(2)}, ${p.y.toFixed(2)})`;
                }),
                customdata: clusterPts.map(p => p.user_id),
                hoverlabel: {
                    bgcolor: '#2c3e50',
                    bordercolor: '#ecf0f1',
                    font: { color: '#fff', size: 12, family: APP_FONT },
                },
            });
        }

        // Nombre de traces de clusters (pour identifier les traces trajectoires ensuite)
        const nClusterTraces = traces.length;

        // --- 2) Trajectoires par étudiant (lignes avec flèches) ---
        const showTrajectories = document.getElementById('microShowTrajectories')?.checked !== false;
        const trajectoryAnnotations = [];

        if (showTrajectories) {
            const byUser = {};
            points.forEach(p => {
                if (!byUser[p.user_id]) byUser[p.user_id] = [];
                byUser[p.user_id].push(p);
            });

            const trajColors = [
                'rgba(52,73,94,{a})', 'rgba(142,68,173,{a})', 'rgba(41,128,185,{a})',
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
                const dimCol = colTemplate.replace('{a}', String(DIM_OPACITY * 0.6));
                colIdx++;

                traces.push({
                    x: userPts.map(p => p.x),
                    y: userPts.map(p => p.y),
                    mode: 'lines',
                    type: 'scatter',
                    name: `Traj. ${userId}`,
                    line: {
                        color: dimCol,
                        width: DIM_LINE_WIDTH,
                        dash: 'dot',
                    },
                    hoverinfo: 'skip',
                    showlegend: false,
                    customdata: userPts.map(() => userId),
                    _userId: userId,
                    _colTemplate: colTemplate,
                });

                // Flèches (annotations) du point N-1 vers N
                for (let i = 0; i < userPts.length - 1; i++) {
                    const fromPt = userPts[i];
                    const toPt = userPts[i + 1];
                    const dx = toPt.x - fromPt.x;
                    const dy = toPt.y - fromPt.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 0.3) continue;

                    trajectoryAnnotations.push({
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
                        arrowsize: 1.4,
                        arrowwidth: DIM_LINE_WIDTH,
                        arrowcolor: colTemplate.replace('{a}', String(DIM_OPACITY * 0.5)),
                        standoff: 5,
                        startstandoff: 5,
                        opacity: DIM_OPACITY * 0.6,
                        _userId: userId,
                        _colTemplate: colTemplate,
                    });
                }
            });
        }

        const exName = data.exercise_name || '';
        const layout = {
            title: {
                text: `Vue Micro — ${exName}<br><sup>${data.n_points} tentatives, ${nClusters} clusters · Survolez un point pour isoler la trajectoire</sup>`,
                font: { size: 15, color: '#2c3e50', family: APP_FONT },
            },
            xaxis: {
                title: { text: 't-SNE dim. 1', font: { family: APP_FONT, size: 12, color: '#7f8c8d' } },
                zeroline: false,
                showgrid: true,
                gridcolor: 'rgba(189,195,199,0.25)',
                gridwidth: 1,
                showline: false,
            },
            yaxis: {
                title: { text: 't-SNE dim. 2', font: { family: APP_FONT, size: 12, color: '#7f8c8d' } },
                zeroline: false,
                showgrid: true,
                gridcolor: 'rgba(189,195,199,0.25)',
                gridwidth: 1,
                showline: false,
            },
            font: { family: APP_FONT },
            hovermode: 'closest',
            plot_bgcolor: 'rgba(0,0,0,0)',
            paper_bgcolor: 'rgba(0,0,0,0)',
            margin: { t: 80, b: 60, l: 60, r: 30 },
            legend: {
                orientation: 'h',
                y: -0.22,
                x: 0.5,
                xanchor: 'center',
                font: { size: 11, color: '#2c3e50', family: APP_FONT },
                bgcolor: 'rgba(255,255,255,0.7)',
                bordercolor: 'rgba(189,195,199,0.3)',
                borderwidth: 1,
            },
            showlegend: true,
            annotations: trajectoryAnnotations.length > 300
                ? trajectoryAnnotations.slice(0, 300)
                : trajectoryAnnotations,
        };

        Plotly.newPlot(container, traces, layout, { responsive: true }).then(() => {
            // ── Hover Focus : surbrillance de la trajectoire de l'utilisateur survolé ──
            container.on('plotly_hover', function (evtData) {
                if (!evtData || !evtData.points || evtData.points.length === 0) return;
                const pt = evtData.points[0];
                const hoveredUserId = pt.customdata;
                if (!hoveredUserId) return;

                _highlightUser(container, traces, nClusterTraces, hoveredUserId, trajectoryAnnotations, layout);
            });

            container.on('plotly_unhover', function () {
                _resetHighlight(container, traces, nClusterTraces, trajectoryAnnotations, layout);
            });
        });

        // Métadonnées
        const metaEl = document.getElementById('microMeta');
        if (metaEl) {
            const uniqueStudents = new Set(points.map(p => p.user_id)).size;
            const correctCount = points.filter(p => p.correct).length;
            metaEl.innerHTML =
                `<span class="meta-item"><strong>${data.n_points}</strong> tentatives</span>` +
                `<span class="meta-item"><strong>${nClusters}</strong> clusters</span>` +
                `<span class="meta-item"><strong>${uniqueStudents}</strong> étudiants</span>` +
                `<span class="meta-item">✅ <strong>${correctCount}</strong> réussies (★ = réussite)</span>` +
                `<span class="meta-item">Exercice : <strong>${_esc(exName)}</strong></span>`;
        }
    }

    /**
     * Met en surbrillance la trajectoire d'un utilisateur donné.
     */
    function _highlightUser(container, traces, nClusterTraces, userId, annotations, layout) {
        const DIM_OPACITY = 0.08;
        const BRIGHT_OPACITY = 1.0;

        const update = {};

        // Mise à jour des traces de clusters (points)
        for (let i = 0; i < nClusterTraces; i++) {
            const trace = traces[i];
            if (!trace.customdata) continue;
            const opacities = trace.customdata.map(uid => uid === userId ? BRIGHT_OPACITY : DIM_OPACITY);
            const sizes = [];
            // Recalculer les tailles : en surbrillance les points sont plus gros
            if (trace.marker && trace.marker.symbol) {
                for (let j = 0; j < trace.customdata.length; j++) {
                    const isHovered = trace.customdata[j] === userId;
                    const isStar = trace.marker.symbol[j] === 'star';
                    if (isHovered) {
                        sizes.push(isStar ? 18 : 11);
                    } else {
                        sizes.push(isStar ? 14 : 8);
                    }
                }
            }
            update['marker.opacity'] = opacities;
            if (sizes.length > 0) update['marker.size'] = sizes;
            Plotly.restyle(container, update, [i]);
        }

        // Mise à jour des traces de trajectoires (lignes)
        for (let i = nClusterTraces; i < traces.length; i++) {
            const trace = traces[i];
            const isHighlighted = trace._userId === userId;
            Plotly.restyle(container, {
                'line.color': isHighlighted
                    ? trace._colTemplate.replace('{a}', '0.9')
                    : trace._colTemplate.replace('{a}', String(DIM_OPACITY)),
                'line.width': isHighlighted ? 3 : 0.5,
                'line.dash': isHighlighted ? 'solid' : 'dot',
            }, [i]);
        }

        // Mise à jour des annotations (flèches)
        if (annotations.length > 0) {
            const updatedAnnotations = annotations.map(ann => {
                const isHighlighted = ann._userId === userId;
                return Object.assign({}, ann, {
                    arrowcolor: ann._colTemplate.replace('{a}', isHighlighted ? '0.9' : String(DIM_OPACITY)),
                    arrowwidth: isHighlighted ? 2.5 : 0.8,
                    opacity: isHighlighted ? 1.0 : DIM_OPACITY,
                });
            });
            const limited = updatedAnnotations.length > 300 ? updatedAnnotations.slice(0, 300) : updatedAnnotations;
            Plotly.relayout(container, { annotations: limited });
        }
    }

    /**
     * Réinitialise toutes les opacités (état par défaut : visible).
     */
    function _resetHighlight(container, traces, nClusterTraces, annotations, layout) {
        const DIM_OPACITY = 0.65;
        const DIM_LINE_WIDTH = 1.5;

        // Reset des points de clusters
        for (let i = 0; i < nClusterTraces; i++) {
            const trace = traces[i];
            if (!trace.customdata) continue;
            const opacities = trace.customdata.map(() => DIM_OPACITY);
            const sizes = [];
            if (trace.marker && trace.marker.symbol) {
                for (let j = 0; j < trace.customdata.length; j++) {
                    const isStar = trace.marker.symbol[j] === 'star';
                    sizes.push(isStar ? 14 : 8);
                }
            }
            const update = { 'marker.opacity': opacities };
            if (sizes.length > 0) update['marker.size'] = sizes;
            Plotly.restyle(container, update, [i]);
        }

        // Reset des lignes de trajectoires
        for (let i = nClusterTraces; i < traces.length; i++) {
            const trace = traces[i];
            Plotly.restyle(container, {
                'line.color': trace._colTemplate.replace('{a}', String(DIM_OPACITY * 0.6)),
                'line.width': DIM_LINE_WIDTH,
                'line.dash': 'dot',
            }, [i]);
        }

        // Reset des annotations
        if (annotations.length > 0) {
            const resetAnnotations = annotations.map(ann => Object.assign({}, ann, {
                arrowcolor: ann._colTemplate.replace('{a}', String(DIM_OPACITY * 0.5)),
                arrowwidth: DIM_LINE_WIDTH,
                opacity: DIM_OPACITY * 0.6,
            }));
            const limited = resetAnnotations.length > 300 ? resetAnnotations.slice(0, 300) : resetAnnotations;
            Plotly.relayout(container, { annotations: limited });
        }
    }

    /**
     * Formate une date ISO en format lisible.
     */
    function _formatDate(dateStr) {
        if (!dateStr || dateStr === 'None' || dateStr === '') return '—';
        try {
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('fr-FR', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        } catch (e) {
            return dateStr;
        }
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
