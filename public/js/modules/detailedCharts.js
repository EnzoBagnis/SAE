/**
 * DetailedCharts Module - Advanced D3.js visualizations for students and exercises
 * Provides detailed, interactive charts for individual students and exercises
 */
const DetailedCharts = (function() {

    /**
     * Helper function to check if an attempt is successful
     * Handles multiple property name variations
     */
    function isAttemptSuccessful(attempt) {
        if (!attempt) return false;

        // Check various possible property names
        return !!(
            attempt.is_correct === true ||
            attempt.is_correct === 1 ||
            attempt.is_correct === '1' ||
            attempt.success === true ||
            attempt.success === 1 ||
            attempt.success === '1' ||
            attempt.correct === true ||
            attempt.correct === 1 ||
            attempt.passed === true ||
            attempt.passed === 1
        );
    }

    /**
     * Render detailed student performance charts
     * @param {Object} student - Student data
     * @param {Array} attempts - Student's attempts
     * @param {Object} stats - Student's statistics
     * @param {string} containerId - ID of the container element
     */
    function renderStudentDetailedCharts(student, attempts, stats, containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error('❌ Container not found:', containerId);
            return;
        }

        // Debug logs
        console.log('📊 Rendering student charts:', {
            student,
            attemptsCount: attempts ? attempts.length : 0,
            stats
        });

        // Validate data
        if (!student) {
            console.error('❌ Student data is missing');
            container.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 2rem;">Erreur: Données étudiant manquantes</p>';
            return;
        }

        if (!attempts || !Array.isArray(attempts)) {
            console.warn('⚠️ Attempts data is missing or invalid, using empty array');
            attempts = [];
        }

        if (!stats || typeof stats !== 'object') {
            console.warn('⚠️ Stats data is missing or invalid, using default');
            stats = { total_attempts: 0, correct_attempts: 0, success_rate: 0 };
        }

        container.innerHTML = '';
        container.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1rem;';

        try {
            // Chart 1: Progress Over Time (Line Chart)
            renderProgressOverTime(attempts, container);

            // Chart 2: Success Rate by Exercise (Bar Chart)
            renderSuccessRateByExercise(attempts, container);

            // Chart 3: Attempts Distribution (Pie Chart)
            renderAttemptsDistribution(stats, container);

            // Chart 4: Time Spent Analysis (Bar Chart)
            renderTimeSpentAnalysis(attempts, container);

            console.log('✅ All charts rendered successfully');
        } catch (error) {
            console.error('❌ Error rendering charts:', error);
            container.innerHTML = `<p style="color: #e74c3c; text-align: center; padding: 2rem;">Erreur lors du rendu: ${error.message}</p>`;
        }
    }

    /**
     * Render progress over time line chart
     * Shows the evolution of success rate over time
     */
    function renderProgressOverTime(attempts, parentContainer) {
        const chartDiv = document.createElement('div');
        chartDiv.className = 'chart-container';
        chartDiv.style.cssText = 'background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        parentContainer.appendChild(chartDiv);

        const title = document.createElement('h3');
        title.textContent = 'Évolution de la performance';
        title.style.cssText = 'margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;';
        chartDiv.appendChild(title);

        if (!attempts || attempts.length === 0) {
            chartDiv.innerHTML += '<p style="color: #7f8c8d; text-align: center;">Aucune donnée disponible</p>';
            return;
        }

        // Sort attempts by date
        const sortedAttempts = [...attempts].sort((a, b) => {
            const dateA = new Date(a.timestamp || a.date_tentative || a.submission_date || 0);
            const dateB = new Date(b.timestamp || b.date_tentative || b.submission_date || 0);
            return dateA.getTime() - dateB.getTime();
        });

        // Calculate cumulative success rate
        let successCount = 0;
        const dataPoints = sortedAttempts.map((attempt, index) => {
            if (isAttemptSuccessful(attempt)) successCount++;
            return {
                index: index + 1,
                successRate: (successCount / (index + 1)) * 100,
                date: attempt.timestamp || attempt.date_tentative,
                isCorrect: isAttemptSuccessful(attempt)
            };
        });

        const margin = {top: 20, right: 30, bottom: 40, left: 50};
        const viewBoxWidth = 500;
        const viewBoxHeight = 300;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = viewBoxHeight - margin.top - margin.bottom;

        const svg = d3.select(chartDiv)
            .append('svg')
            .attr('viewBox', `0 0 ${viewBoxWidth} ${viewBoxHeight}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

        // Scales
        const x = d3.scaleLinear()
            .domain([1, dataPoints.length])
            .range([0, width]);

        const y = d3.scaleLinear()
            .domain([0, 100])
            .range([height, 0]);

        // X Axis
        svg.append('g')
            .attr('transform', `translate(0,${height})`)
            .call(d3.axisBottom(x).ticks(Math.min(10, dataPoints.length)));

        // Y Axis
        svg.append('g')
            .call(d3.axisLeft(y).ticks(5));

        // Grid lines
        svg.append('g')
            .attr('class', 'grid')
            .attr('opacity', 0.1)
            .call(d3.axisLeft(y).ticks(5).tickSize(-width).tickFormat(''));

        // Line
        const line = d3.line()
            .x(d => x(d.index))
            .y(d => y(d.successRate))
            .curve(d3.curveMonotoneX);

        svg.append('path')
            .datum(dataPoints)
            .attr('fill', 'none')
            .attr('stroke', '#3498db')
            .attr('stroke-width', 2)
            .attr('d', line);

        // Points
        svg.selectAll('.dot')
            .data(dataPoints)
            .enter()
            .append('circle')
            .attr('class', 'dot')
            .attr('cx', d => x(d.index))
            .attr('cy', d => y(d.successRate))
            .attr('r', 4)
            .attr('fill', d => d.isCorrect ? '#27ae60' : '#e74c3c')
            .attr('stroke', 'white')
            .attr('stroke-width', 2)
            .style('cursor', 'pointer')
            .on('mouseover', function(event, d) {
                d3.select(this).attr('r', 6);
                const tooltip = d3.select(chartDiv)
                    .append('div')
                    .attr('class', 'chart-tooltip')
                    .style('position', 'absolute')
                    .style('background', 'rgba(0,0,0,0.8)')
                    .style('color', 'white')
                    .style('padding', '8px')
                    .style('border-radius', '4px')
                    .style('font-size', '12px')
                    .style('pointer-events', 'none')
                    .html(`Tentative #${d.index}<br>Taux: ${d.successRate.toFixed(1)}%<br>${d.isCorrect ? '✓ Réussi' : '✗ Échoué'}`);
            })
            .on('mouseout', function() {
                d3.select(this).attr('r', 4);
                d3.select(chartDiv).selectAll('.chart-tooltip').remove();
            });

        // Legend - positioned above the chart to avoid overlap
        const legend = svg.append('g')
            .attr('class', 'legend')
            .attr('transform', `translate(10, -10)`);

        // Success legend
        legend.append('circle')
            .attr('cx', 0)
            .attr('cy', 0)
            .attr('r', 5)
            .attr('fill', '#27ae60')
            .attr('stroke', 'white')
            .attr('stroke-width', 2);

        legend.append('text')
            .attr('x', 12)
            .attr('y', 4)
            .style('font-size', '11px')
            .style('fill', '#2c3e50')
            .text('Réussi');

        // Failure legend
        legend.append('circle')
            .attr('cx', 70)
            .attr('cy', 0)
            .attr('r', 5)
            .attr('fill', '#e74c3c')
            .attr('stroke', 'white')
            .attr('stroke-width', 2);

        legend.append('text')
            .attr('x', 82)
            .attr('y', 4)
            .style('font-size', '11px')
            .style('fill', '#2c3e50')
            .text('Échoué');

        // Trend line legend
        legend.append('line')
            .attr('x1', 140)
            .attr('y1', 0)
            .attr('x2', 160)
            .attr('y2', 0)
            .attr('stroke', '#3498db')
            .attr('stroke-width', 2);

        legend.append('text')
            .attr('x', 165)
            .attr('y', 4)
            .style('font-size', '11px')
            .style('fill', '#2c3e50')
            .text('Tendance');

        // Axis Labels
        svg.append('text')
            .attr('x', width / 2)
            .attr('y', height + 35)
            .attr('text-anchor', 'middle')
            .style('font-size', '12px')
            .style('fill', '#7f8c8d')
            .text('Numéro de tentative');

        svg.append('text')
            .attr('transform', 'rotate(-90)')
            .attr('x', -height / 2)
            .attr('y', -35)
            .attr('text-anchor', 'middle')
            .style('font-size', '12px')
            .style('fill', '#7f8c8d')
            .text('Taux de réussite (%)');
    }

    /**
     * Render success rate by exercise bar chart
     */
    function renderSuccessRateByExercise(attempts, parentContainer) {
        const chartDiv = document.createElement('div');
        chartDiv.className = 'chart-container';
        chartDiv.style.cssText = 'background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        parentContainer.appendChild(chartDiv);

        const title = document.createElement('h3');
        title.textContent = 'Réussite par exercice';
        title.style.cssText = 'margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;';
        chartDiv.appendChild(title);

        if (!attempts || attempts.length === 0) {
            chartDiv.innerHTML += '<p style="color: #7f8c8d; text-align: center;">Aucune donnée disponible</p>';
            return;
        }

        // Group by exercise
        const exerciseStats = {};
        attempts.forEach(attempt => {
            const exName = attempt.funcname || attempt.exo_name || 'Unknown';
            if (!exerciseStats[exName]) {
                exerciseStats[exName] = { total: 0, success: 0 };
            }
            exerciseStats[exName].total++;
            if (isAttemptSuccessful(attempt)) {
                exerciseStats[exName].success++;
            }
        });

        const data = Object.keys(exerciseStats).map(key => ({
            exercise: key,
            successRate: (exerciseStats[key].success / exerciseStats[key].total) * 100,
            total: exerciseStats[key].total
        })).sort((a, b) => b.successRate - a.successRate);

        const margin = {top: 20, right: 30, bottom: 80, left: 50};
        const viewBoxWidth = 500;
        const viewBoxHeight = 300;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = viewBoxHeight - margin.top - margin.bottom;

        const svg = d3.select(chartDiv)
            .append('svg')
            .attr('viewBox', `0 0 ${viewBoxWidth} ${viewBoxHeight}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand()
            .domain(data.map(d => d.exercise))
            .range([0, width])
            .padding(0.2);

        const y = d3.scaleLinear()
            .domain([0, 100])
            .range([height, 0]);

        // Color scale
        const colorScale = d3.scaleThreshold()
            .domain([30, 70])
            .range(['#e74c3c', '#f39c12', '#27ae60']);

        svg.append('g')
            .attr('transform', `translate(0,${height})`)
            .call(d3.axisBottom(x))
            .selectAll('text')
            .attr('transform', 'rotate(-45)')
            .style('text-anchor', 'end')
            .style('font-size', '10px');

        svg.append('g')
            .call(d3.axisLeft(y).ticks(5));

        svg.selectAll('.bar')
            .data(data)
            .enter()
            .append('rect')
            .attr('class', 'bar')
            .attr('x', d => x(d.exercise))
            .attr('y', d => y(d.successRate))
            .attr('width', x.bandwidth())
            .attr('height', d => height - y(d.successRate))
            .attr('fill', d => colorScale(d.successRate))
            .style('cursor', 'pointer')
            .on('mouseover', function(event, d) {
                d3.select(this).attr('opacity', 0.7);
            })
            .on('mouseout', function() {
                d3.select(this).attr('opacity', 1);
            });

        // Legend - positioned above the chart area in horizontal layout
        const legend = svg.append('g')
            .attr('class', 'legend')
            .attr('transform', `translate(10, -10)`);

        const legendData = [
            { color: '#27ae60', label: 'Bon (>70%)' },
            { color: '#f39c12', label: 'Moyen (30-70%)' },
            { color: '#e74c3c', label: 'Faible (<30%)' }
        ];

        legendData.forEach((item, i) => {
            const xOffset = i * 120;

            legend.append('rect')
                .attr('x', xOffset)
                .attr('y', 0)
                .attr('width', 12)
                .attr('height', 12)
                .attr('fill', item.color)
                .attr('rx', 2);

            legend.append('text')
                .attr('x', xOffset + 18)
                .attr('y', 10)
                .style('font-size', '10px')
                .style('fill', '#2c3e50')
                .text(item.label);
        });

        // Axis labels
        svg.append('text')
            .attr('x', width / 2)
            .attr('y', height + margin.bottom - 5)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Exercices');

        svg.append('text')
            .attr('transform', 'rotate(-90)')
            .attr('x', -height / 2)
            .attr('y', -35)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Taux de réussite (%)');
    }

    /**
     * Render attempts distribution pie chart
     */
    function renderAttemptsDistribution(stats, parentContainer) {
        const chartDiv = document.createElement('div');
        chartDiv.className = 'chart-container';
        chartDiv.style.cssText = 'background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        parentContainer.appendChild(chartDiv);

        const title = document.createElement('h3');
        title.textContent = 'Distribution des tentatives';
        title.style.cssText = 'margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;';
        chartDiv.appendChild(title);

        if (!stats || !stats.total_attempts || stats.total_attempts === 0) {
            chartDiv.innerHTML += '<p style="color: #7f8c8d; text-align: center;">Aucune donnée disponible</p>';
            return;
        }

        const data = [
            { label: 'Réussies', value: stats.correct_attempts || stats.success_count || 0, color: '#27ae60' },
            { label: 'Échouées', value: (stats.total_attempts || 0) - (stats.correct_attempts || stats.success_count || 0), color: '#e74c3c' }
        ];

        const viewBoxSize = 300;
        const radius = Math.min(viewBoxSize, viewBoxSize) / 2 - 40; // Back to normal size

        const svg = d3.select(chartDiv)
            .append('svg')
            .attr('viewBox', `0 0 ${viewBoxSize} ${viewBoxSize}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${viewBoxSize/2},${viewBoxSize/2})`);

        const pie = d3.pie()
            .value(d => d.value)
            .sort(null);

        const arc = d3.arc()
            .innerRadius(radius * 0.5)
            .outerRadius(radius);

        const arcs = svg.selectAll('.arc')
            .data(pie(data))
            .enter()
            .append('g')
            .attr('class', 'arc');

        arcs.append('path')
            .attr('d', arc)
            .attr('fill', d => d.data.color)
            .attr('stroke', 'white')
            .attr('stroke-width', 2)
            .style('cursor', 'pointer')
            .on('mouseover', function(event, d) {
                d3.select(this).transition().duration(200)
                    .attr('d', d3.arc().innerRadius(radius * 0.5).outerRadius(radius * 1.1));
            })
            .on('mouseout', function() {
                d3.select(this).transition().duration(200)
                    .attr('d', arc);
            });

        arcs.append('text')
            .attr('transform', d => `translate(${arc.centroid(d)})`)
            .attr('text-anchor', 'middle')
            .style('font-size', '14px')
            .style('font-weight', 'bold')
            .style('fill', 'white')
            .text(d => d.data.value > 0 ? d.data.value : '');

        // Legend with percentages - positioned bottom left with absolute coordinates
        const total = data.reduce((sum, d) => sum + d.value, 0);
        const legend = svg.selectAll('.legend')
            .data(data)
            .enter()
            .append('g')
            .attr('class', 'legend')
            .attr('transform', (d, i) => `translate(${-viewBoxSize/2 + 20},${viewBoxSize/2 - 50 + i * 30})`); // Moved down by 20px

        legend.append('rect')
            .attr('width', 18)
            .attr('height', 18)
            .attr('fill', d => d.color)
            .attr('rx', 2);

        legend.append('text')
            .attr('x', 24)
            .attr('y', 9)
            .attr('dy', '.35em')
            .style('font-size', '12px')
            .style('font-weight', '600')
            .style('fill', '#2c3e50')
            .text(d => d.label);

        legend.append('text')
            .attr('x', 24)
            .attr('y', 22)
            .style('font-size', '10px')
            .style('fill', '#7f8c8d')
            .text(d => {
                const percentage = total > 0 ? ((d.value / total) * 100).toFixed(1) : 0;
                return `${d.value} (${percentage}%)`;
            });
    }

    /**
     * Render time spent analysis
     */
    function renderTimeSpentAnalysis(attempts, parentContainer) {
        const chartDiv = document.createElement('div');
        chartDiv.className = 'chart-container';
        chartDiv.style.cssText = 'background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        parentContainer.appendChild(chartDiv);

        const title = document.createElement('h3');
        title.textContent = 'Tentatives au fil du temps';
        title.style.cssText = 'margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;';
        chartDiv.appendChild(title);

        if (!attempts || attempts.length === 0) {
            chartDiv.innerHTML += '<p style="color: #7f8c8d; text-align: center;">Aucune donnée disponible</p>';
            return;
        }

        // Group by date
        const dateGroups = {};
        attempts.forEach(attempt => {
            const dateStr = attempt.timestamp || attempt.date_tentative || attempt.submission_date;
            if (!dateStr) return;

            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return; // Skip invalid dates

            const dateKey = date.toISOString().split('T')[0];
            if (!dateGroups[dateKey]) {
                dateGroups[dateKey] = 0;
            }
            dateGroups[dateKey]++;
        });

        const data = Object.keys(dateGroups).sort().map(key => ({
            date: key,
            count: dateGroups[key]
        }));

        const margin = {top: 20, right: 30, bottom: 60, left: 50};
        const viewBoxWidth = 500;
        const viewBoxHeight = 300;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = viewBoxHeight - margin.top - margin.bottom;

        const svg = d3.select(chartDiv)
            .append('svg')
            .attr('viewBox', `0 0 ${viewBoxWidth} ${viewBoxHeight}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand()
            .domain(data.map(d => d.date))
            .range([0, width])
            .padding(0.2);

        const y = d3.scaleLinear()
            .domain([0, d3.max(data, d => d.count)])
            .range([height, 0]);

        svg.append('g')
            .attr('transform', `translate(0,${height})`)
            .call(d3.axisBottom(x))
            .selectAll('text')
            .attr('transform', 'rotate(-45)')
            .style('text-anchor', 'end')
            .style('font-size', '10px');

        svg.append('g')
            .call(d3.axisLeft(y).ticks(5));

        svg.selectAll('.bar')
            .data(data)
            .enter()
            .append('rect')
            .attr('x', d => x(d.date))
            .attr('y', d => y(d.count))
            .attr('width', x.bandwidth())
            .attr('height', d => height - y(d.count))
            .attr('fill', '#3498db')
            .style('cursor', 'pointer')
            .on('mouseover', function(event, d) {
                d3.select(this).attr('fill', '#2980b9');
            })
            .on('mouseout', function() {
                d3.select(this).attr('fill', '#3498db');
            });

        // Summary statistics with compact layout
        const totalAttempts = data.reduce((sum, d) => sum + d.count, 0);
        const avgPerDay = (totalAttempts / data.length).toFixed(1);
        const maxDay = data.reduce((max, d) => d.count > max.count ? d : max, data[0]);

        // Legend with statistics - positioned above chart area
        const legend = svg.append('g')
            .attr('class', 'legend')
            .attr('transform', `translate(10, -10)`);

        legend.append('rect')
            .attr('x', 0)
            .attr('y', 0)
            .attr('width', 12)
            .attr('height', 12)
            .attr('fill', '#3498db')
            .attr('rx', 2);

        legend.append('text')
            .attr('x', 18)
            .attr('y', 10)
            .style('font-size', '11px')
            .style('fill', '#2c3e50')
            .style('font-weight', '500')
            .text('Tentatives');

        legend.append('text')
            .attr('x', 100)
            .attr('y', 10)
            .style('font-size', '10px')
            .style('fill', '#7f8c8d')
            .text(`Total: ${totalAttempts} | Moy/jour: ${avgPerDay} | Max: ${maxDay ? maxDay.count : 0}`);

        // Axis labels
        svg.append('text')
            .attr('x', width / 2)
            .attr('y', height + margin.bottom - 5)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Date');

        svg.append('text')
            .attr('transform', 'rotate(-90)')
            .attr('x', -height / 2)
            .attr('y', -35)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Nombre de tentatives');
    }

    /**
     * Render detailed exercise performance charts
     * @param {Object} exercise - Exercise data
     * @param {Array} students - Students who attempted the exercise
     * @param {string} containerId - ID of the container element
     */
    function renderExerciseDetailedCharts(exercise, students, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '';
        container.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; padding: 1rem;';

        // Chart 1: Student Success Rate Distribution
        renderStudentSuccessDistribution(students, container);

        // Chart 2: Attempts per Student
        renderAttemptsPerStudent(students, container);

        // Chart 3: Success Timeline
        renderExerciseSuccessTimeline(students, container);
    }

    /**
     * Render student success distribution for an exercise
     */
    function renderStudentSuccessDistribution(students, parentContainer) {
        const chartDiv = document.createElement('div');
        chartDiv.className = 'chart-container';
        chartDiv.style.cssText = 'background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        parentContainer.appendChild(chartDiv);

        const title = document.createElement('h3');
        title.textContent = 'Distribution des réussites';
        title.style.cssText = 'margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;';
        chartDiv.appendChild(title);

        if (!students || students.length === 0) {
            chartDiv.innerHTML += '<p style="color: #7f8c8d; text-align: center;">Aucune donnée disponible</p>';
            return;
        }

        // Calculate distribution
        const distribution = {
            'Aucune réussite': 0,
            '1-3 tentatives': 0,
            '4-6 tentatives': 0,
            '7+ tentatives': 0
        };

        students.forEach(student => {
            const successAttempts = (student.attempts || []).filter(a => isAttemptSuccessful(a)).length;
            if (successAttempts === 0) {
                distribution['Aucune réussite']++;
            } else if (successAttempts <= 3) {
                distribution['1-3 tentatives']++;
            } else if (successAttempts <= 6) {
                distribution['4-6 tentatives']++;
            } else {
                distribution['7+ tentatives']++;
            }
        });

        const data = Object.keys(distribution).map(key => ({
            category: key,
            count: distribution[key]
        }));

        const margin = {top: 20, right: 30, bottom: 60, left: 50};
        const viewBoxWidth = 500;
        const viewBoxHeight = 300;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = viewBoxHeight - margin.top - margin.bottom;

        const svg = d3.select(chartDiv)
            .append('svg')
            .attr('viewBox', `0 0 ${viewBoxWidth} ${viewBoxHeight}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand()
            .domain(data.map(d => d.category))
            .range([0, width])
            .padding(0.2);

        const y = d3.scaleLinear()
            .domain([0, d3.max(data, d => d.count)])
            .range([height, 0]);

        const colors = ['#e74c3c', '#f39c12', '#3498db', '#27ae60'];

        svg.append('g')
            .attr('transform', `translate(0,${height})`)
            .call(d3.axisBottom(x))
            .selectAll('text')
            .attr('transform', 'rotate(-45)')
            .style('text-anchor', 'end')
            .style('font-size', '10px');

        svg.append('g')
            .call(d3.axisLeft(y).ticks(5));

        svg.selectAll('.bar')
            .data(data)
            .enter()
            .append('rect')
            .attr('x', d => x(d.category))
            .attr('y', d => y(d.count))
            .attr('width', x.bandwidth())
            .attr('height', d => height - y(d.count))
            .attr('fill', (d, i) => colors[i])
            .style('cursor', 'pointer');

        // Legend - positioned horizontally above the chart
        const legend = svg.append('g')
            .attr('class', 'legend')
            .attr('transform', `translate(10, -10)`);

        const legendItems = [
            { color: '#e74c3c', label: 'Aucune' },
            { color: '#f39c12', label: '1-3' },
            { color: '#3498db', label: '4-6' },
            { color: '#27ae60', label: '7+' }
        ];

        legendItems.forEach((item, i) => {
            const xOffset = i * 80;

            legend.append('rect')
                .attr('x', xOffset)
                .attr('y', 0)
                .attr('width', 12)
                .attr('height', 12)
                .attr('fill', item.color)
                .attr('rx', 2);

            legend.append('text')
                .attr('x', xOffset + 16)
                .attr('y', 10)
                .style('font-size', '10px')
                .style('fill', '#2c3e50')
                .text(item.label);
        });

        // Axis labels
        svg.append('text')
            .attr('x', width / 2)
            .attr('y', height + margin.bottom - 5)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Catégories de réussite');

        svg.append('text')
            .attr('transform', 'rotate(-90)')
            .attr('x', -height / 2)
            .attr('y', -35)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Nombre d\'étudiants');
    }

    /**
     * Render attempts per student
     */
    function renderAttemptsPerStudent(students, parentContainer) {
        const chartDiv = document.createElement('div');
        chartDiv.className = 'chart-container';
        chartDiv.style.cssText = 'background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        parentContainer.appendChild(chartDiv);

        const title = document.createElement('h3');
        title.textContent = 'Tentatives par étudiant';
        title.style.cssText = 'margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;';
        chartDiv.appendChild(title);

        if (!students || students.length === 0) {
            chartDiv.innerHTML += '<p style="color: #7f8c8d; text-align: center;">Aucune donnée disponible</p>';
            return;
        }

        const data = students.slice(0, 10).map(student => ({
            student: student.student_identifier || `Étudiant ${student.student_id}`,
            attempts: (student.attempts || []).length,
            success: (student.attempts || []).filter(a => isAttemptSuccessful(a)).length
        }));

        const margin = {top: 20, right: 30, bottom: 80, left: 50};
        const viewBoxWidth = 500;
        const viewBoxHeight = 300;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = viewBoxHeight - margin.top - margin.bottom;

        const svg = d3.select(chartDiv)
            .append('svg')
            .attr('viewBox', `0 0 ${viewBoxWidth} ${viewBoxHeight}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand()
            .domain(data.map(d => d.student))
            .range([0, width])
            .padding(0.2);

        const y = d3.scaleLinear()
            .domain([0, d3.max(data, d => d.attempts)])
            .range([height, 0]);

        svg.append('g')
            .attr('transform', `translate(0,${height})`)
            .call(d3.axisBottom(x))
            .selectAll('text')
            .attr('transform', 'rotate(-45)')
            .style('text-anchor', 'end')
            .style('font-size', '10px');

        svg.append('g')
            .call(d3.axisLeft(y).ticks(5));

        // Total attempts bars
        svg.selectAll('.bar-total')
            .data(data)
            .enter()
            .append('rect')
            .attr('x', d => x(d.student))
            .attr('y', d => y(d.attempts))
            .attr('width', x.bandwidth())
            .attr('height', d => height - y(d.attempts))
            .attr('fill', '#bdc3c7')
            .style('cursor', 'pointer');

        // Success attempts bars
        svg.selectAll('.bar-success')
            .data(data)
            .enter()
            .append('rect')
            .attr('x', d => x(d.student))
            .attr('y', d => y(d.success))
            .attr('width', x.bandwidth())
            .attr('height', d => height - y(d.success))
            .attr('fill', '#27ae60')
            .style('cursor', 'pointer');

        // Legend - positioned horizontally above the chart
        const legend = svg.append('g')
            .attr('class', 'legend')
            .attr('transform', `translate(10, -10)`);

        legend.append('rect')
            .attr('x', 0)
            .attr('y', 0)
            .attr('width', 12)
            .attr('height', 12)
            .attr('fill', '#bdc3c7')
            .attr('rx', 2);

        legend.append('text')
            .attr('x', 18)
            .attr('y', 10)
            .style('font-size', '10px')
            .style('fill', '#2c3e50')
            .text('Total tentatives');

        legend.append('rect')
            .attr('x', 120)
            .attr('y', 0)
            .attr('width', 12)
            .attr('height', 12)
            .attr('fill', '#27ae60')
            .attr('rx', 2);

        legend.append('text')
            .attr('x', 138)
            .attr('y', 10)
            .style('font-size', '10px')
            .style('fill', '#2c3e50')
            .text('Réussites');

        // Axis labels
        svg.append('text')
            .attr('x', width / 2)
            .attr('y', height + margin.bottom - 5)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Étudiants (Top 10)');

        svg.append('text')
            .attr('transform', 'rotate(-90)')
            .attr('x', -height / 2)
            .attr('y', -35)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Nombre de tentatives');
    }

    /**
     * Render exercise success timeline
     */
    function renderExerciseSuccessTimeline(students, parentContainer) {
        const chartDiv = document.createElement('div');
        chartDiv.className = 'chart-container';
        chartDiv.style.cssText = 'background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); grid-column: 1 / -1;';
        parentContainer.appendChild(chartDiv);

        const title = document.createElement('h3');
        title.textContent = 'Chronologie des succès';
        title.style.cssText = 'margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;';
        chartDiv.appendChild(title);

        if (!students || students.length === 0) {
            chartDiv.innerHTML += '<p style="color: #7f8c8d; text-align: center;">Aucune donnée disponible</p>';
            return;
        }

        // Collect all attempts with timestamps
        const allAttempts = [];
        students.forEach(student => {
            (student.attempts || []).forEach(attempt => {
                const dateStr = attempt.timestamp || attempt.date_tentative || attempt.submission_date;
                if (!dateStr) return;

                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return; // Skip invalid dates

                allAttempts.push({
                    date: date,
                    success: isAttemptSuccessful(attempt),
                    student: student.student_identifier || `Étudiant ${student.student_id}`
                });
            });
        });

        allAttempts.sort((a, b) => a.date - b.date);

        // Group by day and count success/fail
        const dayGroups = {};
        allAttempts.forEach(attempt => {
            const dateKey = attempt.date.toISOString().split('T')[0];
            if (!dayGroups[dateKey]) {
                dayGroups[dateKey] = { success: 0, fail: 0 };
            }
            if (attempt.success) {
                dayGroups[dateKey].success++;
            } else {
                dayGroups[dateKey].fail++;
            }
        });

        const data = Object.keys(dayGroups).sort().map(key => ({
            date: key,
            success: dayGroups[key].success,
            fail: dayGroups[key].fail
        }));

        const margin = {top: 20, right: 100, bottom: 60, left: 50};
        const viewBoxWidth = 1000;
        const viewBoxHeight = 300;
        const width = viewBoxWidth - margin.left - margin.right;
        const height = viewBoxHeight - margin.top - margin.bottom;

        const svg = d3.select(chartDiv)
            .append('svg')
            .attr('viewBox', `0 0 ${viewBoxWidth} ${viewBoxHeight}`)
            .attr('preserveAspectRatio', 'xMidYMid meet')
            .style('width', '100%')
            .style('height', 'auto')
            .append('g')
            .attr('transform', `translate(${margin.left},${margin.top})`);

        const x = d3.scaleBand()
            .domain(data.map(d => d.date))
            .range([0, width])
            .padding(0.2);

        const y = d3.scaleLinear()
            .domain([0, d3.max(data, d => d.success + d.fail)])
            .range([height, 0]);

        svg.append('g')
            .attr('transform', `translate(0,${height})`)
            .call(d3.axisBottom(x).tickValues(x.domain().filter((d, i) => i % Math.ceil(data.length / 10) === 0)))
            .selectAll('text')
            .attr('transform', 'rotate(-45)')
            .style('text-anchor', 'end')
            .style('font-size', '10px');

        svg.append('g')
            .call(d3.axisLeft(y).ticks(5));

        // Stacked bars
        const stack = d3.stack()
            .keys(['success', 'fail']);

        const series = stack(data);

        const color = d3.scaleOrdinal()
            .domain(['success', 'fail'])
            .range(['#27ae60', '#e74c3c']);

        svg.selectAll('.series')
            .data(series)
            .enter()
            .append('g')
            .attr('fill', d => color(d.key))
            .selectAll('rect')
            .data(d => d)
            .enter()
            .append('rect')
            .attr('x', d => x(d.data.date))
            .attr('y', d => y(d[1]))
            .attr('height', d => y(d[0]) - y(d[1]))
            .attr('width', x.bandwidth())
            .style('cursor', 'pointer');

        // Legend with improved styling
        const legend = svg.append('g')
            .attr('transform', `translate(${width + 20}, 10)`);

        const legendData = [
            { label: 'Réussites', color: '#27ae60' },
            { label: 'Échecs', color: '#e74c3c' }
        ];

        legendData.forEach((item, i) => {
            const g = legend.append('g')
                .attr('transform', `translate(0, ${i * 25})`);

            g.append('rect')
                .attr('width', 14)
                .attr('height', 14)
                .attr('fill', item.color)
                .attr('rx', 2);

            g.append('text')
                .attr('x', 20)
                .attr('y', 7)
                .attr('dy', '.35em')
                .style('font-size', '11px')
                .style('fill', '#2c3e50')
                .style('font-weight', '600')
                .text(item.label);
        });

        // Total statistics
        const totalSuccess = data.reduce((sum, d) => sum + d.success, 0);
        const totalFail = data.reduce((sum, d) => sum + d.fail, 0);
        const total = totalSuccess + totalFail;

        legend.append('line')
            .attr('x1', 0)
            .attr('y1', 60)
            .attr('x2', 60)
            .attr('y2', 60)
            .attr('stroke', '#bdc3c7')
            .attr('stroke-width', 1);

        legend.append('text')
            .attr('x', 0)
            .attr('y', 75)
            .style('font-size', '10px')
            .style('fill', '#7f8c8d')
            .text(`Total: ${total}`);

        legend.append('text')
            .attr('x', 0)
            .attr('y', 90)
            .style('font-size', '10px')
            .style('fill', '#27ae60')
            .text(`✓ ${totalSuccess}`);

        legend.append('text')
            .attr('x', 0)
            .attr('y', 105)
            .style('font-size', '10px')
            .style('fill', '#e74c3c')
            .text(`✗ ${totalFail}`);

        // Axis labels
        svg.append('text')
            .attr('x', width / 2)
            .attr('y', height + margin.bottom - 5)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Date');

        svg.append('text')
            .attr('transform', 'rotate(-90)')
            .attr('x', -height / 2)
            .attr('y', -35)
            .attr('text-anchor', 'middle')
            .style('font-size', '11px')
            .style('fill', '#7f8c8d')
            .text('Nombre de tentatives');
    }

    /**
     * Render difficulty analysis
     */
    function renderDifficultyAnalysis(students, parentContainer) {
        const chartDiv = document.createElement('div');
        chartDiv.className = 'chart-container';
        chartDiv.style.cssText = 'background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
        parentContainer.appendChild(chartDiv);

        const title = document.createElement('h3');
        title.textContent = 'Analyse de difficulté';
        title.style.cssText = 'margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;';
        chartDiv.appendChild(title);

        if (!students || students.length === 0) {
            chartDiv.innerHTML += '<p style="color: #7f8c8d; text-align: center;">Aucune donnée disponible</p>';
            return;
        }

        // Calculate metrics
        const totalStudents = students.length;
        const studentsWithSuccess = students.filter(s =>
            (s.attempts || []).some(a => isAttemptSuccessful(a))
        ).length;
        const avgAttempts = students.reduce((sum, s) => sum + (s.attempts || []).length, 0) / totalStudents;
        const successRate = (studentsWithSuccess / totalStudents) * 100;

        // Display metrics
        const metricsDiv = document.createElement('div');
        metricsDiv.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;';

        const metrics = [
            { label: 'Taux de réussite', value: `${successRate.toFixed(1)}%`, color: successRate > 70 ? '#27ae60' : successRate > 40 ? '#f39c12' : '#e74c3c' },
            { label: 'Moyenne de tentatives', value: avgAttempts.toFixed(1), color: avgAttempts < 5 ? '#27ae60' : avgAttempts < 10 ? '#f39c12' : '#e74c3c' },
            { label: 'Étudiants ayant réussi', value: `${studentsWithSuccess}/${totalStudents}`, color: '#3498db' },
            { label: 'Difficulté estimée', value: successRate > 70 ? 'Facile' : successRate > 40 ? 'Moyen' : 'Difficile', color: successRate > 70 ? '#27ae60' : successRate > 40 ? '#f39c12' : '#e74c3c' }
        ];

        metrics.forEach(metric => {
            const metricCard = document.createElement('div');
            metricCard.style.cssText = `
                padding: 1rem;
                background: ${metric.color}15;
                border-left: 4px solid ${metric.color};
                border-radius: 4px;
            `;
            metricCard.innerHTML = `
                <div style="font-size: 0.85rem; color: #7f8c8d; margin-bottom: 0.5rem;">${metric.label}</div>
                <div style="font-size: 1.5rem; font-weight: bold; color: ${metric.color};">${metric.value}</div>
            `;
            metricsDiv.appendChild(metricCard);
        });

        chartDiv.appendChild(metricsDiv);
    }

    // Public API
    return {
        renderStudentDetailedCharts: renderStudentDetailedCharts,
        renderExerciseDetailedCharts: renderExerciseDetailedCharts
    };
})();

// Make available globally using globalThis (modern approach)
(function() {
    try {
        if (typeof globalThis !== 'undefined') {
            globalThis.DetailedCharts = DetailedCharts;
        } else if (typeof window !== 'undefined') {
            window['DetailedCharts'] = DetailedCharts;
        } else if (typeof global !== 'undefined') {
            global.DetailedCharts = DetailedCharts;
        }
    } catch (e) {
        console.warn('Could not assign DetailedCharts to global scope:', e);
    }
})();

