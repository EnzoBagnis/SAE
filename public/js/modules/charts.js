// public/js/modules/charts.js

const ChartModule = (function() {

    const getHighGranularityScale = () => {
        return d3.scaleLinear()
            .domain([0, 25, 50, 75, 100])
            .range(["#7f0000", "#f44336", "#fbc02d", "#4caf50", "#1b5e20"])
            .interpolate(d3.interpolateHcl);
    };

    /**
     * Aide pour obtenir le nom exact (priorité aux noms "humains" de la sidebar)
     */
    function getName(d) {
        if (d.nom || d.prenom) return `${d.prenom || ''} ${d.nom || ''}`.trim();
        // Pour les TP : priorité à exo_name (nom affiché à gauche) sur funcname (nom technique)
        return d.exo_name || d.title || d.name || d.funcname || "Inconnu";
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
            .style("z-index", "9999")
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
            .domain(data.map(d => getName(d))) // Utilisation du helper Name
            .padding(0.3);

        const maxAttempts = d3.max(data, d => +d.total_attempts) || 10;
        const y = d3.scaleLinear().domain([0, maxAttempts * 1.1]).range([height, 0]);
        const colorScale = getHighGranularityScale();
        const tooltip = createTooltip();

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(getName(d)))
            .attr("y", d => y(d.total_attempts))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.total_attempts))
            .attr("fill", d => colorScale(d.success_rate))
            .attr("stroke", "#fff").attr("stroke-width", 0.5)
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.7);
                tooltip.style("visibility", "visible")
                    .html(`<strong>${getName(d)}</strong><br>` +
                        `Taux de réussite: <span style="color:${colorScale(d.success_rate)}; font-weight:bold">${d.success_rate}%</span><br>` +
                        `Tentatives: ${d.total_attempts}`);
            })
            .on("mousemove", function(event) {
                tooltip.style("top", (event.pageY - 40) + "px").style("left", (event.pageX + 15) + "px");
            })
            .on("mouseout", function() {
                d3.select(this).attr("opacity", 1);
                tooltip.style("visibility", "hidden");
            })
            .on("click", (event, d) => {
                tooltip.style("visibility", "hidden"); // FERME LA POPUP AU CLIC
                document.dispatchEvent(new CustomEvent('student-chart-click', { detail: { studentId: d.id } }));
            });

        svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x))
            .selectAll("text").attr("transform", "translate(-10,5)rotate(-35)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y));
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

        const x = d3.scaleBand().range([0, width]).domain(data.map(d => getName(d))).padding(0.3); // Utilisation Name
        const y = d3.scaleLinear().domain([0, 100]).range([height, 0]);
        const colorScale = getHighGranularityScale();
        const tooltip = createTooltip();

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(getName(d)))
            .attr("y", d => y(d.success_rate))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.success_rate))
            .attr("fill", d => colorScale(d.success_rate))
            .attr("stroke", "#fff").attr("stroke-width", 0.5)
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.7);
                tooltip.style("visibility", "visible")
                    .html(`<strong>${getName(d)}</strong><br>` +
                        `Réussite: <span style="color:${colorScale(d.success_rate)}; font-weight:bold">${d.success_rate}%</span><br>` +
                        `Essais totaux: ${d.total_attempts}`);
            })
            .on("mousemove", function(event) {
                tooltip.style("top", (event.pageY - 40) + "px").style("left", (event.pageX + 15) + "px");
            })
            .on("mouseout", function() {
                d3.select(this).attr("opacity", 1);
                tooltip.style("visibility", "hidden");
            })
            .on("click", (event, d) => {
                tooltip.style("visibility", "hidden"); // FERME LA POPUP AU CLIC
                document.dispatchEvent(new CustomEvent('exercise-chart-click', { detail: { exerciseId: d.exercise_id || d.id } }));
            });

        svg.append("g").attr("transform", `translate(0,${height})`).call(d3.axisBottom(x))
            .selectAll("text").attr("transform", "translate(-10,5)rotate(-45)").style("text-anchor", "end");
        svg.append("g").call(d3.axisLeft(y).tickFormat(d => d + "%"));
        renderLegend(svg, width + 30, 0);
    }

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

    function renderExerciseCompletionChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<h3>Progression des exercices</h3>';

        if (!data || typeof data.total === 'undefined') {
            container.innerHTML += '<p>Aucune donnée disponible.</p>';
            return;
        }

        const completed = data.completed || 0;
        const notCompleted = data.total - completed;
        const total = data.total || 0;

        if (total === 0) return;

        const margin = {top: 40, right: 20, bottom: 60, left: 60};
        const width = 600 - margin.left - margin.right;
        const height = 400 - margin.top - margin.bottom;

        const svg = d3.select("#" + containerId)
            .append("svg")
            .attr("viewBox", `0 0 600 400`)
            .style("width", "100%").style("height", "auto")
            .append("g")
            .attr("transform", `translate(${margin.left}, ${margin.top})`);

        const x = d3.scaleBand().range([0, width]).domain(['Exercices']).padding(0.3);
        const y = d3.scaleLinear().domain([0, total]).range([height, 0]);

        const stackData = d3.stack().keys(['completed', 'notCompleted'])([{ category: 'Exercices', completed, notCompleted }]);

        const colors = { completed: '#1b5e20', notCompleted: '#7f0000' };

        svg.selectAll("g.layer")
            .data(stackData).enter().append("g")
            .attr("fill", d => colors[d.key])
            .selectAll("rect").data(d => d).enter().append("rect")
            .attr("x", d => x('Exercices'))
            .attr("y", d => y(d[1]))
            .attr("height", d => y(d[0]) - y(d[1]))
            .attr("width", x.bandwidth());

        svg.append("g").attr("transform", `translate(0, ${height})`).call(d3.axisBottom(x));
        svg.append("g").call(d3.axisLeft(y));
    }

    return {
        renderStudentChart,
        renderExerciseChart,
        renderExerciseCompletionChart
    };
})();