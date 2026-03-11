/**
 * iaIntegration.js — Module d'intégration IA dans le dashboard (Macro/Micro)
 *
 * S'intègre dans le VizManager pour afficher :
 *   - Vue Macro (niveau 1) : bouton "Générer la cartographie IA" + graphe t-SNE global
 *   - Vue Micro (niveau 2B - TP) : graphe trajectoires par étudiant ou message d'info
 */

const PLOTLY_CDN = 'https://cdn.plot.ly/plotly-2.32.0.min.js';
const COLORS_10 = [
    '#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6',
    '#1abc9c', '#e67e22', '#34495e', '#d35400', '#7f8c8d'
];

let _plotlyLoaded = false;
let _plotlyLoading = null;

/**
 * Charge Plotly.js dynamiquement si pas encore chargé.
 */
function loadPlotly() {
    if (_plotlyLoaded || window.Plotly) {
        _plotlyLoaded = true;
        return Promise.resolve();
    }
    if (_plotlyLoading) return _plotlyLoading;

    _plotlyLoading = new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = PLOTLY_CDN;
        s.onload = () => { _plotlyLoaded = true; resolve(); };
        s.onerror = () => reject(new Error('Impossible de charger Plotly.js'));
        document.head.appendChild(s);
    });
    return _plotlyLoading;
}

function esc(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === 'None' || dateStr === '') return '—';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('fr-FR', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    } catch (e) { return dateStr; }
}

// ═══════════════════════════════════════════════════════════════════════════════
//  VUE MACRO — Section IA dans la vue globale de la ressource (niveau 1)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Rend la section Macro IA dans le conteneur donné.
 * @param {HTMLElement} parentContainer — l'élément dans lequel injecter la section
 * @param {number} resourceId — ID de la ressource
 * @param {Function} onExerciseClick — callback(exerciseId, exerciseName) quand on clique un centroïde
 */
