// Module de gestion des onglets

export class TabManager {
    constructor() {
        this.currentTab = 'raw';
    }

    createTabs() {
        const tabsContainer = document.createElement('div');
        tabsContainer.className = 'tabs-container';
        tabsContainer.style.marginBottom = '2rem';
        tabsContainer.style.borderBottom = '2px solid #e0e0e0';
        tabsContainer.style.display = 'flex';
        tabsContainer.style.gap = '0.5rem';

        const rawDataTab = this.createTabButton('📊 Données brutes', 'raw', true);
        const visualizationTab = this.createTabButton('📈 Visualisation', 'visualization', false);

        tabsContainer.appendChild(rawDataTab);
        tabsContainer.appendChild(visualizationTab);

        return tabsContainer;
    }

    createTabButton(text, tabName, isActive) {
        const button = document.createElement('button');
        button.className = isActive ? 'tab-button active' : 'tab-button';
        button.textContent = text;
        button.onclick = () => this.switchTab(tabName);

        const styles = {
            padding: '0.75rem 1.5rem',
            border: 'none',
            background: 'transparent',
            cursor: 'pointer',
            fontSize: '1rem',
            fontWeight: '600',
            color: isActive ? '#3498db' : '#7f8c8d',
            borderBottom: isActive ? '3px solid #3498db' : '3px solid transparent',
            transition: 'all 0.3s ease'
        };

        Object.assign(button.style, styles);
        return button;
    }

    switchTab(tabName) {
        this.currentTab = tabName;

        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach((button) => {
            button.classList.remove('active');
            button.style.color = '#7f8c8d';
            button.style.borderBottom = '3px solid transparent';
        });

        const activeButton = tabButtons[tabName === 'raw' ? 0 : 1];
        if (activeButton) {
            activeButton.classList.add('active');
            activeButton.style.color = '#3498db';
            activeButton.style.borderBottom = '3px solid #3498db';
        }

        const rawContent = document.getElementById('raw-data-content');
        const vizContent = document.getElementById('visualization-content');

        if (tabName === 'raw') {
            if (rawContent) {
                rawContent.style.display = 'block';
                rawContent.classList.add('active');
            }
            if (vizContent) {
                vizContent.style.display = 'none';
                vizContent.classList.remove('active');
            }
        } else {
            if (rawContent) {
                rawContent.style.display = 'none';
                rawContent.classList.remove('active');
            }
            if (vizContent) {
                vizContent.style.display = 'block';
                vizContent.classList.add('active');

                // Render charts on first activation
                const chartsContainer = document.getElementById('student-charts-container');
                if (chartsContainer && chartsContainer.dataset.rendered === 'false') {
                    chartsContainer.dataset.rendered = 'true';

                    try {
                        const student = JSON.parse(chartsContainer.dataset.student);
                        const attempts = JSON.parse(chartsContainer.dataset.attempts);
                        const stats = JSON.parse(chartsContainer.dataset.stats);

                        if (typeof window.DetailedCharts !== 'undefined') {
                            window.DetailedCharts.renderStudentDetailedCharts(
                                student,
                                attempts,
                                stats,
                                'student-charts-container'
                            );
                        } else {
                            chartsContainer.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 2rem;">Erreur: Module de graphiques non chargé</p>';
                        }
                    } catch (e) {
                        console.error('Erreur lors du rendu des graphiques:', e);
                        chartsContainer.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 2rem;">Erreur lors du chargement des graphiques</p>';
                    }
                }
            }
        }
    }
}

