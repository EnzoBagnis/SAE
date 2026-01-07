// public/js/modules/charts.js

/**
 * Module to handle D3.js charts for StudTraj dashboard
 */
const ChartModule = (function() {

    /**
     * Render global student statistics chart
     * @param {Array} data - Array of student stats objects
     * @param {string} containerId - ID of the container element
     */
    function renderStudentChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<h3>Performance moyenne des étudiants</h3>';

        if (!data || data.length === 0) {
            container.innerHTML += '<p>Aucune donnée disponible.</p>';
            return;
        }

        // Setup margins and dimensions
        const margin = {top: 20, right: 20, bottom: 60, left: 50};
        const width = container.offsetWidth - margin.left - margin.right;
        const height = 300 - margin.top - margin.bottom;

        // Append SVG
        const svg = d3.select("#" + containerId)
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        // X Axis
        const x = d3.scaleBand()
            .range([0, width])
            .domain(data.map(d => (d.nom && d.prenom) ? `${d.nom} ${d.prenom}` : d.student_identifier))
            .padding(0.2);

        svg.append("g")
            .attr("transform", "translate(0," + height + ")")
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "translate(-10,0)rotate(-45)")
            .style("text-anchor", "end");

        // Y Axis
        const maxAttempts = d3.max(data, d => +d.total_attempts) || 10;
        const y = d3.scaleLinear()
            .domain([0, maxAttempts + (maxAttempts * 0.1)]) // Add some padding
            .range([height, 0]);

        svg.append("g")
            .call(d3.axisLeft(y).ticks(Math.min(maxAttempts, 10))); // Avoid too many ticks for small numbers

        // Add Y axis label
        svg.append("text")
            .attr("transform", "rotate(-90)")
            .attr("y", 0 - margin.left)
            .attr("x", 0 - (height / 2))
            .attr("dy", "1em")
            .style("text-anchor", "middle")
            .style("font-size", "12px")
            .text("Nombre de tentatives");

        // Color scale
        const colorScale = d3.scaleThreshold()
            .domain([50, 80])
            .range(["#ef5350", "#ffca28", "#66bb6a"]);

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
            // Interaction
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.8);
                tooltip.style("visibility", "visible")
                       .html(`<strong>${(d.nom && d.prenom) ? `${d.nom} ${d.prenom}` : d.student_identifier}</strong><br>` +
                             `Réussite: ${d.success_rate}%<br>` +
                             `Tentatives: ${d.total_attempts}`);
            })
            .on("mousemove", function(event) {
                tooltip.style("top", (event.pageY - 10) + "px")
                       .style("left", (event.pageX + 10) + "px");
            })
            .on("mouseout", function(event, d) {
                d3.select(this).attr("opacity", 1);
                tooltip.style("visibility", "hidden");
            })
            .on("click", function(event, d) {
                // Trigger event or navigate
                // We dispatch a custom event so the main app handles navigation
                const customEvent = new CustomEvent('student-chart-click', {
                    detail: { studentId: d.id }
                });
                document.dispatchEvent(customEvent);
            });

        // Add title
        svg.append("text")
            .attr("x", width / 2)
            .attr("y", -5)
            .attr("text-anchor", "middle")
            .style("font-size", "14px")
            .text("Tentatives (Hauteur) et Taux de réussite (Couleur)");
    }

    /**
     * Render global exercise statistics chart
     * @param {Array} data - Array of exercise stats objects
     * @param {string} containerId - ID of the container element
     */
    function renderExerciseChart(data, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<h3>Taux de réussite par exercice</h3>';

        if (!data || data.length === 0) {
            container.innerHTML += '<p>Aucune donnée disponible.</p>';
            return;
        }

        // Setup margins and dimensions
        const margin = {top: 20, right: 20, bottom: 100, left: 50}; // Larger bottom margin for long names
        const width = container.offsetWidth - margin.left - margin.right;
        const height = 300 - margin.top - margin.bottom;

        // Append SVG
        const svg = d3.select("#" + containerId)
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        // X Axis
        const x = d3.scaleBand()
            .range([0, width])
            .domain(data.map(d => d.exo_name))
            .padding(0.2);

        svg.append("g")
            .attr("transform", "translate(0," + height + ")")
            .call(d3.axisBottom(x))
            .selectAll("text")
            .attr("transform", "translate(-10,0)rotate(-45)")
            .style("text-anchor", "end")
            .style("font-size", "10px");

        // Y Axis
        const y = d3.scaleLinear()
            .domain([0, 100])
            .range([height, 0]);

        svg.append("g")
            .call(d3.axisLeft(y));

        // Color scale (Reverse for exercises? No, high success is still green usually,
        // though low success indicates a hard exercise which might be interesting)
        const colorScale = d3.scaleThreshold()
            .domain([50, 80])
            .range(["#ef5350", "#ffca28", "#66bb6a"]);

        // Tooltip (reuse logic or create new div if needed, but simple append works locally)
        // Note: ID must be unique if we have multiple charts, but usually one at a time.
        // We can scope it to container.
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


        // Bars
        svg.selectAll("rect")
            .data(data)
            .enter()
            .append("rect")
            .attr("x", d => x(d.exo_name))
            .attr("y", d => y(d.success_rate))
            .attr("width", x.bandwidth())
            .attr("height", d => height - y(d.success_rate))
            .attr("fill", d => colorScale(d.success_rate))
            // Interaction
            .style("cursor", "pointer")
            .on("mouseover", function(event, d) {
                d3.select(this).attr("opacity", 0.8);
                tooltip.style("visibility", "visible")
                       .html(`<strong>${d.exo_name}</strong><br>` +
                             `Réussite: ${d.success_rate}%<br>` +
                             `Essais totaux: ${d.total_attempts}`);
            })
            .on("mousemove", function(event) {
                tooltip.style("top", (event.pageY - 10) + "px")
                       .style("left", (event.pageX + 10) + "px");
            })
            .on("mouseout", function(event, d) {
                d3.select(this).attr("opacity", 1);
                tooltip.style("visibility", "hidden");
            })
            .on("click", function(event, d) {
                const customEvent = new CustomEvent('exercise-chart-click', {
                    detail: { exerciseId: d.exercise_id }
                });
                document.dispatchEvent(customEvent);
            });

        // Title
        svg.append("text")
            .attr("x", width / 2)
            .attr("y", -5)
            .attr("text-anchor", "middle")
            .style("font-size", "14px")
            .text("Taux de réussite (%)");
    }

    return {
        renderStudentChart: renderStudentChart,
        renderExerciseChart: renderExerciseChart
    };
})();