export async function renderMacroSection(parentContainer, resourceId, onExerciseClick) {
    // Créer la carte IA
    const card = document.createElement('div');
    card.className = 'viz-chart-card';
    card.id = 'viz-ia-macro-card';
    card.style.gridColumn = '1 / -1';
    card.innerHTML = `
        <h3 class="viz-chart-title">🧠 Cartographie IA des TDs</h3>
        <div id="ia-macro-status" style="color:#7f8c8d;font-size:0.9rem;margin-bottom:1rem;">
            Vérification des données IA…
        </div>
        <div id="ia-macro-actions" style="display:none;text-align:center;margin-bottom:1rem;">
            <button id="ia-macro-generate-btn" class="viz-bc-btn" style="padding:10px 24px;font-size:0.95rem;border-radius:8px;cursor:pointer;">
                🚀 Générer la cartographie IA
            </button>
        </div>
        <div id="ia-macro-spinner" style="display:none;text-align:center;padding:2rem;">
            <div style="display:inline-block;width:40px;height:40px;border:4px solid #e0e0e0;border-top:4px solid #3498db;border-radius:50%;animation:ia-spin 1s linear infinite;"></div>
            <p style="color:#7f8c8d;margin-top:1rem;">Analyse IA en cours… Cela peut prendre quelques instants.</p>
        </div>
        <div id="ia-macro-error" style="display:none;color:#e74c3c;background:#fdf0ef;border:1px solid #f5c6cb;border-radius:6px;padding:12px;margin-bottom:1rem;"></div>
        <div id="ia-macro-plot" style="display:none;width:100%;min-height:450px;"></div>
        <div id="ia-macro-meta" style="display:none;text-align:center;color:#7f8c8d;font-size:0.85rem;margin-top:0.5rem;"></div>
    `;
    parentContainer.appendChild(card);

    // Ajouter l'animation CSS si pas encore présente
    if (!document.getElementById('ia-spin-style')) {
        const style = document.createElement('style');
        style.id = 'ia-spin-style';
        style.textContent = '@keyframes ia-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    // Vérifier le statut IA
    try {
        const BASE = window.BASE_URL || '';
        const resp = await fetch(`${BASE}/api/ia/status?resource_id=${resourceId}`);
        const data = await resp.json();

        const statusEl = document.getElementById('ia-macro-status');
        const actionsEl = document.getElementById('ia-macro-actions');

        if (data.success && data.available) {
            statusEl.innerHTML = `✅ ${esc(data.message)}`;
            actionsEl.style.display = 'block';

            // Bouton de génération
            const btn = document.getElementById('ia-macro-generate-btn');
            btn.addEventListener('click', () => {
                _launchMacroGeneration(resourceId, onExerciseClick);
            });
        } else {
            statusEl.innerHTML = `ℹ️ ${esc(data.message || 'Aucune donnée AES disponible pour cette ressource.')}`;
            statusEl.style.color = '#e67e22';
        }
    } catch (err) {
        const statusEl = document.getElementById('ia-macro-status');
        if (statusEl) statusEl.innerHTML = '⚠️ Impossible de vérifier le statut IA.';
        console.error('[iaIntegration] status check error:', err);
    }
}

/**
 * Lance la génération Macro (appel POST /api/ia/macro).
 */
async function _launchMacroGeneration(resourceId, onExerciseClick) {
    const actionsEl = document.getElementById('ia-macro-actions');
    const spinnerEl = document.getElementById('ia-macro-spinner');
    const errorEl = document.getElementById('ia-macro-error');
    const plotEl = document.getElementById('ia-macro-plot');
    const metaEl = document.getElementById('ia-macro-meta');
    const statusEl = document.getElementById('ia-macro-status');

    if (actionsEl) actionsEl.style.display = 'none';
    if (errorEl) errorEl.style.display = 'none';
    if (plotEl) plotEl.style.display = 'none';
    if (spinnerEl) spinnerEl.style.display = 'block';
    if (statusEl) statusEl.innerHTML = '⏳ Génération en cours…';

    try {
        await loadPlotly();

        const BASE = window.BASE_URL || '';
        const resp = await fetch(`${BASE}/api/ia/macro`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ resource_id: resourceId, perplexity: 30 }),
        });

        const ct = resp.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            throw new Error('Réponse non-JSON du serveur (HTTP ' + resp.status + ')');
        }
        const data = await resp.json();

        if (spinnerEl) spinnerEl.style.display = 'none';

        if (data.success) {
            statusEl.innerHTML = '✅ Cartographie IA générée avec succès !';
            _drawMacroPlot(data, plotEl, metaEl, onExerciseClick);
        } else {
            statusEl.innerHTML = '❌ Échec de la génération.';
            if (errorEl) {
                errorEl.textContent = '❌ ' + (data.message || data.error || 'Erreur inconnue');
                errorEl.style.display = 'block';
            }
            if (actionsEl) actionsEl.style.display = 'block';
        }
    } catch (err) {
        if (spinnerEl) spinnerEl.style.display = 'none';
        if (errorEl) {
            errorEl.textContent = '❌ Erreur réseau : ' + err.message;
            errorEl.style.display = 'block';
        }
        if (actionsEl) actionsEl.style.display = 'block';
        if (statusEl) statusEl.innerHTML = '⚠️ Erreur lors de la génération.';
        console.error('[iaIntegration] macro generation error:', err);
    }
}

/**
 * Dessine le graphe Macro (centroïdes + nuage) avec Plotly.
 */
