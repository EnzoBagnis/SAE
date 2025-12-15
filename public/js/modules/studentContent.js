// Module de gestion du contenu des étudiants

import { TabManager } from '/public/js/modules/tabManager.js';
import { StatsRenderer } from '/public/js/modules/statsRenderer.js';
import { AttemptsRenderer } from '/public/js/modules/attemptsRenderer.js';

export class StudentContentManager {
    constructor() {
        this.currentStudentId = null;
        this.tabManager = new TabManager();
        this.statsRenderer = new StatsRenderer();
        this.attemptsRenderer = new AttemptsRenderer();
        this.resourceId = this.getResourceIdFromUrl();
    }

    // Récupérer l'ID de la ressource depuis l'URL
    getResourceIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('resource_id');
    }

    selectStudent(studentId) {
        this.currentStudentId = studentId;

        document.querySelectorAll('.student-item').forEach((item) => {
            item.classList.remove('active');
        });

        const selectedItem = document.querySelector(`[data-student-id="${studentId}"]`);
        if (selectedItem) {
            selectedItem.classList.add('active');
        }

        document.querySelectorAll('#burgerStudentList a').forEach((link) => {
            link.classList.remove('active');
            if (link.dataset.studentId === studentId.toString()) {
                link.classList.add('active');
            }
        });

        this.loadStudentContent(studentId);
    }

    async loadStudentContent(studentId) {
        const dataZone = document.querySelector('.data-zone');
        if (!dataZone) return;

        dataZone.innerHTML = '<div class="loading-spinner">⏳ Chargement...</div>';

        try {
            // Construire l'URL avec le resource_id si disponible
            let url = `/index.php?action=student&id=${studentId}`;
            if (this.resourceId) {
                url += `&resource_id=${this.resourceId}`;
            }

            const response = await fetch(url);
            if (!response.ok) throw new Error('Erreur lors du chargement de l\'étudiant');

            const result = await response.json();
            if (result.success) {
                const { student, attempts, stats } = result.data;
                this.renderStudentData(dataZone, student, attempts, stats);
            }
        } catch (error) {
            console.error('Erreur:', error);
            dataZone.innerHTML = '<p class="placeholder-message">Erreur lors du chargement de l\'étudiant</p>';
        }
    }

    renderStudentData(dataZone, student, attempts, stats) {
        dataZone.innerHTML = '';

        const titleElement = document.createElement('h2');
        titleElement.textContent = student.identifier;
        titleElement.style.marginBottom = '1.5rem';
        dataZone.appendChild(titleElement);

        // Afficher les informations de l'étudiant
        if (student.nom_fictif || student.prenom_fictif) {
            const studentInfo = document.createElement('div');
            studentInfo.style.marginBottom = '1rem';
            studentInfo.style.padding = '0.75rem';
            studentInfo.style.backgroundColor = '#ecf0f1';
            studentInfo.style.borderRadius = '0.5rem';
            studentInfo.innerHTML = `
                <strong>Nom fictif:</strong> ${student.nom_fictif || 'N/A'} ${student.prenom_fictif || ''}<br>
                <strong>Dataset:</strong> ${student.dataset || 'N/A'}
                ${student.pays ? `<br><strong>Pays:</strong> ${student.pays}` : ''}
                ${student.annee ? `<br><strong>Année:</strong> ${student.annee}` : ''}
            `;
            dataZone.appendChild(studentInfo);
        }

        const tabsContainer = this.tabManager.createTabs();
        dataZone.appendChild(tabsContainer);

        const rawDataContent = document.createElement('div');
        rawDataContent.id = 'raw-data-content';
        rawDataContent.className = 'tab-content active';

        const statsDiv = this.statsRenderer.renderStats(stats);
        rawDataContent.appendChild(statsDiv);

        const { title: attemptsTitle, container: attemptsContainer } = this.attemptsRenderer.renderAttempts(attempts);
        rawDataContent.appendChild(attemptsTitle);
        rawDataContent.appendChild(attemptsContainer);

        const visualizationContent = this.createVisualizationContent();

        dataZone.appendChild(rawDataContent);
        dataZone.appendChild(visualizationContent);

        const mainContent = document.querySelector('.main-content');
        if (mainContent) mainContent.scrollTop = 0;
    }

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

    getResourceId() {
        return this.resourceId;
    }

    /**
     * Select an exercise and display its details
     * @param {number} exerciseId - The ID of the exercise to select
     */
    async selectExercise(exerciseId) {
        const dataZone = document.querySelector('.data-zone');
        if (!dataZone) return;

        dataZone.innerHTML = '<div class="loading-spinner">⏳ Chargement de l\'exercice...</div>';

        try {
            // Build URL with resource_id if available
            let url = `/index.php?action=exercise&id=${exerciseId}`;
            if (this.resourceId) {
                url += `&resource_id=${this.resourceId}`;
            }

            const response = await fetch(url);
            if (!response.ok) throw new Error('Erreur lors du chargement de l\'exercice');

            const result = await response.json();
            if (result.success) {
                this.renderExerciseData(dataZone, result.data.exercise, result.data.students);
            } else {
                // If API not available yet, show basic info
                dataZone.innerHTML = `
                    <div class="exercise-details">
                        <h2>Exercice #${exerciseId}</h2>
                        <p class="placeholder-message">Détails de l'exercice en cours de chargement...</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Erreur:', error);
            // Show placeholder if exercise API is not implemented
            dataZone.innerHTML = `
                <div class="exercise-details">
                    <h2>Exercice sélectionné</h2>
                    <p class="placeholder-message">L'affichage détaillé des exercices sera disponible prochainement.</p>
                </div>
            `;
        }
    }

    /**
     * Render exercise data in the content area
     * @param {HTMLElement} dataZone - The container element
     * @param {Object} exercise - The exercise data
     * @param {Array} students - List of students who attempted the exercise
     */
    renderExerciseData(dataZone, exercise, students) {
        dataZone.innerHTML = '';

        const titleElement = document.createElement('h2');
        titleElement.textContent = exercise.exo_name || 'Exercice sans nom';
        titleElement.style.marginBottom = '1.5rem';
        dataZone.appendChild(titleElement);

        // Exercise info card
        const exerciseInfo = document.createElement('div');
        exerciseInfo.className = 'exercise-info-card';
        exerciseInfo.style.marginBottom = '1.5rem';
        exerciseInfo.style.padding = '1rem';
        exerciseInfo.style.backgroundColor = '#ecf0f1';
        exerciseInfo.style.borderRadius = '0.5rem';

        const difficultyColors = {
            'facile': '#27ae60',
            'moyen': '#f39c12',
            'difficile': '#e74c3c'
        };
        const difficultyLabels = {
            'facile': 'Facile',
            'moyen': 'Moyen',
            'difficile': 'Difficile'
        };

        exerciseInfo.innerHTML = `
            ${exercise.funcname ? `<strong>Fonction:</strong> ${exercise.funcname}<br>` : ''}
            ${exercise.description ? `<strong>Description:</strong> ${exercise.description}<br>` : ''}
            ${exercise.difficulte ? `<strong>Difficulté:</strong> <span style="color: ${difficultyColors[exercise.difficulte] || '#333'}">${difficultyLabels[exercise.difficulte] || exercise.difficulte}</span><br>` : ''}
            ${exercise.resource_name ? `<strong>Ressource:</strong> ${exercise.resource_name}` : ''}
        `;
        dataZone.appendChild(exerciseInfo);

        // Students who attempted this exercise
        if (students && students.length > 0) {
            const studentsSection = document.createElement('div');
            studentsSection.innerHTML = `
                <h3 style="margin-bottom: 1rem; color: #2c3e50;">Étudiants ayant tenté cet exercice (${students.length})</h3>
                <div class="students-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 0.75rem;">
                    ${students.map(s => `
                        <div class="student-card" style="padding: 0.75rem; background: white; border-radius: 4px; border: 1px solid #dee2e6; cursor: pointer;" 
                             onclick="window.dispatchEvent(new CustomEvent('studentSelected', { detail: ${s.student_id} }))">
                            ${s.student_identifier || 'Étudiant #' + s.student_id}
                        </div>
                    `).join('')}
                </div>
            `;
            dataZone.appendChild(studentsSection);
        }

        const mainContent = document.querySelector('.main-content');
        if (mainContent) mainContent.scrollTop = 0;
    }
}
