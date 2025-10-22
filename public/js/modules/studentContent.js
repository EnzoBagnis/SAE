// Module de gestion du contenu des étudiants

import { TabManager } from './tabManager.js';
import { StatsRenderer } from './statsRenderer.js';
import { AttemptsRenderer } from './attemptsRenderer.js';

export class StudentContentManager {
    constructor() {
        this.currentStudentId = null;
        this.tabManager = new TabManager();
        this.statsRenderer = new StatsRenderer();
        this.attemptsRenderer = new AttemptsRenderer();
    }

    // Sélectionner un étudiant
    selectStudent(studentId) {
        this.currentStudentId = studentId;

        // Mettre à jour l'état actif dans la sidebar
        document.querySelectorAll('.student-item').forEach((item) => {
            item.classList.remove('active');
        });

        const selectedItem = document.querySelector(`[data-student-id="${studentId}"]`);
        if (selectedItem) {
            selectedItem.classList.add('active');
        }

        // Mettre à jour le menu burger
        document.querySelectorAll('#burgerStudentList a').forEach((link) => {
            link.classList.remove('active');
            if (link.dataset.studentId === studentId.toString()) {
                link.classList.add('active');
            }
        });

        // Charger le contenu
        this.loadStudentContent(studentId);
    }

    // Charger le contenu d'un étudiant
    async loadStudentContent(studentId) {
        const dataZone = document.querySelector('.data-zone');

        if (!dataZone) return;

        dataZone.innerHTML = '<div class="loading-spinner">⏳ Chargement...</div>';

        try {
            const response = await fetch(`/index.php?action=student&id=${studentId}`);

            if (!response.ok) {
                throw new Error('Erreur lors du chargement de l\'étudiant');
            }

            const result = await response.json();

            if (result.success) {
                const { userId, attempts, stats } = result.data;
                this.renderStudentData(dataZone, userId, attempts, stats);
            }
        } catch (error) {
            console.error('Erreur:', error);
            dataZone.innerHTML = '<p class="placeholder-message">Erreur lors du chargement de l\'étudiant</p>';
        }
    }

    // Afficher les données de l'étudiant
    renderStudentData(dataZone, userId, attempts, stats) {
        dataZone.innerHTML = '';

        // Titre
        const titleElement = document.createElement('h2');
        titleElement.textContent = userId;
        titleElement.style.marginBottom = '1.5rem';
        dataZone.appendChild(titleElement);

        // Créer les onglets
        const tabsContainer = this.tabManager.createTabs();
        dataZone.appendChild(tabsContainer);

        // Créer le contenu des données brutes
        const rawDataContent = document.createElement('div');
        rawDataContent.id = 'raw-data-content';
        rawDataContent.className = 'tab-content active';

        // Ajouter les statistiques
        const statsDiv = this.statsRenderer.renderStats(stats);
        rawDataContent.appendChild(statsDiv);

        // Ajouter les tentatives
        const { title: attemptsTitle, container: attemptsContainer } = this.attemptsRenderer.renderAttempts(attempts);
        rawDataContent.appendChild(attemptsTitle);
        rawDataContent.appendChild(attemptsContainer);

        // Créer le contenu de visualisation
        const visualizationContent = this.createVisualizationContent();

        dataZone.appendChild(rawDataContent);
        dataZone.appendChild(visualizationContent);

        // Scroll vers le haut
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.scrollTop = 0;
        }
    }

    // Créer le contenu de visualisation
    createVisualizationContent() {
        const visualizationContent = document.createElement('div');
        visualizationContent.id = 'visualization-content';
        visualizationContent.className = 'tab-content';
        visualizationContent.style.display = 'none';

        const placeholder = document.createElement('div');
        placeholder.style.textAlign = 'center';
        placeholder.style.padding = '3rem 1rem';
        placeholder.style.color = '#7f8c8d';
        placeholder.style.fontSize = '1.1rem';
        placeholder.innerHTML = `
            <div style="margin-bottom: 1rem; font-size: 3rem;">📈</div>
            <div style="font-weight: 600; margin-bottom: 0.5rem; color: #2c3e50;">Visualisation des données</div>
            <div>Cet espace est prêt pour votre visualisation personnalisée.</div>
            <div style="margin-top: 1rem; font-size: 0.9rem;">Les données sont disponibles et peuvent être affichées sous forme de graphiques.</div>
        `;
        visualizationContent.appendChild(placeholder);

        return visualizationContent;
    }

    getCurrentStudentId() {
        return this.currentStudentId;
    }
}

