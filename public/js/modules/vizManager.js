/**
 * VizManager - Module de visualisation à 3 niveaux
 *
 * Niveau 1 : Vue globale d'une ressource (3 graphiques : élèves, TP, réussite/échec)
 * Niveau 2A : Vue d'un étudiant (radar + stats A/B/C + évolution)
 * Niveau 2B : Vue d'un TP (lignes par étudiant + stacked bar classe)
 * Niveau 3  : Statistiques détaillées d'un étudiant (sans onglet visualisation)
 */

import { StatsRenderer } from '/public/js/modules/statsRenderer.js';
import { AttemptsRenderer } from '/public/js/modules/attemptsRenderer.js';

export class VizManager {
    constructor() {
        this.resourceId = window.RESOURCE_ID || null;
        this.statsRenderer = new StatsRenderer();
        this.attemptsRenderer = new AttemptsRenderer();
        this._navStack = []; // historique de navigation
    }

    /** Couleur selon taux de réussite (vert/jaune/rouge) */
    static gradeColor(rate) {
        if (rate >= 70) return '#27ae60';
        if (rate >= 40) return '#f39c12';
        return '#e74c3c';
    }

    /** Libellé de niveau A/B/C selon taux de réussite */
    static gradeLabel(rate) {
        if (rate >= 70) return 'A';
        if (rate >= 40) return 'B';
        return 'C';
    }

    // =========================================================================
    // NIVEAU 1 — Vue globale ressource
    // =========================================================================

    async renderLevel1(container) {
        container.innerHTML = '<div class="viz-loading">⏳ Chargement des données…</div>';
        this._navStack = [];
        this._renderBreadcrumb(container, []);

        try {
            const [statsResp, exResp] = await Promise.all([
                fetch(`${window.BASE_URL}/api/dashboard/students-stats?resource_id=${this.resourceId}`).then(r => r.json()),
                fetch(`${window.BASE_URL}/api/dashboard/exercises?resource_id=${this.resourceId}`).then(r => r.json()),
            ]);

            const studentsData = statsResp.success ? statsResp.data : [];
            const exercisesData = exResp.success ? (exResp.data.exercises || []) : [];

            // Calcul des données globales réussite/échec
            const totalAttempts = studentsData.reduce((s, d) => s + (d.total_attempts || 0), 0);
            const totalCorrect  = studentsData.reduce((s, d) => s + (d.correct_attempts || 0), 0);

            container.innerHTML = '';
            this._renderBreadcrumb(container, [{ label: 'Vue globale' }]);

            const title = document.createElement('h2');
            title.className = 'viz-title';
            title.textContent = 'Vue d\'ensemble de la ressource';
            container.appendChild(title);

            // Hint interactif
            const hint = document.createElement('p');
            hint.className = 'viz-hint';
            hint.textContent = '💡 Cliquez sur un élément d\'un graphique pour explorer en détail.';
            container.appendChild(hint);

            // Grille 2 colonnes du haut
            const topGrid = document.createElement('div');
            topGrid.className = 'viz-top-grid';
            container.appendChild(topGrid);

            // Graphique 1 : élèves (barres colorées par taux de réussite)
            const chartStudents = document.createElement('div');
            chartStudents.id = 'viz-chart-students';
            chartStudents.className = 'viz-chart-card';
            topGrid.appendChild(chartStudents);

            // Graphique 2 : TP (barres colorées par taux de réussite)
            const chartTP = document.createElement('div');
            chartTP.id = 'viz-chart-tp';
            chartTP.className = 'viz-chart-card';
            topGrid.appendChild(chartTP);

            // Graphique 3 : réussite globale (camembert)
            const chartGlobal = document.createElement('div');
            chartGlobal.id = 'viz-chart-global';
            chartGlobal.className = 'viz-chart-card viz-chart-full';
            container.appendChild(chartGlobal);

            // Rendu des graphiques
            this._renderStudentsBarChart(studentsData, 'viz-chart-students');
            this._renderTPBarChart(exercisesData, 'viz-chart-tp');
            this._renderGlobalPieChart(totalCorrect, totalAttempts - totalCorrect, 'viz-chart-global');

        } catch (err) {
            console.error('[VizManager] renderLevel1 error:', err);
            container.innerHTML = '<p class="placeholder-message">Erreur lors du chargement des données.</p>';
        }
    }

    // =========================================================================
    // NIVEAU 2A — Vue d'un étudiant
    // =========================================================================

    async renderLevel2Student(container, studentId) {
        container.innerHTML = '<div class="viz-loading">⏳ Chargement…</div>';

        try {
            let url = `${window.BASE_URL}/api/dashboard/student/${encodeURIComponent(studentId)}`;
            if (this.resourceId) url += `?resource_id=${this.resourceId}`;
            const resp = await fetch(url).then(r => r.json());
            if (!resp.success) throw new Error('Données indisponibles');

            const { student, attempts, stats } = resp.data;
            const rate = stats.success_rate || 0;
            const grade = VizManager.gradeLabel(rate);
            const color = VizManager.gradeColor(rate);

            container.innerHTML = '';
            this._renderBreadcrumb(container, [
                { label: 'Vue globale', action: () => this.renderLevel1(container) },
                { label: `Étudiant : ${studentId}` },
            ]);

            const title = document.createElement('h2');
            title.className = 'viz-title';
            title.innerHTML = `<span style="color:${color}">●</span> Étudiant : ${htmlEscape(studentId)} <span class="grade-badge grade-${grade.toLowerCase()}">${grade}</span>`;
            container.appendChild(title);

            const hint = document.createElement('p');
            hint.className = 'viz-hint';
            hint.textContent = '💡 Cliquez sur un exercice du graphique pour voir le détail du TP.';
            container.appendChild(hint);

            // Grille : radar + bar chart
            const grid = document.createElement('div');
            grid.className = 'viz-top-grid';
            container.appendChild(grid);

            const radarCard = document.createElement('div');
            radarCard.id = 'viz-student-radar';
            radarCard.className = 'viz-chart-card';
            grid.appendChild(radarCard);

            const barCard = document.createElement('div');
            barCard.id = 'viz-student-bar';
            barCard.className = 'viz-chart-card';
            grid.appendChild(barCard);

            // Statistiques A/B/C
            const statsCard = this._renderStudentStatsCard(stats, attempts);
            container.appendChild(statsCard);

            // Rendu graphiques
            this._renderStudentRadar(stats, attempts, 'viz-student-radar', grade, color);
            this._renderStudentExerciseBar(attempts, 'viz-student-bar', container);

        } catch (err) {
            console.error('[VizManager] renderLevel2Student:', err);
            container.innerHTML = '<p class="placeholder-message">Erreur lors du chargement.</p>';
        }
    }

