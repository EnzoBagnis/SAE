// public/js/modules/charts.js

const ChartModule = (function() {

    const getHighGranularityScale = () => {
        return d3.scaleLinear()
            .domain([0, 25, 50, 75, 100])
            .range(["#7f0000", "#f44336", "#fbc02d", "#4caf50", "#1b5e20"])
            .interpolate(d3.interpolateHcl);
    };

    /**
     * CORRECTION : On attache le tooltip au body pour éviter les décalages de conteneur
     */
    function createTooltip() {
        // Supprimer l'ancien tooltip s'il existe pour éviter les doublons
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
            .style("z-index", "9999") // Très haut pour passer devant tout
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
            .domain(data.map(d => (d.nom && d.prenom) ? `${d.nom} ${d.prenom}` : d.student_identifier))
            .padding(0.3);

        const maxAttempts = d3.max(data, d => +d.total_attempts) || 10;
        const y = d3.scaleLinear().domain([0, maxAttempts * 1.1]).range([height, 0]);
        const colorScale = getHighGranularityScale();
        const tooltip = createTooltip();

        // Bars
        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x((d.nom && d.prenom) ? `${d.nom} ${d.prenom}` : d.student_identifier))
            .attr("y", d => y(d.total_attempts))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.total_attempts))
            .attr("fill", d => colorScale(d.success_rate))
            .attr("stroke", "#fff").attr("stroke-width", 0.5)
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.7);
                tooltip.style("visibility", "visible")
                    .html(`<strong>${(d.nom && d.prenom) ? `${d.nom} ${d.prenom}` : d.student_identifier}</strong><br>` +
                        `Taux de réussite: <span style="color:${colorScale(d.success_rate)}; font-weight:bold">${d.success_rate}%</span><br>` +
                        `Tentatives: ${d.total_attempts}`);
            })
            .on("mousemove", function(event) {
                // CORRECTION POSITION : Utilisation des coordonnées page avec un petit offset
                tooltip.style("top", (event.pageY - 40) + "px") // Un peu plus haut que le curseur
                    .style("left", (event.pageX + 15) + "px"); // Un peu à droite
            })
            .on("mouseout", function() {
                d3.select(this).attr("opacity", 1);
                tooltip.style("visibility", "hidden");
            })
            .on("click", (event, d) => {
                document.dispatchEvent(new CustomEvent('student-chart-click', { detail: { studentId: d.id } }));
            });

        // Axes & Legend
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

        const x = d3.scaleBand().range([0, width]).domain(data.map(d => d.funcname || d.exo_name)).padding(0.3);
        const y = d3.scaleLinear().domain([0, 100]).range([height, 0]);
        const colorScale = getHighGranularityScale();
        const tooltip = createTooltip();

        svg.selectAll("rect").data(data).enter().append("rect")
            .attr("x", d => x(d.funcname || d.exo_name))
            .attr("y", d => y(d.success_rate))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.success_rate))
            .attr("fill", d => colorScale(d.success_rate))
            .attr("stroke", "#fff").attr("stroke-width", 0.5)
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.7);
                tooltip.style("visibility", "visible")
                    .html(`<strong>${d.exo_name}</strong><br>` +
                        `Réussite: <span style="color:${colorScale(d.success_rate)}; font-weight:bold">${d.success_rate}%</span><br>` +
                        `Essais totaux: ${d.total_attempts}`);
            })
            .on("mousemove", function(event) {
                // CORRECTION POSITION : Coordonnées page
                tooltip.style("top", (event.pageY - 40) + "px")
                    .style("left", (event.pageX + 15) + "px");
            })
            .on("mouseout", function() {
                d3.select(this).attr("opacity", 1);
                tooltip.style("visibility", "hidden");
            })
            .on("click", (event, d) => {
                document.dispatchEvent(new CustomEvent('exercise-chart-click', { detail: { exerciseId: d.exercise_id } }));
            });

        // Axes & Legend
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

    /**
     *
     * Render exercise completion chart (done vs not done)
     * @param {Object} data - Object with completed and total counts
     * @param {string} containerId - ID of the container element
     */
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

        if (total === 0) {
            container.innerHTML += '<p>Aucun exercice disponible.</p>';
            return;
        }

        // Setup dimensions
        const margin = {top: 40, right: 20, bottom: 60, left: 60};
        const viewBoxWidth = 600;
        const viewBoxHeight = 400;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = viewBoxHeight - margin.top - margin.bottom;

        // Append SVG with viewBox for responsiveness
        const svg = d3.select("#" + containerId)
            .append("svg")
            .attr("viewBox", `0 0 ${viewBoxWidth} ${viewBoxHeight}`)
            .attr("preserveAspectRatio", "xMidYMid meet")
            .style("width", "100%")
            .style("height", "auto")
            .style("max-height", "400px")
            .append("g")
            .attr("transform", `translate(${margin.left}, ${margin.top})`);

        // Prepare data for stacked bar chart
        const chartData = [
            { category: 'Exercices', completed: completed, notCompleted: notCompleted }
        ];

        // X Axis
        const x = d3.scaleBand()
            .range([0, width])
            .domain(['Exercices'])
            .padding(0.3);

        svg.append("g")
            .attr("transform", `translate(0, ${height})`)
            .call(d3.axisBottom(x))
            .selectAll("text")
            .style("font-size", "14px")
            .style("font-weight", "bold");

        // Y Axis
        const y = d3.scaleLinear()
            .domain([0, total])
            .range([height, 0]);

        svg.append("g")
            .call(d3.axisLeft(y).ticks(Math.min(total, 10)));

        // Add Y axis label
        svg.append("text")
            .attr("transform", "rotate(-90)")
            .attr("y", 0 - margin.left + 10)
            .attr("x", 0 - (height / 2))
            .attr("dy", "1em")
            .style("text-anchor", "middle")
            .style("font-size", "12px")
            .text("Nombre d'exercices");

        // Tooltip
        const tooltip = d3.select("#" + containerId)
            .append("div")
            .style("position", "absolute")
            .style("visibility", "hidden")
            .style("background-color", "rgba(0,0,0,0.8)")
            .style("color", "#fff")
            .style("padding", "8px")
            .style("border-radius", "4px")
            .style("font-size", "12px")
            .style("pointer-events", "none");

        // Stack the data
        const stack = d3.stack()
            .keys(['completed', 'notCompleted']);

        const stackedData = stack(chartData);

        // Color scale
        const colors = {
            completed: '#66bb6a',
            notCompleted: '#ef5350'
        };

        // Draw stacked bars
        svg.selectAll("g.layer")
            .data(stackedData)
            .enter()
            .append("g")
            .attr("class", "layer")
            .attr("fill", d => colors[d.key])
            .selectAll("rect")
            .data(d => d)
            .enter()
            .append("rect")
            .attr("x", d => x('Exercices'))
            .attr("y", d => y(d[1]))
            .attr("height", d => y(d[0]) - y(d[1]))
            .attr("width", x.bandwidth())
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                const key = d3.select(this.parentNode).datum().key;
                const value = d[1] - d[0];
                const label = key === 'completed' ? 'Exercices faits' : 'Exercices non faits';
                const percentage = ((value / total) * 100).toFixed(1);

                d3.select(this).attr("opacity", 0.8);
                tooltip.style("visibility", "visible")
                       .html(`<strong>${label}</strong><br>` +
                             `Nombre: ${value}<br>` +
                             `Pourcentage: ${percentage}%`);
            })
            .on("mousemove", function(event) {
                tooltip.style("top", (event.pageY - 10) + "px")
                       .style("left", (event.pageX + 10) + "px");
            })
            .on("mouseout", function() {
                d3.select(this).attr("opacity", 1);
                tooltip.style("visibility", "hidden");
            });

        // Add value labels on bars
        svg.selectAll("g.layer")
            .selectAll("text")
            .data(d => d)
            .enter()
            .append("text")
            .attr("x", d => x('Exercices') + x.bandwidth() / 2)
            .attr("y", d => y(d[1]) + (y(d[0]) - y(d[1])) / 2)
            .attr("dy", ".35em")
            .attr("text-anchor", "middle")
            .style("fill", "#fff")
            .style("font-weight", "bold")
            .style("font-size", "16px")
            .style("text-shadow", "1px 1px 2px rgba(0,0,0,0.8)")
            .text(d => {
                const value = d[1] - d[0];
                return value > 0 ? value : '';
            });

        // Add title
        svg.append("text")
            .attr("x", width / 2)
            .attr("y", -20)
            .attr("text-anchor", "middle")
            .style("font-size", "16px")
            .style("font-weight", "bold")
            .text(`Total: ${completed} / ${total} exercices`);

        // Add legend (top left)
        const legend = svg.append("g")
            .attr("class", "legend")
            .attr("transform", `translate(0, -25)`);

        const legendData = [
            { color: '#66bb6a', label: 'Exercices faits', key: 'completed' },
            { color: '#ef5350', label: 'Exercices non faits', key: 'notCompleted' }
        ];

        legendData.forEach((item, i) => {
            const legendRow = legend.append("g")
                .attr("transform", `translate(0, ${i * 20})`);

            legendRow.append("rect")
                .attr("width", 14)
                .attr("height", 14)
                .attr("fill", item.color)
                .attr("rx", 2);

            legendRow.append("text")
                .attr("x", 20)
                .attr("y", 7)
                .attr("dy", ".35em")
                .style("font-size", "11px")
                .style("fill", "#2c3e50")
                .style("font-weight", "500")
                .text(item.label);
        });
    }

    return {
        renderStudentChart: renderStudentChart,
        renderExerciseChart: renderExerciseChart,
        renderExerciseCompletionChart: renderExerciseCompletionChart
    };
})();
