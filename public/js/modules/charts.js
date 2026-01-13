// public/js/modules/charts.js

const ChartModule = (function() {
    "use strict";

    // Configuration des couleurs (Extrémités très sombres)
    const getHighGranularityScale = function() {
        return d3.scaleLinear()
            .domain([0, 25, 50, 75, 100])
            .range(["#7f0000", "#f44336", "#fbc02d", "#4caf50", "#1b5e20"])
            .interpolate(d3.interpolateHcl);
    };

    // Fonction pour obtenir le nom EXACT des boutons de la sidebar
    const getName = function(d) {
        if (d.nom || d.prenom) {
            return ((d.prenom || '') + ' ' + (d.nom || '')).trim();
        }
        // Priorité absolue à exo_name pour correspondre aux boutons de gauche
        return d.exo_name || d.title || d.name || d.funcname || "Sans nom";
    };

    // Création du Tooltip sur le body
    const createTooltip = function() {
        d3.select(".chart-tooltip").remove();
        return d3.select("body")
            .append("div")
            .attr("class", "chart-tooltip")
            .style("position", "absolute")
            .style("visibility", "hidden")
            .style("background-color", "rgba(0,0,0,0.9)")
            .style("color", "#fff")
            .style("padding", "10px")
            .style("border-radius", "4px")
            .style("font-size", "12px")
            .style("pointer-events", "none")
            .style("z-index", "10000")
            .style("box-shadow", "0 4px 8px rgba(0,0,0,0.5)");
    };

    const renderLegend = function(svg, xPos) {
        const categories = [
            { c: "#1b5e20", l: "Parfait (100%)" },
            { c: "#4caf50", l: "Très Bien (75%)" },
            { c: "#fbc02d", l: "Passable (50%)" },
            { c: "#f44336", l: "Faible (25%)" },
            { c: "#7f0000", l: "Critique (0%)" }
        ];
        const legend = svg.append("g").attr("transform", "translate(" + (xPos + 20) + ", 0)");
        categories.forEach((item, i) => {
            const row = legend.append("g").attr("transform", "translate(0, " + (i * 22) + ")");
            row.append("rect").attr("width", 15).attr("height", 15).attr("fill", item.c).attr("rx", 2);
            row.append("text").attr("x", 22).attr("y", 12).style("font-size", "11px").text(item.l);
        });
    };

    const renderStudentChart = function(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<h3>Performance moyenne des étudiants</h3>';
        if (!data || data.length === 0) return;

        const tooltip = createTooltip();
        const colorScale = getHighGranularityScale();
        const margin = {top: 30, right: 170, bottom: 80, left: 60};
        const width = container.offsetWidth - margin.left - margin.right;
        const height = 350 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId).append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        const x = d3.scaleBand().range([0, width]).domain(data.map(d => getName(d))).padding(0.3);
        const y = d3.scaleLinear().domain([0, (d3.max(data, d => +d.total_attempts) || 10) * 1.1]).range([height, 0]);

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(getName(d)))
            .attr("y", d => y(d.total_attempts))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.total_attempts))
            .attr("fill", d => colorScale(d.success_rate))
            .style("cursor", "pointer")
            .on("mouseover", (event, d) => {
                tooltip.style("visibility", "visible").html("<strong>" + getName(d) + "</strong><br>Réussite: " + d.success_rate + "%");
            })
            .on("mousemove", (event) => {
                tooltip.style("top", (event.pageY - 40) + "px").style("left", (event.pageX + 15) + "px");
            })
            .on("mouseout", () => tooltip.style("visibility", "hidden"))
            .on("click", (event, d) => {
                tooltip.style("visibility", "hidden");
                document.dispatchEvent(new CustomEvent('student-chart-click', { detail: { studentId: d.id || d.student_id } }));
            });

        svg.append("g").attr("transform", "translate(0," + height + ")").call(d3.axisBottom(x)).selectAll("text").attr("transform", "translate(-10,5)rotate(-35)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y));
        renderLegend(svg, width);
    };

    const renderExerciseChart = function(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<h3>Taux de réussite par exercice</h3>';
        if (!data || data.length === 0) return;

        const tooltip = createTooltip();
        const colorScale = getHighGranularityScale();
        const margin = {top: 30, right: 170, bottom: 100, left: 60};
        const width = 950 - margin.left - margin.right;
        const height = 450 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId).append("svg")
            .attr("viewBox", "0 0 950 450").style("width", "100%")
            .append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        const x = d3.scaleBand().range([0, width]).domain(data.map(d => getName(d))).padding(0.3);
        const y = d3.scaleLinear().domain([0, 100]).range([height, 0]);

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(getName(d)))
            .attr("y", d => y(d.success_rate))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.success_rate))
            .attr("fill", d => colorScale(d.success_rate))
            .style("cursor", "pointer")
            .on("mouseover", (event, d) => {
                tooltip.style("visibility", "visible").html("<strong>" + getName(d) + "</strong><br>Réussite: " + d.success_rate + "%");
            })
            .on("mousemove", (event) => {
                tooltip.style("top", (event.pageY - 40) + "px").style("left", (event.pageX + 15) + "px");
            })
            .on("mouseout", () => tooltip.style("visibility", "hidden"))
            .on("click", (event, d) => {
                tooltip.style("visibility", "hidden");
                document.dispatchEvent(new CustomEvent('exercise-chart-click', { detail: { exerciseId: d.exercise_id || d.id } }));
            });

        svg.append("g").attr("transform", "translate(0," + height + ")").call(d3.axisBottom(x)).selectAll("text").attr("transform", "translate(-10,5)rotate(-45)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y).tickFormat(d => d + "%"));
        renderLegend(svg, width);
    };

    const renderExerciseCompletionChart = function(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container || !data) return;
        container.innerHTML = '<h3>Progression des exercices</h3>';
        const margin = {top: 20, right: 20, bottom: 40, left: 40};
        const width = 400 - margin.left - margin.right;
        const height = 250 - margin.top - margin.bottom;
        const svg = d3.select("#" + containerId).append("svg").attr("viewBox", "0 0 400 250").style("width", "100%")
            .append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");
        const y = d3.scaleLinear().domain([0, data.total || 1]).range([height, 0]);
        svg.append("rect").attr("x", width/4).attr("y", 0).attr("width", width/2).attr("height", height).attr("fill", "#7f0000").attr("rx", 3);
        svg.append("rect").attr("x", width/4).attr("y", y(data.completed || 0)).attr("width", width/2).attr("height", height - y(data.completed || 0)).attr("fill", "#1b5e20").attr("rx", 3);
        svg.append("g").call(d3.axisLeft(y).ticks(5));
    };

    // EXPORTATION DES FONCTIONS
    return {
        renderStudentChart: renderStudentChart,
        renderExerciseChart: renderExerciseChart,
        renderExerciseCompletionChart: renderExerciseCompletionChart
    };

})();