    // =========================================================================
    // NIVEAU 2B — Vue d'un TP
    // =========================================================================

    async renderLevel2TP(container, exerciseId, exerciseName) {
        container.innerHTML = '<div class="viz-loading">⏳ Chargement…</div>';

        try {
            let url = `${window.BASE_URL}/api/dashboard/exercises?exercise_id=${exerciseId}`;
            if (this.resourceId) url += `&resource_id=${this.resourceId}`;
            const resp = await fetch(url).then(r => r.json());
            if (!resp.success) throw new Error('Données indisponibles');

            const { exercise, students } = resp.data;
            const displayName = exercise.funcname || exercise.exo_name || exerciseName || 'TP';

            container.innerHTML = '';
            this._renderBreadcrumb(container, [
                { label: 'Vue globale', action: () => this.renderLevel1(container) },
                { label: `TP : ${displayName}` },
            ]);

            const title = document.createElement('h2');
            title.className = 'viz-title';
            title.textContent = `TP : ${displayName}`;
            container.appendChild(title);

            const hint = document.createElement('p');
            hint.className = 'viz-hint';
            hint.textContent = '💡 Cliquez sur un étudiant dans le graphique pour voir son profil détaillé.';
            container.appendChild(hint);

            // Grille : lignes par étudiant + stacked bar
            const grid = document.createElement('div');
            grid.className = 'viz-top-grid';
            container.appendChild(grid);

            const linesCard = document.createElement('div');
            linesCard.id = 'viz-tp-lines';
            linesCard.className = 'viz-chart-card';
            grid.appendChild(linesCard);

            const stackedCard = document.createElement('div');
            stackedCard.id = 'viz-tp-stacked';
            stackedCard.className = 'viz-chart-card';
            grid.appendChild(stackedCard);

            this._renderTPStudentLines(students, 'viz-tp-lines', container, { id: exerciseId, name: displayName });
            this._renderTPStackedBar(students, 'viz-tp-stacked', container, { id: exerciseId, name: displayName });

        } catch (err) {
            console.error('[VizManager] renderLevel2TP:', err);
            container.innerHTML = '<p class="placeholder-message">Erreur lors du chargement.</p>';
        }
    }

    // =========================================================================
    // NIVEAU 3 — Détail statistiques d'un étudiant
    // =========================================================================

    async renderLevel3Student(container, studentId, fromTP = false, tpData = null) {
        container.innerHTML = '<div class="viz-loading">⏳ Chargement…</div>';

        try {
            let url = `${window.BASE_URL}/api/dashboard/student/${encodeURIComponent(studentId)}`;
            if (this.resourceId) url += `?resource_id=${this.resourceId}`;
            const resp = await fetch(url).then(r => r.json());
            if (!resp.success) throw new Error('Données indisponibles');

            const { student, attempts, stats } = resp.data;

            container.innerHTML = '';

            // Breadcrumb avec contexte
            if (fromTP && tpData) {
                this._renderBreadcrumb(container, [
                    { label: 'Vue globale', action: () => this.renderLevel1(container) },
                    { label: `TP : ${tpData.name}`, action: () => this.renderLevel2TP(container, tpData.id, tpData.name) },
                    { label: `Étudiant : ${studentId}` },
                ]);
            } else {
                this._renderBreadcrumb(container, [
                    { label: 'Vue globale', action: () => this.renderLevel1(container) },
                    { label: `Étudiant : ${studentId}`, action: () => this.renderLevel2Student(container, studentId) },
                    { label: 'Statistiques détaillées' },
                ]);
            }

            const rate = stats.success_rate || 0;
            const grade = VizManager.gradeLabel(rate);
            const color = VizManager.gradeColor(rate);

            const title = document.createElement('h2');
            title.className = 'viz-title';
            title.innerHTML = `Statistiques : ${htmlEscape(studentId)} <span class="grade-badge grade-${grade.toLowerCase()}">${grade}</span>`;
            container.appendChild(title);

            // Cartes de stats
            const statsCard = this._renderStudentStatsCard(stats, attempts);
            container.appendChild(statsCard);

            // Tableau des tentatives
            const { title: attTitle, container: attContainer } = this.attemptsRenderer.renderAttempts(attempts);
            container.appendChild(attTitle);
            container.appendChild(attContainer);

        } catch (err) {
            console.error('[VizManager] renderLevel3Student:', err);
            container.innerHTML = '<p class="placeholder-message">Erreur lors du chargement.</p>';
        }
    }

    // =========================================================================
    // Breadcrumb
    // =========================================================================

