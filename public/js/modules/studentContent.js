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
     * Select an exercise and display all student attempts for this exercise
     * @param {number} exerciseId - The ID of the exercise to select
     */
    async selectExercise(exerciseId) {
        const dataZone = document.querySelector('.data-zone');
        if (!dataZone) return;

        dataZone.innerHTML = '<div class="loading-spinner">⏳ Chargement des tentatives...</div>';

        try {
            let url = `/index.php?action=exercise&id=${exerciseId}`;
            if (this.resourceId) {
                url += `&resource_id=${this.resourceId}`;
            }

            console.log('🔍 [Exercise] Chargement:', url);
            const response = await fetch(url);

            const result = await response.json();
            console.log('📦 [Exercise] Données reçues:', result);

            if (result.success) {
                this.renderExerciseAttempts(dataZone, result.data.exercise, result.data.students || []);
            } else {
                dataZone.innerHTML = `<p class="placeholder-message">Erreur: ${result.message || 'Impossible de charger les données'}</p>`;
            }
        } catch (error) {
            console.error('❌ [Exercise] Erreur:', error);
            dataZone.innerHTML = '<p class="placeholder-message">Erreur lors du chargement des tentatives</p>';
        }
    }

    /**
     * Render exercise with students and their collapsible attempts
     * @param {HTMLElement} dataZone - The container element
     * @param {Object} exercise - The exercise data
     * @param {Array} students - List of students with their attempts
     */
    renderExerciseAttempts(dataZone, exercise, students) {
        dataZone.innerHTML = '';

        // Titre de l'exercice (funcname prioritaire, sinon exo_name)
        const titleElement = document.createElement('h2');
        titleElement.textContent = exercise.funcname || exercise.exo_name || 'Exercice sans nom';
        dataZone.appendChild(titleElement);

        // Info exercice
        const exerciseInfo = document.createElement('div');
        exerciseInfo.style.marginBottom = '1.5rem';
        exerciseInfo.style.padding = '1rem';
        exerciseInfo.style.backgroundColor = '#ecf0f1';
        exerciseInfo.style.borderRadius = '0.5rem';

        let infoHtml = '';
        if (exercise.funcname && exercise.exo_name) {
            infoHtml += `<strong>ID:</strong> <code style="font-size: 0.85rem;">${exercise.exo_name}</code><br>`;
        }
        if (exercise.description) {
            infoHtml += `<strong>Description:</strong> ${exercise.description}`;
        }
        exerciseInfo.innerHTML = infoHtml || 'Aucune description disponible';
        dataZone.appendChild(exerciseInfo);

        // Titre section étudiants
        const studentsHeader = document.createElement('h3');
        studentsHeader.style.marginBottom = '1rem';
        studentsHeader.style.color = '#2c3e50';
        studentsHeader.textContent = `Étudiants ayant tenté cet exercice (${students ? students.length : 0})`;
        dataZone.appendChild(studentsHeader);

        if (!students || students.length === 0) {
            const noStudents = document.createElement('p');
            noStudents.className = 'placeholder-message';
            noStudents.textContent = 'Aucune tentative pour cet exercice';
            dataZone.appendChild(noStudents);
            return;
        }

        // Container des étudiants
        const studentsContainer = document.createElement('div');
        studentsContainer.className = 'students-attempts-list';

        students.forEach((student) => {
            const studentCard = this.createStudentAttemptCard(student, exercise);
            studentsContainer.appendChild(studentCard);
        });

        dataZone.appendChild(studentsContainer);

        const mainContent = document.querySelector('.main-content');
        if (mainContent) mainContent.scrollTop = 0;
    }

    /**
     * Create a student card with collapsible attempts
     * @param {Object} student - Student data with attempts
     * @param {Object} exercise - Exercise data
     */
    createStudentAttemptCard(student, exercise) {
        const card = document.createElement('div');
        card.style.cssText = `
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        `;

        // Header cliquable
        const header = document.createElement('div');
        header.style.cssText = `
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid #eee;
        `;
        header.addEventListener('mouseenter', () => header.style.background = '#f8f9fa');
        header.addEventListener('mouseleave', () => header.style.background = 'white');

        const leftSection = document.createElement('div');
        leftSection.style.cssText = 'display: flex; align-items: center; gap: 1rem;';

        // Flèche
        const arrow = document.createElement('span');
        arrow.textContent = '▶';
        arrow.style.cssText = 'font-size: 0.8rem; color: #666; transition: transform 0.3s;';

        // Info étudiant
        const studentInfo = document.createElement('div');
        const studentName = student.student_identifier || `Étudiant #${student.student_id}`;
        studentInfo.innerHTML = `<strong style="color: #2c3e50;">${studentName}</strong>`;

        leftSection.appendChild(arrow);
        leftSection.appendChild(studentInfo);

        // Badge nombre de tentatives
        const badge = document.createElement('span');
        const attemptCount = student.attempt_count || (student.attempts ? student.attempts.length : 0);
        badge.textContent = `${attemptCount} tentative${attemptCount > 1 ? 's' : ''}`;
        badge.style.cssText = `
            background: #3498db;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        `;

        header.appendChild(leftSection);
        header.appendChild(badge);

        // Contenu déroulable (tentatives)
        const content = document.createElement('div');
        content.style.cssText = 'display: none; padding: 1rem 1.5rem; background: #f8f9fa;';

        // Afficher les tentatives de cet étudiant
        if (student.attempts && student.attempts.length > 0) {
            student.attempts.forEach((attempt, index) => {
                const attemptItem = this.createAttemptItem(attempt, index, student.attempts.length);
                content.appendChild(attemptItem);
            });
        } else {
            content.innerHTML = '<p style="color: #7f8c8d; text-align: center;">Aucune tentative détaillée disponible</p>';
        }

        // Toggle au clic
        header.addEventListener('click', () => {
            const isOpen = content.style.display !== 'none';
            content.style.display = isOpen ? 'none' : 'block';
            arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
        });

        card.appendChild(header);
        card.appendChild(content);

        return card;
    }

    /**
     * Create an attempt item inside the collapsible section
     * @param {Object} attempt - Attempt data
     * @param {number} index - Index of the attempt
     * @param {number} total - Total number of attempts
     */
    createAttemptItem(attempt, index, total) {
        const item = document.createElement('div');
        item.style.cssText = `
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        `;

        // Header de la tentative
        const header = document.createElement('div');
        header.style.cssText = 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;';

        const attemptNumber = document.createElement('span');
        attemptNumber.style.cssText = 'font-weight: 600; color: #2c3e50;';
        attemptNumber.textContent = `Tentative #${total - index}`;

        const statusBadge = document.createElement('span');
        const isCorrect = attempt.correct === 1 || attempt.correct === '1';
        statusBadge.textContent = isCorrect ? '✓ Réussi' : '✗ Échoué';
        statusBadge.style.cssText = `
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            background: ${isCorrect ? '#d4edda' : '#f8d7da'};
            color: ${isCorrect ? '#155724' : '#721c24'};
        `;

        header.appendChild(attemptNumber);
        header.appendChild(statusBadge);

        // Date
        const dateDiv = document.createElement('div');
        dateDiv.style.cssText = 'font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;';
        const dateStr = attempt.submission_date ?
            new Date(attempt.submission_date).toLocaleString('fr-FR') : 'Date inconnue';
        dateDiv.textContent = `📅 ${dateStr}`;

        item.appendChild(header);
        item.appendChild(dateDiv);

        // Code soumis
        if (attempt.upload) {
            const codeSection = document.createElement('details');
            codeSection.style.marginTop = '0.5rem';

            const summary = document.createElement('summary');
            summary.style.cssText = 'cursor: pointer; color: #3498db; font-size: 0.9rem;';
            summary.textContent = '👁 Voir le code soumis';

            const codeBlock = document.createElement('pre');
            codeBlock.style.cssText = `
                background: #2c3e50;
                color: #ecf0f1;
                padding: 1rem;
                border-radius: 4px;
                overflow-x: auto;
                font-size: 0.85rem;
                margin-top: 0.5rem;
            `;
            codeBlock.textContent = attempt.upload;

            codeSection.appendChild(summary);
            codeSection.appendChild(codeBlock);
            item.appendChild(codeSection);
        }

        return item;
    }
}
