// Module de rendu des statistiques

export class StatsRenderer {
    renderStats(stats) {
        const statsDiv = document.createElement('div');
        statsDiv.className = 'student-stats';
        statsDiv.style.display = 'grid';
        statsDiv.style.gridTemplateColumns = 'repeat(auto-fit, minmax(200px, 1fr))';
        statsDiv.style.gap = '1rem';
        statsDiv.style.marginBottom = '2rem';

        const statCards = [
            { label: 'Tentatives totales', value: stats.total_attempts, color: '#3498db' },
            { label: 'Tentatives réussies', value: stats.correct_attempts, color: '#2ecc71' },
            { label: 'Taux de réussite', value: stats.success_rate + '%', color: '#e74c3c' },
            { label: 'Exercices uniques', value: stats.unique_exercises, color: '#f39c12' }
        ];

        statCards.forEach(stat => {
            const card = this.createStatCard(stat);
            statsDiv.appendChild(card);
        });

        return statsDiv;
    }

    createStatCard({ label, value, color }) {
        const card = document.createElement('div');
        const styles = {
            background: '#f8f9fa',
            padding: '1.5rem',
            borderRadius: '8px',
            borderLeft: `4px solid ${color}`,
            boxShadow: '0 2px 4px rgba(0,0,0,0.1)'
        };
        Object.assign(card.style, styles);

        const valueEl = document.createElement('div');
        Object.assign(valueEl.style, {
            fontSize: '2rem',
            fontWeight: 'bold',
            color: color,
            marginBottom: '0.5rem'
        });
        valueEl.textContent = value;

        const labelEl = document.createElement('div');
        Object.assign(labelEl.style, {
            color: '#7f8c8d',
            fontSize: '0.9rem'
        });
        labelEl.textContent = label;

        card.appendChild(valueEl);
        card.appendChild(labelEl);

        return card;
    }
}