    _renderBreadcrumb(container, crumbs) {
        let bc = container.querySelector('.viz-breadcrumb');
        if (bc) bc.remove();

        if (!crumbs || crumbs.length === 0) return;

        bc = document.createElement('nav');
        bc.className = 'viz-breadcrumb';
        crumbs.forEach((crumb, i) => {
            if (i > 0) {
                const sep = document.createElement('span');
                sep.className = 'viz-bc-sep';
                sep.textContent = ' › ';
                bc.appendChild(sep);
            }
            if (crumb.action) {
                const btn = document.createElement('button');
                btn.className = 'viz-bc-btn';
                btn.textContent = crumb.label;
                btn.addEventListener('click', crumb.action);
                bc.appendChild(btn);
            } else {
                const span = document.createElement('span');
                span.className = 'viz-bc-current';
                span.textContent = crumb.label;
                bc.appendChild(span);
            }
        });

        container.insertBefore(bc, container.firstChild);
    }

    // =========================================================================
    // Helper : tri des données
    // =========================================================================

    _sortData(data, order, nameKey) {
        const copy = [...data];
        switch (order) {
            case 'asc':
                return copy.sort((a, b) => (a.success_rate || 0) - (b.success_rate || 0));
            case 'alpha':
                return copy.sort((a, b) => {
                    const na = (a[nameKey] || a.identifier || '').toString().toLowerCase();
                    const nb = (b[nameKey] || b.identifier || '').toString().toLowerCase();
                    return na.localeCompare(nb, 'fr');
                });
            case 'alpha-desc':
                return copy.sort((a, b) => {
                    const na = (a[nameKey] || a.identifier || '').toString().toLowerCase();
                    const nb = (b[nameKey] || b.identifier || '').toString().toLowerCase();
                    return nb.localeCompare(na, 'fr');
                });
            case 'desc':
            default:
                return copy.sort((a, b) => (b.success_rate || 0) - (a.success_rate || 0));
        }
    }

    // =========================================================================
    // Graphique 1 : Barres élèves (niveau 1)
    // =========================================================================

    _renderStudentsBarChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';

        const header = document.createElement('div');
        header.className = 'viz-chart-header';
        header.innerHTML = `<h3 class="viz-chart-title" style="margin:0;">Visualisation élèves</h3>`;

        const select = document.createElement('select');
        select.className = 'viz-sort-select';
        select.title = 'Trier les élèves';
        select.innerHTML = `
            <option value="desc">↓ Taux décroissant</option>
            <option value="asc">↑ Taux croissant</option>
            <option value="alpha">A→Z Alphabétique</option>
            <option value="alpha-desc">Z→A Alphabétique inversé</option>
        `;
        header.appendChild(select);
        container.appendChild(header);

        if (!data || data.length === 0) {
            const p = document.createElement('p');
            p.className = 'viz-no-data';
            p.textContent = 'Aucune donnée élève.';
            container.appendChild(p);
            return;
        }

        const self = this;
        const drawStudentsBars = (order) => {
            const oldSvg = container.querySelector('svg');
            if (oldSvg) oldSvg.remove();

            const sorted = self._sortData(data, order, 'identifier');
            const margin = { top: 10, right: 100, bottom: 20, left: 50 };
            const vw = 500, vh = 300;
            const w = vw - margin.left - margin.right;
            const h = vh - margin.top - margin.bottom;

            const svg = d3.select(`#${containerId}`).append('svg')
                .attr('viewBox', `0 0 ${vw} ${vh}`)
                .style('width', '100%')
                .append('g')
                .attr('transform', `translate(${margin.left},${margin.top})`);

            const x = d3.scaleBand().range([0, w]).domain(sorted.map((_, i) => i)).padding(0.2);
            const y = d3.scaleLinear().domain([0, 100]).range([h, 0]);
            const tooltip = self._createTooltip(containerId);

            svg.selectAll('rect.bar').data(sorted).enter().append('rect')
                .attr('class', 'bar')
                .attr('x', (_, i) => x(i))
                .attr('y', d => y(d.success_rate || 0))
                .attr('width', x.bandwidth())
                .attr('height', d => h - y(d.success_rate || 0))
                .attr('fill', d => VizManager.gradeColor(d.success_rate || 0))
                .attr('rx', 2).style('cursor', 'pointer')
                .on('mouseover', function(event, d) {
                    d3.select(this).attr('opacity', 0.75);
                    const rate = d.success_rate || 0;
                    const rateColor = VizManager.gradeColor(rate);
                    const name = htmlEscape(d.identifier || d.student_id);
                    tooltip.style('visibility', 'visible').style('border-left', `3px solid ${rateColor}`)
                        .html(`<div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#fff">${name}</div><div style="color:${rateColor};font-weight:600">Réussite : ${rate}%</div><div style="color:#ccc;font-size:11px;margin-top:3px">🎯 ${d.total_attempts} tentative${d.total_attempts > 1 ? 's' : ''}</div><div style="color:#aaa;font-size:10px;margin-top:4px">Cliquer pour explorer →</div>`);
                })
                .on('mousemove', event => tooltip.style('top', (event.pageY - 40) + 'px').style('left', (event.pageX + 12) + 'px'))
                .on('mouseout', function() { d3.select(this).attr('opacity', 1); tooltip.style('visibility', 'hidden'); })
                .on('click', (event, d) => {
                    tooltip.style('visibility', 'hidden');
                    const dataZone = document.querySelector('.viz-data-zone');
                    if (dataZone) self.renderLevel2Student(dataZone, d.identifier || d.student_id);
                });

            svg.append('g').attr('transform', `translate(0,${h})`).call(d3.axisBottom(x).tickFormat(() => '')).selectAll('text').remove();
            svg.append('g').call(d3.axisLeft(y).tickFormat(d => d + '%'));
            self._renderGradeLegend(svg, w + 5, 0);
        };