function _drawMacroPlot(data, plotEl, metaEl, onExerciseClick) {
    if (!plotEl) return;
    plotEl.style.display = 'block';

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

    // Centroïdes (gros points cliquables)
    if (data.centroids && data.centroids.length > 0) {
        const cx = data.centroids.map(c => c.x);
        const cy = data.centroids.map(c => c.y);
        const labels = data.centroids.map(c => c.exercise_name);
        const sizes = data.centroids.map(c => Math.max(16, Math.min(45, 10 + Math.sqrt(c.n_attempts) * 3)));
        const colors = data.centroids.map((_, i) => COLORS_10[i % COLORS_10.length]);
        const ids = data.centroids.map(c => c.exercice_id);
        const hovers = data.centroids.map(c =>
            `<b>${esc(c.exercise_name)}</b><br>${c.n_attempts} tentatives<br><i>🔍 Cliquer pour détailler ce TD</i>`
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
        title: { text: 'Cartographie globale des TDs (t-SNE)', font: { size: 15, color: '#2c3e50' } },
        xaxis: { title: 't-SNE dim. 1', zeroline: false, showgrid: true, gridcolor: '#ecf0f1' },
        yaxis: { title: 't-SNE dim. 2', zeroline: false, showgrid: true, gridcolor: '#ecf0f1' },
        hovermode: 'closest',
        plot_bgcolor: '#fafbfc',
        paper_bgcolor: '#fff',
        margin: { t: 60, b: 50, l: 60, r: 30 },
        legend: { orientation: 'h', y: -0.15 },
    };

    Plotly.newPlot(plotEl, traces, layout, { responsive: true }).then(() => {
        // Clic sur un centroïde → navigation vers le TD (Vue Micro)
        plotEl.on('plotly_click', function (evtData) {
            if (!evtData || !evtData.points || evtData.points.length === 0) return;
            const pt = evtData.points[0];
            const exerciseId = pt.customdata;
            if (exerciseId && typeof exerciseId === 'number' && exerciseId > 0) {
                const centroid = data.centroids.find(c => c.exercice_id === exerciseId);
                const exerciseName = centroid ? centroid.exercise_name : '';
                if (typeof onExerciseClick === 'function') {
                    onExerciseClick(exerciseId, exerciseName);
                }
            }
        });
    });

    // Métadonnées
    if (metaEl) {
        metaEl.style.display = 'block';
        metaEl.innerHTML =
            `<span style="margin-right:1.5rem;"><strong>${data.n_points}</strong> tentatives analysées</span>` +
            `<span><strong>${data.n_exercises}</strong> exercices (TDs)</span>`;
    }
}


// ═══════════════════════════════════════════════════════════════════════════════
//  VUE MICRO — Section IA dans la vue d'un TP (niveau 2B)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Rend la section Micro IA (trajectoires) dans le conteneur donné.
 * @param {HTMLElement} parentContainer — l'élément parent
 * @param {number} exerciseId — ID de l'exercice
 * @param {string} exerciseName — Nom de l'exercice
 */
export async function renderMicroSection(parentContainer, exerciseId, exerciseName) {
    const card = document.createElement('div');
    card.className = 'viz-chart-card';
    card.id = 'viz-ia-micro-card';
    card.style.gridColumn = '1 / -1';
    card.innerHTML = `
        <h3 class="viz-chart-title">🧠 Cartographie des trajectoires IA — ${esc(exerciseName)}</h3>
        <div id="ia-micro-status" style="color:#7f8c8d;font-size:0.9rem;margin-bottom:0.5rem;">
            Vérification des données IA…
        </div>
        <div id="ia-micro-spinner" style="display:none;text-align:center;padding:2rem;">
            <div style="display:inline-block;width:36px;height:36px;border:4px solid #e0e0e0;border-top:4px solid #9b59b6;border-radius:50%;animation:ia-spin 1s linear infinite;"></div>
            <p style="color:#7f8c8d;margin-top:0.8rem;">Calcul des trajectoires en cours…</p>
        </div>
        <div id="ia-micro-error" style="display:none;color:#e74c3c;background:#fdf0ef;border:1px solid #f5c6cb;border-radius:6px;padding:12px;margin-bottom:1rem;"></div>
        <div id="ia-micro-plot" style="display:none;width:100%;min-height:500px;"></div>
        <div id="ia-micro-meta" style="display:none;text-align:center;color:#7f8c8d;font-size:0.85rem;margin-top:0.5rem;"></div>
    `;
    parentContainer.appendChild(card);

    // Ajouter l'animation CSS si pas encore présente
    if (!document.getElementById('ia-spin-style')) {
        const style = document.createElement('style');
        style.id = 'ia-spin-style';
        style.textContent = '@keyframes ia-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    // Vérifier si des données AES existent pour cet exercice
    try {
        const BASE = window.BASE_URL || '';
        const resp = await fetch(`${BASE}/api/ia/status?exercise_id=${exerciseId}`);
        const statusData = await resp.json();

        const statusEl = document.getElementById('ia-micro-status');

        if (statusData.success && statusData.available) {
            statusEl.innerHTML = '⏳ Chargement de la cartographie des trajectoires…';
            _launchMicroGeneration(exerciseId, exerciseName);
        } else {
            statusEl.innerHTML = `
                <div style="background:#fef9e7;border:1px solid #f9e79f;border-radius:8px;padding:16px;text-align:center;color:#7d6608;">
                    <strong>📌 Analyse IA non disponible pour ce TD</strong><br>
                    <span style="font-size:0.85rem;">Veuillez générer l'analyse IA depuis la page de la ressource (vue globale).</span>
                </div>
            `;
        }
    } catch (err) {
        const statusEl = document.getElementById('ia-micro-status');
        if (statusEl) statusEl.innerHTML = '⚠️ Impossible de vérifier le statut IA.';
        console.error('[iaIntegration] micro status check error:', err);
    }
}

/**
 * Lance la génération Micro (POST /api/ia/micro).
 */
async function _launchMicroGeneration(exerciseId, exerciseName) {
    const spinnerEl = document.getElementById('ia-micro-spinner');
    const errorEl = document.getElementById('ia-micro-error');
    const plotEl = document.getElementById('ia-micro-plot');
    const metaEl = document.getElementById('ia-micro-meta');
    const statusEl = document.getElementById('ia-micro-status');

    if (spinnerEl) spinnerEl.style.display = 'block';
    if (errorEl) errorEl.style.display = 'none';

    try {
        await loadPlotly();

        const BASE = window.BASE_URL || '';
        const resp = await fetch(`${BASE}/api/ia/micro`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ exercise_id: exerciseId, n_clusters: 8, perplexity: 30 }),
        });

        const ct = resp.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            throw new Error('Réponse non-JSON du serveur (HTTP ' + resp.status + ')');
        }
        const data = await resp.json();

        if (spinnerEl) spinnerEl.style.display = 'none';

        if (data.success) {
            if (statusEl) statusEl.innerHTML = '✅ Trajectoires IA chargées.';
            _drawMicroPlot(data, plotEl, metaEl);
        } else {
            if (statusEl) statusEl.innerHTML = '';
            if (errorEl) {
                errorEl.innerHTML = `
                    <div style="background:#fef9e7;border:1px solid #f9e79f;border-radius:8px;padding:16px;text-align:center;color:#7d6608;">
                        <strong>📌 Analyse IA non disponible pour ce TD</strong><br>
                        <span style="font-size:0.85rem;">Veuillez générer l'analyse IA depuis la page de la ressource (vue globale).</span><br>
                        <span style="font-size:0.8rem;color:#999;">${esc(data.message || data.error || '')}</span>
                    </div>
                `;
                errorEl.style.display = 'block';
                errorEl.style.color = 'inherit';
                errorEl.style.background = 'none';
                errorEl.style.border = 'none';
            }
        }
    } catch (err) {
        if (spinnerEl) spinnerEl.style.display = 'none';
        if (errorEl) {
            errorEl.textContent = '❌ Erreur réseau : ' + err.message;
            errorEl.style.display = 'block';
        }
        console.error('[iaIntegration] micro generation error:', err);
    }
}

