// public/js/modules/charts.js

/**
 * Module to handle D3.js charts for StudTraj dashboard
 */
const ChartModule = (function() {

    // Échelle de couleur sombre aux extrémités
    const getHighGranularityScale = () => {
        return d3.scaleLinear()
            .domain([0, 25, 50, 75, 100])
            .range(["#7f0000", "#f44336", "#fbc02d", "#4caf50", "#1b5e20"])
            .interpolate(d3.interpolateHcl);
    };

    /**
     * Gère les noms pour correspondre EXACTEMENT à la sidebar
     */
    function getName(d) {
        if (d.nom || d.prenom) {
            return `${d.prenom || ''} ${d.nom || ''}`.trim();
        }
        // Priorité à exo_name pour les ressources
        return d.exo_name || d.title || d.name || d.funcname || d.student_identifier || "Sans nom";
    }

    function createTooltip() {
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
    }

    function renderStudentChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<h3>Performance moyenne des étudiants</h3>';
        if (!data || data.length === 0) {
            container.innerHTML += '<p>Aucune donnée disponible.</p>';
            return;
        }

        const tooltip = createTooltip();
        const colorScale = getHighGranularityScale();
        const margin = {top: 30, right: 180, bottom: 80, left: 60};
        const width = container.offsetWidth - margin.left - margin.right;
        const height = 350 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId).append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g").attr("transform", `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand().range([0, width]).domain(data.map(d => getName(d))).padding(0.3);
        const y = d3.scaleLinear().domain([0, (d3.max(data, d => +d.total_attempts) || 10) * 1.1]).range([height, 0]);

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(getName(d)))
            .attr("y", d => y(d.total_attempts))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.total_attempts))
            .attr("fill", d => colorScale(d.success_rate))
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.7);
                tooltip.style("visibility", "visible").html(`<strong>${getName(d)}</strong><br>Réussite: ${d.success_rate}%`);
            })
            .on("mousemove", (e) => tooltip.style("top", (e.pageY - 40) + "px").style("left", (e.pageX + 15) + "px"))
            .on("mouseout", function() { d3.select(this).attr("opacity", 1); tooltip.style("visibility", "hidden"); })
            .on("click", function(event, d) {
                tooltip.style("visibility", "hidden");
                document.dispatchEvent(new CustomEvent('student-chart-click', { detail: { studentId: d.id || d.student_id } }));
            });

        svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x)).selectAll("text").attr("transform", "translate(-10,5)rotate(-35)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y));
        renderLegend(svg, width + 30);
    }

    function renderExerciseChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<h3>Taux de réussite par exercice</h3>';
        if (!data || data.length === 0) {
            container.innerHTML += '<p>Aucune donnée disponible.</p>';
            return;
        }

        const tooltip = createTooltip();
        const colorScale = getHighGranularityScale();
        const margin = {top: 30, right: 180, bottom: 100, left: 60};
        const viewBoxWidth = 950;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = 450 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId).append("svg")
            .attr("viewBox", `0 0 ${viewBoxWidth} 450`).style("width", "100%")
            .append("g").attr("transform", `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand().range([0, width]).domain(data.map(d => getName(d))).padding(0.3);
        const y = d3.scaleLinear().domain([0, 100]).range([height, 0]);

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(getName(d)))
            .attr("y", d => y(d.success_rate))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.success_rate))
            .attr("fill", d => colorScale(d.success_rate))
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.7);
                tooltip.style("visibility", "visible").html(`<strong>${getName(d)}</strong><br>Réussite: ${d.success_rate}%`);
            })
            .on("mousemove", (e) => tooltip.style("top", (e.pageY - 40) + "px").style("left", (e.pageX + 15) + "px"))
            .on("mouseout", function() { d3.select(this).attr("opacity", 1); tooltip.style("visibility", "hidden"); })
            .on("click", function(event, d) {
                tooltip.style("visibility", "hidden");
                document.dispatchEvent(new CustomEvent('exercise-chart-click', { detail: { exerciseId: d.exercise_id || d.id } }));
            });

        svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x)).selectAll("text").attr("transform", "translate(-10,5)rotate(-45)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y).tickFormat(d => d + "%"));
        renderLegend(svg, width + 30);
    }

    /**
     * Graphique de complétion (indispensable pour éviter l'erreur de chargement)
     */
    function renderExerciseCompletionChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '<h3>Progression des exercices</h3>';
        if (!data || data.total === undefined) return;

        const margin = {top: 40, right: 20, bottom: 40, left: 60};
        const width = 400 - margin.left - margin.right;
        const height = 300 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId).append("svg")
            .attr("viewBox", "0 0 400 300").style("width", "100%")
            .append("g").attr("transform", `translate(${margin.left},${margin.top})`);

        const y = d3.scaleLinear().domain([0, data.total]).range([height, 0]);

        // Barre rouge (fond)
        svg.append("rect").attr("x", width/4).attr("y", 0).attr("width", width/2).attr("height", height).attr("fill", "#7f0000").attr("rx", 4);
        // Barre verte (progression)
        svg.append("rect").attr("x", width/4).attr("y", y(data.completed || 0)).attr("width", width/2).attr("height", height - y(data.completed || 0)).attr("fill", "#1b5e20").attr("rx", 4);

        svg.append("g").call(d3.axisLeft(y).ticks(5));
        svg.append("text").attr("x", width/2).attr("y", height + 25).attr("text-anchor", "middle").style("font-size", "12px").text(`${data.completed || 0} / ${data.total} faits`);
    }

    function renderLegend(svg, x) {
        const legend = svg.append("g").attr("transform", `translate(${x}, 0)`);
        const cats = [{c: "#1b5e20", l: "Parfait"}, {c: "#4caf50", l: "Bien"}, {c: "#fbc02d", l: "Moyen"}, {c: "#f44336", l: "Faible"}, {c: "#7f0000", l: "Critique"}];
        cats.forEach((item, i) => {
            const row = legend.append("g").attr("transform", `translate(0, ${i * 22})`);
            row.append("rect").attr("width", 15).attr("height", 15).attr("fill", item.c).attr("rx", 2);
            row.append("text").attr("x", 22).attr("y", 12).style("font-size", "11px").text(item.l);
        });
    }

    return {
        renderStudentChart,
        renderExerciseChart,
        renderExerciseCompletionChart
    };
})();