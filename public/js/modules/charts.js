// public/js/modules/charts.js

/**
 * Module to handle D3.js charts for StudTraj dashboard
 */
const ChartModule = (function() {

    /**
     * Échelle de couleur haute performance :
     * 0%   : Rouge Sombre (Critique)
     * 25%  : Rouge Vif (Insuffisant)
     * 50%  : Jaune/Or (Moyen)
     * 75%  : Vert Éclatant (Bien)
     * 100% : Vert Forêt Sombre (Excellent)
     * L'interpolation HCL permet des transitions plus riches que RGB.
     */
    const getHighGranularityScale = () => {
        return d3.scaleLinear()
            .domain([0, 25, 50, 75, 100])
            .range(["#7f0000", "#f44336", "#fbc02d", "#4caf50", "#1b5e20"])
            .interpolate(d3.interpolateHcl);
    };

    function renderStudentChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<h3>Performance moyenne des étudiants</h3>';
        if (!data || data.length === 0) {
            container.innerHTML += '<p>Aucune donnée disponible.</p>';
            return;
        }

        const margin = {top: 30, right: 180, bottom: 80, left: 60};
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
        const y = d3.scaleLinear().domain([0, maxAttempts * 1.1]).range([height, 0]);
        const colorScale = getHighGranularityScale();

        // Axes
        svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x))
            .selectAll("text").attr("transform", "translate(-10,5)rotate(-35)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y));

        // Bars
        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x((d.nom && d.prenom) ? `${d.nom} ${d.prenom}` : d.student_identifier))
            .attr("y", d => y(d.total_attempts))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.total_attempts))
            .attr("fill", d => colorScale(d.success_rate))
            .attr("stroke", "#fff").attr("stroke-width", 0.5) // Séparation subtile
            .style("cursor", "pointer")
            .on("click", (event, d) => {
                document.dispatchEvent(new CustomEvent('student-chart-click', { detail: { studentId: d.id } }));
            });

        // Légende Granulaire
        renderLegend(svg, width + 30, 0);
    }

    function renderExerciseChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<h3>Taux de réussite par exercice</h3>';
        if (!data || data.length === 0) {
            container.innerHTML += '<p>Aucune donnée disponible.</p>';
            return;
        }

        const margin = {top: 30, right: 180, bottom: 100, left: 60};
        const viewBoxWidth = 950;
        const viewBoxHeight = 450;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = viewBoxHeight - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId)
            .append("svg")
            .attr("viewBox", `0 0 ${viewBoxWidth} ${viewBoxHeight}`)
            .style("width", "100%").style("height", "auto")
            .append("g").attr("transform", `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand().range([0, width]).domain(data.map(d => d.funcname || d.exo_name)).padding(0.3);
        const y = d3.scaleLinear().domain([0, 100]).range([height, 0]);
        const colorScale = getHighGranularityScale();

        svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x))
            .selectAll("text").attr("transform", "translate(-10,5)rotate(-45)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y).tickFormat(d => d + "%"));

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(d.funcname || d.exo_name))
            .attr("y", d => y(d.success_rate))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.success_rate))
            .attr("fill", d => colorScale(d.success_rate))
            .attr("stroke", "#fff").attr("stroke-width", 0.5)
            .style("cursor", "pointer")
            .on("click", (event, d) => {
                document.dispatchEvent(new CustomEvent('exercise-chart-click', { detail: { exerciseId: d.exercise_id } }));
            });

        renderLegend(svg, width + 30, 0);
    }

    /**
     * Helper pour dessiner une légende détaillée
     */
    function renderLegend(svg, x, y) {
        const legend = svg.append("g").attr("transform", `translate(${x}, ${y})`);
        const categories = [
            { c: "#1b5e20", l: "Parfait (100%)" },
            { c: "#4caf50", l: "Très Bien (75%)" },
            { c: "#fbc02d", l: "Passable (50%)" },
            { c: "#f44336", l: "Faible (25%)" },
            { c: "#7f0000", l: "Critique (0%)" }
        ];

        categories.forEach((item, i) => {
            const row = legend.append("g").attr("transform", `translate(0, ${i * 25})`);
            row.append("rect").attr("width", 18).attr("height", 18).attr("fill", item.c).attr("rx", 3);
            row.append("text").attr("x", 26).attr("y", 14).style("font-size", "12px").style("font-weight", "600").text(item.l);
        });
    }

    return { renderStudentChart, renderExerciseChart };
})();