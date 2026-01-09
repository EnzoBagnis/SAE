// public/js/modules/charts.js

/**
 * Module to handle D3.js charts for StudTraj dashboard
 */
const ChartModule = (function() {

    // Échelle de couleur partagée pour une meilleure cohérence
    // Transition fluide : Rouge -> Orange -> Jaune -> Vert Clair -> Vert Foncé
    const getPerformanceColorScale = () => {
        return d3.scaleLinear()
            .domain([0, 25, 50, 75, 100])
            .range(["#ef5350", "#ff9800", "#fdd835", "#8bc34a", "#4caf50"])
            .interpolate(d3.interpolateRgb);
    };

    /**
     * Render global student statistics chart
     */
    function renderStudentChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<h3>Performance moyenne des étudiants</h3>';
        if (!data || data.length === 0) {
            container.innerHTML += '<p>Aucune donnée disponible.</p>';
            return;
        }

        const margin = {top: 30, right: 160, bottom: 70, left: 60};
        const width = container.offsetWidth - margin.left - margin.right;
        const height = 350 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId)
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform", `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand()
            .range([0, width])
            .domain(data.map(d => (d.nom && d.prenom) ? `${d.nom} ${d.prenom}` : d.student_identifier))
            .padding(0.3);

        const maxAttempts = d3.max(data, d => +d.total_attempts) || 10;
        const y = d3.scaleLinear()
            .domain([0, maxAttempts * 1.1])
            .range([height, 0]);

        const colorScale = getPerformanceColorScale();

        // Axes
        svg.append("g")
            .attr("transform", `translate(0,${height})`)
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "translate(-10,5)rotate(-35)")
            .style("text-anchor", "end");

        svg.append("g").call(d3.axisLeft(y));

        // Tooltip
        const tooltip = d3.select("#" + containerId).append("div")
            .attr("class", "chart-tooltip")
            .style("position", "absolute")
            .style("visibility", "hidden")
            .style("background", "rgba(0,0,0,0.8)")
            .style("color", "#fff")
            .style("padding", "8px")
            .style("border-radius", "4px")
            .style("font-size", "12px")
            .style("z-index", "10");

        // Bars
        svg.selectAll("rect")
            .data(data)
            .enter()
            .append("rect")
            .attr("x", d => x((d.nom && d.prenom) ? `${d.nom} ${d.prenom}` : d.student_identifier))
            .attr("y", d => y(d.total_attempts))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.total_attempts))
            .attr("fill", d => colorScale(d.success_rate))
            .attr("rx", 3)
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.8);
                tooltip.style("visibility", "visible")
                    .html(`<strong>${(d.nom && d.prenom) ? `${d.nom} ${d.prenom}` : d.student_identifier}</strong><br>
                              Réussite: ${d.success_rate}%<br>
                              Tentatives: ${d.total_attempts}`);
            })
            .on("mousemove", (event) => {
                tooltip.style("top", (event.pageY - 10) + "px").style("left", (event.pageX + 10) + "px");
            })
            .on("mouseout", function() {
                d3.select(this).attr("opacity", 1);
                tooltip.style("visibility", "hidden");
            })
            .on("click", (event, d) => {
                document.dispatchEvent(new CustomEvent('student-chart-click', { detail: { studentId: d.id } }));
            });

        // Légende étendue
        const legend = svg.append("g").attr("transform", `translate(${width + 20}, 0)`);
        const categories = [
            { c: "#4caf50", l: "Excellent (>75%)" },
            { c: "#8bc34a", l: "Bon (50-75%)" },
            { c: "#fdd835", l: "Moyen (25-50%)" },
            { c: "#ff9800", l: "Fragile (10-25%)" },
            { c: "#ef5350", l: "Critique (<10%)" }
        ];

        categories.forEach((item, i) => {
            const row = legend.append("g").attr("transform", `translate(0, ${i * 22})`);
            row.append("rect").attr("width", 15).attr("height", 15).attr("fill", item.c).attr("rx", 2);
            row.append("text").attr("x", 22).attr("y", 12).style("font-size", "11px").text(item.l);
        });
    }

    /**
     * Render global exercise statistics chart
     */
    function renderExerciseChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<h3>Taux de réussite par exercice</h3>';
        if (!data || data.length === 0) {
            container.innerHTML += '<p>Aucune donnée disponible.</p>';
            return;
        }

        const margin = {top: 30, right: 160, bottom: 100, left: 60};
        const viewBoxWidth = 900;
        const viewBoxHeight = 450;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = viewBoxHeight - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId)
            .append("svg")
            .attr("viewBox", `0 0 ${viewBoxWidth} ${viewBoxHeight}`)
            .style("width", "100%")
            .style("height", "auto")
            .append("g")
            .attr("transform", `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand()
            .range([0, width])
            .domain(data.map(d => d.funcname || d.exo_name))
            .padding(0.3);

        const y = d3.scaleLinear().domain([0, 100]).range([height, 0]);
        const colorScale = getPerformanceColorScale();

        svg.append("g")
            .attr("transform", `translate(0,${height})`)
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "translate(-10,5)rotate(-45)")
            .style("text-anchor", "end");

        svg.append("g").call(d3.axisLeft(y).ticks(10).tickFormat(d => d + "%"));

        const tooltip = d3.select("#" + containerId).append("div")
            .style("position", "absolute")
            .style("visibility", "hidden")
            .style("background", "rgba(0,0,0,0.8)")
            .style("color", "#fff")
            .style("padding", "8px")
            .style("border-radius", "4px")
            .style("font-size", "12px");

        svg.selectAll("rect")
            .data(data)
            .enter()
            .append("rect")
            .attr("x", d => x(d.funcname || d.exo_name))
            .attr("y", d => y(d.success_rate))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.success_rate))
            .attr("fill", d => colorScale(d.success_rate))
            .attr("rx", 3)
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.8);
                tooltip.style("visibility", "visible")
                    .html(`<strong>${d.exo_name}</strong><br>
                              Réussite: ${d.success_rate}%<br>
                              Essais totaux: ${d.total_attempts}`);
            })
            .on("mousemove", (event) => {
                tooltip.style("top", (event.pageY - 10) + "px").style("left", (event.pageX + 10) + "px");
            })
            .on("mouseout", function() {
                d3.select(this).attr("opacity", 1);
                tooltip.style("visibility", "hidden");
            })
            .on("click", (event, d) => {
                document.dispatchEvent(new CustomEvent('exercise-chart-click', { detail: { exerciseId: d.exercise_id } }));
            });

        // Légende identique
        const legend = svg.append("g").attr("transform", `translate(${width + 20}, 0)`);
        const categories = [
            { c: "#4caf50", l: "Très Facile (>75%)" },
            { c: "#8bc34a", l: "Facile (50-75%)" },
            { c: "#fdd835", l: "Moyen (25-50%)" },
            { c: "#ff9800", l: "Difficile (10-25%)" },
            { c: "#ef5350", l: "Très Difficile (<10%)" }
        ];

        categories.forEach((item, i) => {
            const row = legend.append("g").attr("transform", `translate(0, ${i * 22})`);
            row.append("rect").attr("width", 15).attr("height", 15).attr("fill", item.c).attr("rx", 2);
            row.append("text").attr("x", 22).attr("y", 12).style("font-size", "11px").text(item.l);
        });
    }

    return {
        renderStudentChart,
        renderExerciseChart
    };
})();