        drawStudentsBars('desc');
        select.addEventListener('change', () => drawStudentsBars(select.value));
    }

    // =========================================================================
    // Graphique 2 : Barres TP (niveau 1)
    // =========================================================================

    _renderTPBarChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';

        const header = document.createElement('div');
        header.className = 'viz-chart-header';
        header.innerHTML = `<h3 class="viz-chart-title" style="margin:0;">Visualisation TP</h3>`;

        const select = document.createElement('select');
        select.className = 'viz-sort-select';
        select.title = 'Trier les TP';
        select.innerHTML = `
            <option value="desc">↓ Taux décroissant</option>
            <option value="asc">↑ Taux croissant</option>
            <option value="alpha">A→Z Alphabétique</option>
            <option value="alpha-desc">Z→A Alphabétique inversé</option>
        `;
        header.appendChild(select);
        container.appendChild(header);

        if (!data || data.length === 0) {
            const p = document.createElement('p');
            p.className = 'viz-no-data';
            p.textContent = 'Aucun TP disponible.';
            container.appendChild(p);
            return;
        }

        const self = this;
        const drawTPBars = (order) => {
            const oldSvg = container.querySelector('svg');
            if (oldSvg) oldSvg.remove();

            const sorted = self._sortData(data, order, 'funcname');
            const margin = { top: 10, right: 100, bottom: 20, left: 50 };
            const vw = 500, vh = 300;
            const w = vw - margin.left - margin.right;
            const h = vh - margin.top - margin.bottom;

            const svg = d3.select(`#${containerId}`).append('svg')
                .attr('viewBox', `0 0 ${vw} ${vh}`)
                .style('width', '100%')
                .append('g')
                .attr('transform', `translate(${margin.left},${margin.top})`);

            const x = d3.scaleBand().range([0, w]).domain(sorted.map((_, i) => i)).padding(0.2);
            const y = d3.scaleLinear().domain([0, 100]).range([h, 0]);
            const tooltip = self._createTooltip(containerId);

            svg.selectAll('rect.bar').data(sorted).enter().append('rect')
                .attr('class', 'bar')
                .attr('x', (_, i) => x(i))
                .attr('y', d => y(d.success_rate != null ? d.success_rate : 0))
                .attr('width', x.bandwidth())
                .attr('height', d => h - y(d.success_rate != null ? d.success_rate : 0))
                .attr('fill', d => d.success_rate != null ? VizManager.gradeColor(d.success_rate) : '#95a5a6')
                .attr('rx', 2).style('cursor', 'pointer')
                .on('mouseover', function(event, d) {
                    d3.select(this).attr('opacity', 0.75);
                    const rate = d.success_rate != null ? d.success_rate : null;
                    const rateColor = rate != null ? VizManager.gradeColor(rate) : '#95a5a6';
                    const name = htmlEscape(d.funcname || d.exo_name);
                    const rateStr = rate != null ? rate + '%' : 'N/A';
                    tooltip.style('visibility', 'visible').style('border-left', `3px solid ${rateColor}`)
                        .html(`<div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#fff">${name}</div><div style="color:${rateColor};font-weight:600">Réussite : ${rateStr}</div><div style="color:#ccc;font-size:11px;margin-top:3px">🎯 ${d.total_attempts} tentative${d.total_attempts > 1 ? 's' : ''}</div><div style="color:#aaa;font-size:10px;margin-top:4px">Cliquer pour explorer →</div>`);
                })
                .on('mousemove', event => tooltip.style('top', (event.pageY - 40) + 'px').style('left', (event.pageX + 12) + 'px'))
                .on('mouseout', function() { d3.select(this).attr('opacity', 1); tooltip.style('visibility', 'hidden'); })
                .on('click', (event, d) => {
                    tooltip.style('visibility', 'hidden');
                    const dataZone = document.querySelector('.viz-data-zone');
                    if (dataZone) self.renderLevel2TP(dataZone, d.exercise_id, d.funcname || d.exo_name);
                });

            svg.append('g').attr('transform', `translate(0,${h})`).call(d3.axisBottom(x).tickFormat(() => '')).selectAll('text').remove();
            svg.append('g').call(d3.axisLeft(y).tickFormat(d => d + '%'));
            self._renderGradeLegend(svg, w + 5, 0);
        };

        drawTPBars('desc');
        select.addEventListener('change', () => drawTPBars(select.value));
    }

    // =========================================================================
    // Graphique 3 : Camembert réussite globale (niveau 1)
    // =========================================================================

    _renderGlobalPieChart(correct, failed, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<h3 class="viz-chart-title">Réussite globale des étudiants</h3>';

        const total = correct + failed;
        if (total === 0) {
            container.innerHTML += '<p class="viz-no-data">Aucune tentative enregistrée.</p>';
            return;
        }

        const data = [
            { label: 'Réussies', value: correct, color: '#27ae60' },
            { label: 'Échouées', value: failed, color: '#e74c3c' },
        ];

        const size = 280;
        const radius = size / 2 - 20;

        const svg = d3.select(`#${containerId}`).append('svg')
            .attr('viewBox', `0 0 ${size} ${size}`)
            .style('width', '100%')
            .style('max-width', '320px')
            .style('display', 'block')
            .style('margin', '0 auto')
            .append('g')
            .attr('transform', `translate(${size / 2},${size / 2})`);

        const pie = d3.pie().value(d => d.value)(data);
        const arc        = d3.arc().innerRadius(radius * 0.5).outerRadius(radius);
        const arcHover   = d3.arc().innerRadius(radius * 0.5).outerRadius(radius * 1.12);
        const arcShrink  = d3.arc().innerRadius(radius * 0.5).outerRadius(radius * 0.90);

        const tooltip = this._createTooltip();

        const paths = svg.selectAll('path')
            .data(pie)
            .enter()
            .append('path')
            .attr('d', arc)
            .attr('fill', d => d.data.color)
            .attr('stroke', '#fff')
            .attr('stroke-width', 2)
            .style('cursor', 'pointer')
            .on('mouseover', function(event, d) {
                // Agrandir la part survolée, rétrécir les autres
                paths.transition().duration(200).attr('d', function(pd) {
                    return pd === d ? arcHover(pd) : arcShrink(pd);
                });
                const pct = ((d.data.value / total) * 100).toFixed(1);
                tooltip
                    .style('visibility', 'visible')
                    .style('border-left', `3px solid ${d.data.color}`)
                    .html(`<strong style="color:${d.data.color}">${d.data.label}</strong><br>${d.data.value} tentatives<br>${pct}%`);
            })
            .on('mousemove', event => tooltip.style('top', (event.pageY - 40) + 'px').style('left', (event.pageX + 12) + 'px'))
            .on('mouseout', function() {
                // Rétablir toutes les parts à la taille normale
                paths.transition().duration(200).attr('d', arc);
                tooltip.style('visibility', 'hidden');
            });

        // Labels dans le camembert
        svg.selectAll('text.arc-label')
            .data(pie)
            .enter()
            .append('text')
            .attr('class', 'arc-label')
            .attr('transform', d => `translate(${arc.centroid(d)})`)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#fff')
            .style('font-weight', 'bold')
            .text(d => {
                const pct = ((d.data.value / total) * 100).toFixed(1);
                return parseFloat(pct) > 5 ? `${pct}%` : '';
            });

        // Légende
        const legend = d3.select(`#${containerId}`).append('div').style('text-align', 'center').style('margin-top', '0.5rem');
        data.forEach(d => {
            const item = legend.append('span').style('display', 'inline-flex').style('align-items', 'center').style('gap', '4px').style('margin', '0 10px').style('font-size', '0.85rem');
            item.append('span').style('display', 'inline-block').style('width', '12px').style('height', '12px').style('background', d.color).style('border-radius', '2px');
            item.append('span').text(`${d.label} (${d.value})`);
        });

        // Texte central
        const pctSuccess = ((correct / total) * 100).toFixed(1);
        svg.append('text').attr('text-anchor', 'middle').attr('dy', '-0.2em').style('font-size', '18px').style('font-weight', 'bold').style('fill', '#2c3e50').text(`${pctSuccess}%`);
        svg.append('text').attr('text-anchor', 'middle').attr('dy', '1.2em').style('font-size', '10px').style('fill', '#7f8c8d').text('de réussite');
    }

    // =========================================================================
    // Niveau 2A : Radar chart étudiant
    // =========================================================================

    _renderStudentRadar(stats, attempts, containerId, grade, color) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<h3 class="viz-chart-title">Profil étudiant</h3>';

        const rate = stats.success_rate || 0;

        // Calculer les métriques
        const totalAttempts = stats.total_attempts || 0;
        const correctAttempts = stats.correct_attempts || 0;
        const uniqueExos = new Set(attempts.map(a => a.exercice_id)).size;
        const totalExos = Math.max(uniqueExos, 1);
        const exoReussis = new Set(attempts.filter(a => a.correct).map(a => a.exercice_id)).size;

        // Régularité par semaine (approximation)
        const weeks = new Set(attempts.map(a => {
            const d = new Date(a.timestamp || a.date_tentative || Date.now());
            const jan1 = new Date(d.getFullYear(), 0, 1);
            return Math.ceil((((d - jan1) / 86400000) + jan1.getDay() + 1) / 7);
        })).size;
        const regularite = Math.min(100, (weeks / Math.max(1, 12)) * 100);

        // Qualité code = moyenne aes0/aes1/aes2 si disponible
        const withAes = attempts.filter(a => a.aes0 != null);
        const qualiteCode = withAes.length > 0
            ? (withAes.reduce((s, a) => s + (parseFloat(a.aes0) || 0), 0) / withAes.length * 100)
            : rate;

        const axes = [
            { label: 'Taux de réussite', value: rate },
            { label: 'Nb tentatives', value: Math.min(100, (totalAttempts / 50) * 100) },
            { label: 'Exos réussis', value: totalExos > 0 ? (exoReussis / totalExos) * 100 : 0 },
            { label: 'Exos faits', value: Math.min(100, (uniqueExos / 20) * 100) },
            { label: 'Qualité code', value: qualiteCode },
            { label: 'Régularité', value: regularite },
        ];

        const size = 260;
        const cx = size / 2, cy = size / 2;
        const maxR = size / 2 - 30;
        const n = axes.length;
        const angleSlice = (Math.PI * 2) / n;

        const svg = d3.select(`#${containerId}`).append('svg')
            .attr('viewBox', `0 0 ${size} ${size}`)
            .style('width', '100%')
            .style('max-width', '320px')
            .style('display', 'block')
            .style('margin', '0 auto');

        const g = svg.append('g').attr('transform', `translate(${cx},${cy})`);

        // Grilles circulaires
        [0.25, 0.5, 0.75, 1].forEach(level => {
            const pts = axes.map((_, i) => {
                const a = angleSlice * i - Math.PI / 2;
                return [Math.cos(a) * maxR * level, Math.sin(a) * maxR * level];
            });
            g.append('polygon')
                .attr('points', pts.map(p => p.join(',')).join(' '))
                .attr('fill', 'none')
                .attr('stroke', '#ddd')
                .attr('stroke-width', 1);
        });

        // Axes radiaux
        axes.forEach((_, i) => {
            const a = angleSlice * i - Math.PI / 2;
            g.append('line')
                .attr('x1', 0).attr('y1', 0)
                .attr('x2', Math.cos(a) * maxR)
                .attr('y2', Math.sin(a) * maxR)
                .attr('stroke', '#ddd')
                .attr('stroke-width', 1);
        });

        // Polygone de données
        const pts = axes.map((ax, i) => {
            const a = angleSlice * i - Math.PI / 2;
            const r = maxR * (ax.value / 100);
            return [Math.cos(a) * r, Math.sin(a) * r];
        });

        g.append('polygon')
            .attr('points', pts.map(p => p.join(',')).join(' '))
            .attr('fill', color)
            .attr('fill-opacity', 0.3)
            .attr('stroke', color)
            .attr('stroke-width', 2);

        // Labels
        axes.forEach((ax, i) => {
            const a = angleSlice * i - Math.PI / 2;
            const lx = Math.cos(a) * (maxR + 18);
            const ly = Math.sin(a) * (maxR + 18);
            g.append('text')
                .attr('x', lx).attr('y', ly)
                .attr('text-anchor', 'middle')
                .attr('dy', '0.35em')
                .style('font-size', '9px')
                .style('fill', '#555')
                .text(ax.label);
        });

        // Grade badge central
        g.append('text').attr('text-anchor', 'middle').attr('dy', '0.35em')
            .style('font-size', '22px').style('font-weight', 'bold').style('fill', color)
            .text(grade);
    }

    // =========================================================================
    // Niveau 2A : Bar chart exercices d'un étudiant
    // =========================================================================

    _renderStudentExerciseBar(attempts, containerId, container) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = '<h3 class="viz-chart-title">Réussite par exercice</h3>';

        if (!attempts || attempts.length === 0) {
            el.innerHTML += '<p class="viz-no-data">Aucune tentative.</p>';
            return;
        }

        // Agréger par exercice
        const byExo = {};
        attempts.forEach(a => {
            const key = a.exercice_id;
            if (!byExo[key]) byExo[key] = { name: a.exercice_name || `Exo #${key}`, id: key, total: 0, correct: 0 };
            byExo[key].total++;
            if (a.correct) byExo[key].correct++;
        });
        const data = Object.values(byExo).map(e => ({
            ...e,
            rate: e.total > 0 ? Math.round((e.correct / e.total) * 100) : 0
        })).sort((a, b) => b.rate - a.rate);

        const margin = { top: 10, right: 20, bottom: 20, left: 50 };
        const vw = 500, vh = 300;
        const w = vw - margin.left - margin.right;
        const h = vh - margin.top - margin.bottom;

        const svg = d3.select(`#${containerId}`).append('svg')
            .attr('viewBox', `0 0 ${vw} ${vh}`)
            .style('width', '100%')
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand().range([0, w]).domain(data.map((_, i) => i)).padding(0.2);
        const y = d3.scaleLinear().domain([0, 100]).range([h, 0]);

        const tooltip = this._createTooltip(containerId);
        const self = this;

        // Label SVG flottant au survol
        svg.selectAll('rect.bar')
            .data(data)
            .enter()
            .append('rect')
            .attr('class', 'bar')
            .attr('x', (_, i) => x(i))
            .attr('y', d => y(d.rate))
            .attr('width', x.bandwidth())
            .attr('height', d => h - y(d.rate))
            .attr('fill', d => VizManager.gradeColor(d.rate))
            .attr('rx', 2)
            .style('cursor', 'pointer')
            .on('mouseover', function(event, d) {
                d3.select(this).attr('opacity', 0.75);
                const rateColor = VizManager.gradeColor(d.rate);
                const name = htmlEscape(d.name);
                tooltip.style('visibility', 'visible').style('border-left', `3px solid ${rateColor}`)
                    .html(`
                        <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#fff">${name}</div>
                        <div style="color:${rateColor};font-weight:600">Réussite : ${d.rate}%</div>
                        <div style="color:#ccc;font-size:11px;margin-top:3px">🎯 ${d.correct}/${d.total} tentatives réussies</div>
                        <div style="color:#aaa;font-size:10px;margin-top:4px">Cliquer pour explorer →</div>
                    `);
            })
            .on('mousemove', event => tooltip.style('top', (event.pageY - 40) + 'px').style('left', (event.pageX + 12) + 'px'))
            .on('mouseout', function() {
                d3.select(this).attr('opacity', 1);
                tooltip.style('visibility', 'hidden');
            })
            .on('click', (event, d) => {
                tooltip.style('visibility', 'hidden');
                const dataZone = document.querySelector('.viz-data-zone');
                if (dataZone) self.renderLevel2TP(dataZone, d.id, d.name);
            });

        svg.append('g').attr('transform', `translate(0,${h})`).call(d3.axisBottom(x).tickFormat(() => ''))
            .selectAll('text').remove();

        svg.append('g').call(d3.axisLeft(y).tickFormat(d => d + '%'));
    }

    // =========================================================================
    // Niveau 2A : Carte statistiques A/B/C
    // =========================================================================

    _renderStudentStatsCard(stats, attempts) {
        const card = document.createElement('div');
        card.className = 'viz-stats-grid';

        const rate = stats.success_rate || 0;
        const grade = VizManager.gradeLabel(rate);
        const color = VizManager.gradeColor(rate);

        const uniqueExos = new Set(attempts.map(a => a.exercice_id)).size;
        const exoReussis = new Set(attempts.filter(a => a.correct).map(a => a.exercice_id)).size;

        const weeks = new Set(attempts.map(a => {
            const d = new Date(a.timestamp || a.date_tentative || Date.now());
            const jan1 = new Date(d.getFullYear(), 0, 1);
            return Math.ceil((((d - jan1) / 86400000) + jan1.getDay() + 1) / 7);
        })).size;

        const withAes = attempts.filter(a => a.aes0 != null);
        const qualiteCode = withAes.length > 0
            ? Math.round(withAes.reduce((s, a) => s + (parseFloat(a.aes0) || 0), 0) / withAes.length * 100)
            : null;

        const metrics = [
            { label: 'Niveau', value: grade, color, large: true },
            { label: 'Taux de réussite', value: rate + '%', color },
            { label: 'Nb de tentatives', value: stats.total_attempts || 0, color: '#3498db' },
            { label: 'Exos réalisés', value: uniqueExos, color: '#9b59b6' },
            { label: 'Exos réussis', value: exoReussis, color: '#27ae60' },
            { label: 'Régularité', value: weeks + ' sem.', color: '#f39c12' },
            qualiteCode !== null ? { label: 'Qualité code', value: qualiteCode + '%', color: '#1abc9c' } : null,
        ].filter(Boolean);

        metrics.forEach(m => {
            const c = document.createElement('div');
            c.className = 'viz-stat-card';
            c.style.borderLeftColor = m.color;
            c.innerHTML = `
                <div class="viz-stat-value" style="color:${m.color}; font-size:${m.large ? '2.5rem' : '1.8rem'}">${m.value}</div>
                <div class="viz-stat-label">${m.label}</div>
            `;
            card.appendChild(c);
        });

        return card;
    }

    // =========================================================================
    // Niveau 2B : Lignes par étudiant pour un TP
    // =========================================================================

    _renderTPStudentLines(students, containerId, container, tpContext) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = '';

        // En-tête avec titre + menu déroulant de tri
        const header = document.createElement('div');
        header.className = 'viz-chart-header';
        header.innerHTML = `<h3 class="viz-chart-title" style="margin:0;">Performance par étudiant</h3>`;

        const select = document.createElement('select');
        select.className = 'viz-sort-select';
        select.title = 'Trier les étudiants';
        select.innerHTML = `
            <option value="desc">↓ Taux décroissant</option>
            <option value="asc">↑ Taux croissant</option>
            <option value="alpha">A→Z Alphabétique</option>
            <option value="alpha-desc">Z→A Alphabétique inversé</option>
        `;
        header.appendChild(select);
        el.appendChild(header);

        if (!students || students.length === 0) {
            const p = document.createElement('p');
            p.className = 'viz-no-data';
            p.textContent = 'Aucun étudiant.';
            el.appendChild(p);
            return;
        }

        const self = this;
        const drawTPLines = (order) => {
            const oldSvg = el.querySelector('svg');
            if (oldSvg) oldSvg.remove();

            const sorted = self._sortData(students, order, 'identifier');
            const margin = { top: 10, right: 100, bottom: 20, left: 50 };
            const vw = 500, vh = 300;
            const w = vw - margin.left - margin.right;
            const h = vh - margin.top - margin.bottom;

            const svg = d3.select(`#${containerId}`).append('svg')
                .attr('viewBox', `0 0 ${vw} ${vh}`)
                .style('width', '100%')
                .append('g')
                .attr('transform', `translate(${margin.left},${margin.top})`);

            const x = d3.scaleBand().range([0, w]).domain(sorted.map((_, i) => i)).padding(0.3);
            const y = d3.scaleLinear().domain([0, 100]).range([h, 0]);
            const tooltip = self._createTooltip(containerId);

            svg.selectAll('circle').data(sorted).enter().append('circle')
                .attr('cx', (_, i) => x(i) + x.bandwidth() / 2)
                .attr('cy', d => y(d.success_rate || 0))
                .attr('r', 6)
                .attr('fill', d => VizManager.gradeColor(d.success_rate || 0))
                .attr('stroke', '#fff')
                .attr('stroke-width', 2)
                .style('cursor', 'pointer')
                .on('mouseover', function(event, d) {
                    d3.select(this).attr('r', 9);
                    const rate = d.success_rate || 0;
                    const rateColor = VizManager.gradeColor(rate);
                    const name = htmlEscape(d.identifier);
                    tooltip.style('visibility', 'visible').style('border-left', `3px solid ${rateColor}`)
                        .html(`<div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#fff">${name}</div><div style="color:${rateColor};font-weight:600">Réussite : ${rate}%</div><div style="color:#ccc;font-size:11px;margin-top:3px">🎯 ${d.total_attempts} tentative${d.total_attempts > 1 ? 's' : ''}</div><div style="color:#aaa;font-size:10px;margin-top:4px">Cliquer pour explorer →</div>`);
                })
                .on('mousemove', event => tooltip.style('top', (event.pageY - 40) + 'px').style('left', (event.pageX + 12) + 'px'))
                .on('mouseout', function() { d3.select(this).attr('r', 6); tooltip.style('visibility', 'hidden'); })
                .on('click', (event, d) => {
                    tooltip.style('visibility', 'hidden');
                    const dataZone = document.querySelector('.viz-data-zone');
                    if (dataZone) self.renderLevel3Student(dataZone, d.identifier, true, tpContext);
                });

            svg.append('g').attr('transform', `translate(0,${h})`).call(d3.axisBottom(x).tickFormat(() => '')).selectAll('text').remove();
            svg.append('g').call(d3.axisLeft(y).tickFormat(d => d + '%'));
            self._renderGradeLegend(svg, w + 5, 0);
        };

        drawTPLines('desc');
        select.addEventListener('change', () => drawTPLines(select.value));
    }

    // =========================================================================
    // Niveau 2B : Stacked bar classe (meilleurs/pires)
    // =========================================================================

    _renderTPStackedBar(students, containerId, container, tpContext) {
        const el = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = '<h3 class="viz-chart-title">Distribution de la classe</h3>';

        if (!students || students.length === 0) {
            el.innerHTML += '<p class="viz-no-data">Aucun étudiant.</p>';
            return;
        }

        const green  = students.filter(s => (s.success_rate || 0) >= 70).length;
        const yellow = students.filter(s => (s.success_rate || 0) >= 40 && (s.success_rate || 0) < 70).length;
        const red    = students.filter(s => (s.success_rate || 0) < 40).length;
        const total  = students.length;

        const categories = [
            { label: 'Bons (≥70%)',   value: green,  color: '#27ae60' },
            { label: 'Moyens (40-70%)', value: yellow, color: '#f39c12' },
            { label: 'Faibles (<40%)', value: red,    color: '#e74c3c' },
        ];

        const size = 280;
        const radius = size / 2 - 20;

        const svg = d3.select(`#${containerId}`).append('svg')
            .attr('viewBox', `0 0 ${size} ${size}`)
            .style('width', '100%')
            .style('max-width', '320px')
            .style('display', 'block')
            .style('margin', '0 auto')
            .append('g')
            .attr('transform', `translate(${size / 2},${size / 2})`);

        const pie = d3.pie().value(d => d.value)(categories.filter(c => c.value > 0));
        const arc        = d3.arc().innerRadius(radius * 0.4).outerRadius(radius);
        const arcHover   = d3.arc().innerRadius(radius * 0.4).outerRadius(radius * 1.12);
        const arcShrink  = d3.arc().innerRadius(radius * 0.4).outerRadius(radius * 0.90);
        const tooltip = this._createTooltip();

        const paths2 = svg.selectAll('path')
            .data(pie)
            .enter()
            .append('path')
            .attr('d', arc)
            .attr('fill', d => d.data.color)
            .attr('stroke', '#fff')
            .attr('stroke-width', 2)
            .style('cursor', 'pointer')
            .on('mouseover', function(event, d) {
                paths2.transition().duration(200).attr('d', function(pd) {
                    return pd === d ? arcHover(pd) : arcShrink(pd);
                });
                tooltip
                    .style('visibility', 'visible')
                    .style('border-left', `3px solid ${d.data.color}`)
                    .html(`<strong style="color:${d.data.color}">${d.data.label}</strong><br>${d.data.value} étudiant(s)<br>${((d.data.value / total) * 100).toFixed(1)}%`);
            })
            .on('mousemove', event => tooltip.style('top', (event.pageY - 40) + 'px').style('left', (event.pageX + 12) + 'px'))
            .on('mouseout', function() {
                paths2.transition().duration(200).attr('d', arc);
                tooltip.style('visibility', 'hidden');
            });

        // Labels
        svg.selectAll('text.arc-label')
            .data(pie)
            .enter()
            .append('text')
            .attr('class', 'arc-label')
            .attr('transform', d => `translate(${arc.centroid(d)})`)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#fff')
            .style('font-weight', 'bold')
            .text(d => {
                const pct = ((d.data.value / total) * 100).toFixed(0);
                return pct > 5 ? pct + '%' : '';
            });

        // Total central
        svg.append('text').attr('text-anchor', 'middle').attr('dy', '0.35em')
            .style('font-size', '18px').style('font-weight', 'bold').style('fill', '#2c3e50')
            .text(total);
        svg.append('text').attr('text-anchor', 'middle').attr('dy', '1.5em')
            .style('font-size', '10px').style('fill', '#7f8c8d').text('étudiants');

        // Légende
        const legend = d3.select(`#${containerId}`).append('div').style('text-align', 'center').style('margin-top', '0.5rem');
        categories.forEach(c => {
            const item = legend.append('span').style('display', 'inline-flex').style('align-items', 'center').style('gap', '4px').style('margin', '0 8px').style('font-size', '0.8rem');
            item.append('span').style('display', 'inline-block').style('width', '10px').style('height', '10px').style('background', c.color).style('border-radius', '2px');
            item.append('span').text(`${c.label} (${c.value})`);
        });
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    _createTooltip(id = 'default') {
        const cls = `viz-tooltip-${id}`;
        d3.select(`.${cls}`).remove();
        return d3.select('body').append('div')
            .attr('class', `viz-tooltip ${cls}`)
            .style('position', 'absolute')
            .style('visibility', 'hidden')
            .style('background', 'rgba(15,15,15,0.93)')
            .style('color', '#fff')
            .style('padding', '10px 14px')
            .style('border-radius', '6px')
            .style('font-size', '12px')
            .style('font-family', 'system-ui, sans-serif')
            .style('pointer-events', 'none')
            .style('z-index', '9999')
            .style('min-width', '160px')
            .style('line-height', '1.5')
            .style('box-shadow', '0 4px 16px rgba(0,0,0,0.5)')
            .style('border-left', '3px solid #27ae60');
    }

    _renderGradeLegend(svg, x, y) {
        const items = [
            { color: '#27ae60', label: 'Fort', sub: '≥ 70%' },
            { color: '#f39c12', label: 'Moyen', sub: '40 – 70%' },
            { color: '#e74c3c', label: 'Faible', sub: '< 40%' },
        ];
        const g = svg.append('g').attr('transform', `translate(${x},${y})`);
        items.forEach((item, i) => {
            const row = g.append('g').attr('transform', `translate(0,${i * 26})`);
            row.append('rect').attr('width', 12).attr('height', 12).attr('fill', item.color).attr('rx', 2);
            row.append('text').attr('x', 17).attr('y', 10).style('font-size', '11px').style('font-weight', '600').style('fill', item.color).text(item.label);
            row.append('text').attr('x', 17).attr('y', 21).style('font-size', '9px').style('fill', '#888').text(item.sub);
        });
    }
}

// Helper XSS
function htmlEscape(str) {
    if (str == null) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