/**
 * Dessine le graphe Micro (clusters + trajectoires + hover focus) avec Plotly.
 */
function _drawMicroPlot(data, plotEl, metaEl) {
    if (!plotEl) return;
    plotEl.style.display = 'block';

    const points = data.points || [];
    if (points.length === 0) return;

    const nClusters = data.n_clusters || 8;
    const traces = [];

    const DIM_OPACITY = 0.15;

    // 1) Points colorés par cluster
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
                size: clusterPts.map(p => p.correct ? 14 : 8),
                color: col,
                opacity: DIM_OPACITY,
                line: {
                    width: clusterPts.map(p => p.correct ? 2.5 : 1),
                    color: clusterPts.map(p => p.correct ? '#FFD700' : '#fff'),
                },
                symbol: clusterPts.map(p => p.correct ? 'star' : 'circle'),
            },
            hoverinfo: 'text',
            text: clusterPts.map(p => {
                const dateStr = p.date && p.date !== 'None' ? formatDate(p.date) : '—';
                return `<b>👤 Étudiant :</b> ${esc(p.user_id)}<br>` +
                       `<b>🎯 Cluster :</b> ${p.cluster}<br>` +
                       `<b>${p.correct ? '✅' : '❌'} Correct :</b> ${p.correct ? '<span style="color:#2ecc71;font-weight:bold">Oui</span>' : '<span style="color:#e74c3c">Non</span>'}<br>` +
                       `<b>📝 Tentative :</b> #${p.attempt_id}<br>` +
                       `<b>📅 Date :</b> ${dateStr}`;
            }),
            customdata: clusterPts.map(p => p.user_id),
            hoverlabel: {
                bgcolor: '#2c3e50', bordercolor: '#ecf0f1',
                font: { color: '#fff', size: 12, family: 'sans-serif' },
            },
        });
    }

    const nClusterTraces = traces.length;

    // 2) Trajectoires par étudiant (lignes)
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

    const trajectoryAnnotations = [];
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
            const fromPt = userPts[i];
            const toPt = userPts[i + 1];
            const dx = toPt.x - fromPt.x;
            const dy = toPt.y - fromPt.y;
            if (Math.sqrt(dx * dx + dy * dy) < 0.3) continue;

            trajectoryAnnotations.push({
                x: toPt.x, y: toPt.y,
                ax: fromPt.x, ay: fromPt.y,
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
            text: `Cartographie des trajectoires — ${exName}<br><sup>${data.n_points} tentatives, ${nClusters} clusters · Survolez pour isoler une trajectoire · ★ = réussite</sup>`,
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
        annotations: trajectoryAnnotations.length > 300 ? trajectoryAnnotations.slice(0, 300) : trajectoryAnnotations,
    };

    Plotly.newPlot(plotEl, traces, layout, { responsive: true }).then(() => {
        // Hover focus : surbrillance trajectoire
        plotEl.on('plotly_hover', function (evtData) {
            if (!evtData || !evtData.points || !evtData.points.length) return;
            const hoveredUserId = evtData.points[0].customdata;
            if (!hoveredUserId) return;
            _highlightUser(plotEl, traces, nClusterTraces, hoveredUserId, trajectoryAnnotations);
        });

        plotEl.on('plotly_unhover', function () {
            _resetHighlight(plotEl, traces, nClusterTraces, trajectoryAnnotations);
        });
    });

    // Métadonnées
    if (metaEl) {
        const uniqueStudents = new Set(points.map(p => p.user_id)).size;
        const correctCount = points.filter(p => p.correct).length;
        metaEl.style.display = 'block';
        metaEl.innerHTML =
            `<span style="margin-right:1rem;"><strong>${data.n_points}</strong> tentatives</span>` +
            `<span style="margin-right:1rem;"><strong>${nClusters}</strong> clusters</span>` +
            `<span style="margin-right:1rem;"><strong>${uniqueStudents}</strong> étudiants</span>` +
            `<span>✅ <strong>${correctCount}</strong> réussies (★)</span>`;
    }
}

