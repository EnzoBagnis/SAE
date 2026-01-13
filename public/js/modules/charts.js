// public/js/modules/charts.js

/**
 * Module pour gérer les graphiques D3.js de StudTraj
 */
const ChartModule = (function() {

    // Échelle de couleurs : Rouge sombre (0%) -> Jaune (50%) -> Vert sombre (100%)
    const colorScale = d3.scaleLinear()
        .domain([0, 25, 50, 75, 100])
        .range(["#7f0000", "#f44336", "#fbc02d", "#4caf50", "#1b5e20"])
        .interpolate(d3.interpolateHcl);

    // Fonction pour récupérer le nom EXACT (comme dans la sidebar)
    function getDisplayName(d) {
        if (d.prenom || d.nom) {
            return (d.prenom + " " + d.nom).trim();
        }
        // Pour les exercices : priorité au nom du TP (exo_name)
        return d.exo_name || d.title || d.funcname || "Inconnu";
    }

    // Gestion de la popup (tooltip)
    function showTooltip(event, content) {
        let tooltip = d3.select(".chart-tooltip");
        if (tooltip.empty()) {
            tooltip = d3.select("body").append("div").attr("class", "chart-tooltip")
                .style("position", "absolute").style("background", "rgba(0,0,0,0.9)")
                .style("color", "#fff").style("padding", "8px").style("border-radius", "4px")
                .style("font-size", "12px").style("pointer-events", "none").style("z-index", "10000");
        }
        tooltip.style("visibility", "visible").html(content)
            .style("top", (event.pageY - 40) + "px")
            .style("left", (event.pageX + 15) + "px");
    }

    function hideTooltip() {
        d3.select(".chart-tooltip").style("visibility", "hidden");
    }

    // --- GRAPHIQUE ETUDIANTS ---
    function renderStudentChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<h3>Performance moyenne des étudiants</h3>';
        if (!data || data.length === 0) return;

        const margin = {top: 30, right: 30, bottom: 80, left: 60};
        const width = container.offsetWidth - margin.left - margin.right;
        const height = 300 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId).append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g").attr("transform", `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand().range([0, width]).domain(data.map(d => getDisplayName(d))).padding(0.3);
        const y = d3.scaleLinear().domain([0, (d3.max(data, d => +d.total_attempts) || 10) * 1.1]).range([height, 0]);

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(getDisplayName(d)))
            .attr("y", d => y(d.total_attempts))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.total_attempts))
            .attr("fill", d => colorScale(d.success_rate))
            .style("cursor", "pointer")
            .on("mouseover", (event, d) => showTooltip(event, `<strong>${getDisplayName(d)}</strong><br>Réussite: ${d.success_rate}%`))
            .on("mousemove", (event) => showTooltip(event, d3.select(".chart-tooltip").html()))
            .on("mouseout", hideTooltip)
            .on("click", (event, d) => {
                hideTooltip();
                document.dispatchEvent(new CustomEvent('student-chart-click', { detail: { studentId: d.id || d.student_id } }));
            });

        svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x))
            .selectAll("text").attr("transform", "rotate(-35)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y));
    }

    // --- GRAPHIQUE EXERCICES ---
    function renderExerciseChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<h3>Taux de réussite par exercice</h3>';
        if (!data || data.length === 0) return;

        const margin = {top: 30, right: 30, bottom: 100, left: 60};
        const width = container.offsetWidth - margin.left - margin.right;
        const height = 400 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId).append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g").attr("transform", `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand().range([0, width]).domain(data.map(d => getDisplayName(d))).padding(0.3);
        const y = d3.scaleLinear().domain([0, 100]).range([height, 0]);

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(getDisplayName(d)))
            .attr("y", d => y(d.success_rate))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.success_rate))
            .attr("fill", d => colorScale(d.success_rate))
            .style("cursor", "pointer")
            .on("mouseover", (event, d) => showTooltip(event, `<strong>${getDisplayName(d)}</strong><br>Réussite: ${d.success_rate}%`))
            .on("mousemove", (event) => showTooltip(event, d3.select(".chart-tooltip").html()))
            .on("mouseout", hideTooltip)
            .on("click", (event, d) => {
                hideTooltip();
                document.dispatchEvent(new CustomEvent('exercise-chart-click', { detail: { exerciseId: d.exercise_id || d.id } }));
            });

        svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x))
            .selectAll("text").attr("transform", "rotate(-45)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y).tickFormat(d => d + "%"));
    }

    // --- GRAPHIQUE PROGRESSION ---
    function renderExerciseCompletionChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container || !data) return;
        container.innerHTML = '<h3>Progression des exercices</h3>';

        const total = data.total || 1;
        const completed = data.completed || 0;
        const margin = {top: 20, right: 20, bottom: 40, left: 40};
        const width = container.offsetWidth - margin.left - margin.right;
        const height = 200 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId).append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g").attr("transform", `translate(${margin.left},${margin.top})`);

        const y = d3.scaleLinear().domain([0, total]).range([height, 0]);

        // Fond (rouge sombre)
        svg.append("rect").attr("x", 50).attr("y", 0).attr("width", width - 100).attr("height", height).attr("fill", "#7f0000");
        // Progrès (vert sombre)
        svg.append("rect").attr("x", 50).attr("y", y(completed)).attr("width", width - 100).attr("height", height - y(completed)).attr("fill", "#1b5e20");

        svg.append("g").call(d3.axisLeft(y).ticks(5));
        svg.append("text").attr("x", width/2).attr("y", height + 30).attr("text-anchor", "middle").text(`${completed} / ${total} terminés`);
    }

    return {
        renderStudentChart,
        renderExerciseChart,
        renderExerciseCompletionChart
    };
})();