/**
 * Met en surbrillance la trajectoire d'un utilisateur.
 */
function _highlightUser(container, traces, nClusterTraces, userId, annotations) {
    const DIM = 0.08;
    const BRIGHT = 1.0;

    for (let i = 0; i < nClusterTraces; i++) {
        const trace = traces[i];
        if (!trace.customdata) continue;
        const opacities = trace.customdata.map(uid => uid === userId ? BRIGHT : DIM);
        const sizes = [];
        if (trace.marker && trace.marker.symbol) {
            for (let j = 0; j < trace.customdata.length; j++) {
                const isHovered = trace.customdata[j] === userId;
                const isStar = trace.marker.symbol[j] === 'star';
                sizes.push(isHovered ? (isStar ? 18 : 11) : (isStar ? 14 : 8));
            }
        }
        const update = { 'marker.opacity': opacities };
        if (sizes.length) update['marker.size'] = sizes;
        Plotly.restyle(container, update, [i]);
    }

    for (let i = nClusterTraces; i < traces.length; i++) {
        const trace = traces[i];
        const isHl = trace._userId === userId;
        Plotly.restyle(container, {
            'line.color': isHl ? trace._colTemplate.replace('{a}', '0.9') : trace._colTemplate.replace('{a}', String(DIM)),
            'line.width': isHl ? 3 : 0.5,
            'line.dash': isHl ? 'solid' : 'dot',
        }, [i]);
    }

    if (annotations.length > 0) {
        const updated = annotations.map(ann => ({
            ...ann,
            arrowcolor: ann._colTemplate.replace('{a}', ann._userId === userId ? '0.9' : String(DIM)),
            arrowwidth: ann._userId === userId ? 2.5 : 0.8,
            opacity: ann._userId === userId ? 1.0 : DIM,
        }));
        Plotly.relayout(container, { annotations: updated.slice(0, 300) });
    }
}

/**
 * Réinitialise toutes les opacités.
 */
function _resetHighlight(container, traces, nClusterTraces, annotations) {
    const DIM = 0.15;

    for (let i = 0; i < nClusterTraces; i++) {
        const trace = traces[i];
        if (!trace.customdata) continue;
        const opacities = trace.customdata.map(() => DIM);
        const sizes = [];
        if (trace.marker && trace.marker.symbol) {
            for (let j = 0; j < trace.customdata.length; j++) {
                sizes.push(trace.marker.symbol[j] === 'star' ? 14 : 8);
            }
        }
        const update = { 'marker.opacity': opacities };
        if (sizes.length) update['marker.size'] = sizes;
        Plotly.restyle(container, update, [i]);
    }

    for (let i = nClusterTraces; i < traces.length; i++) {
        const trace = traces[i];
        Plotly.restyle(container, {
            'line.color': trace._colTemplate.replace('{a}', String(DIM)),
            'line.width': 1,
            'line.dash': 'dot',
        }, [i]);
    }

    if (annotations.length > 0) {
        const reset = annotations.map(ann => ({
            ...ann,
            arrowcolor: ann._colTemplate.replace('{a}', String(DIM)),
            arrowwidth: 1.5,
            opacity: DIM,
        }));
        Plotly.relayout(container, { annotations: reset.slice(0, 300) });
    }
}

