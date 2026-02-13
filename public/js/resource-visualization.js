// Fichier JavaScript pour la visualisation de ressource

import { Utils } from '/public/js/modules/utils.js';
import { BurgerMenuManager } from '/public/js/modules/burgerMenu.js';

// Variables globales
let currentDetailedView = 'students';
let burgerMenuManager;

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeVisualization();
});

/**
 * Initialiser la visualisation
 */
function initializeVisualization() {
    // Créer le manager du menu burger
    burgerMenuManager = new BurgerMenuManager();

    // Exposer les fonctions globales
    window.toggleBurgerMenu = () => burgerMenuManager.toggleMenu();
    window.confirmLogout = Utils.confirmLogout;
    window.switchDetailedView = switchDetailedView;

    // Charger les données initiales
    loadOverviewCharts();
    loadDetailedView('students');

    // Configurer les utilitaires
    Utils.setupModalCloseOnOutsideClick();
}

/**
 * Charger les graphiques de vue d'ensemble
 */
async function loadOverviewCharts() {
    const resourceId = window.RESOURCE_ID;

    // Charger le graphique des étudiants
    try {
        const studentsResponse = await fetch(
            `${window.BASE_URL}/index.php?action=students_stats&resource_id=${resourceId}`
        );
        const studentsData = await studentsResponse.json();

        if (studentsData.success && typeof ChartModule !== 'undefined') {
            ChartModule.renderStudentChart(studentsData.data, 'students-overview-chart');
        } else {
            document.getElementById('students-overview-chart').innerHTML =
                '<p style="text-align: center; color: #999;">Aucune donnée disponible</p>';
        }
    } catch (error) {
        console.error('Erreur lors du chargement des stats étudiants:', error);
        document.getElementById('students-overview-chart').innerHTML =
            '<p style="text-align: center; color: #d32f2f;">Erreur lors du chargement des données</p>';
    }

    // Charger le graphique des exercices
    try {
        const exercisesResponse = await fetch(
            `${window.BASE_URL}/index.php?action=exercises_stats&resource_id=${resourceId}`
        );
        const exercisesData = await exercisesResponse.json();

        if (exercisesData.success && typeof ChartModule !== 'undefined') {
            ChartModule.renderExerciseChart(exercisesData.data, 'exercises-overview-chart');
        } else {
            document.getElementById('exercises-overview-chart').innerHTML =
                '<p style="text-align: center; color: #999;">Aucune donnée disponible</p>';
        }
    } catch (error) {
        console.error('Erreur lors du chargement des stats exercices:', error);
        document.getElementById('exercises-overview-chart').innerHTML =
            '<p style="text-align: center; color: #d32f2f;">Erreur lors du chargement des données</p>';
    }
}

/**
 * Basculer la vue détaillée
 * @param {string} viewType - Type de vue ('students' ou 'exercises')
 */
function switchDetailedView(viewType) {
    currentDetailedView = viewType;

    // Mettre à jour les boutons actifs
    document.querySelectorAll('.view-toggle button').forEach(btn => {
        btn.classList.remove('active');
    });

    if (viewType === 'students') {
        document.getElementById('btn-detailed-students').classList.add('active');
    } else {
        document.getElementById('btn-detailed-exercises').classList.add('active');
    }

    // Charger la vue
    loadDetailedView(viewType);
}

/**
 * Charger la vue détaillée
 * @param {string} viewType - Type de vue
 */
async function loadDetailedView(viewType) {
    const container = document.getElementById('detailed-view-container');
    const resourceId = window.RESOURCE_ID;

    // Afficher le chargement
    container.innerHTML = '<div class="loading-spinner">Chargement des données...</div>';

    try {
        if (viewType === 'students') {
            // Charger les données détaillées des étudiants
            const response = await fetch(
                `${window.BASE_URL}/index.php?action=students&resource_id=${resourceId}&page=1&perPage=100`
            );
            const data = await response.json();

            if (data.success && data.data && data.data.length > 0) {
                renderStudentsDetailedView(data.data, container);
            } else {
                container.innerHTML = '<p style="text-align: center; color: #999;">Aucun étudiant trouvé</p>';
            }
        } else {
            // Charger les données détaillées des exercices
            const response = await fetch(
                `${window.BASE_URL}/index.php?action=exercises&resource_id=${resourceId}`
            );
            const data = await response.json();

            if (data.success && data.data && data.data.length > 0) {
                renderExercisesDetailedView(data.data, container);
            } else {
                container.innerHTML = '<p style="text-align: center; color: #999;">Aucun exercice trouvé</p>';
            }
        }
    } catch (error) {
        console.error('Erreur lors du chargement de la vue détaillée:', error);
        container.innerHTML = '<p style="text-align: center; color: #d32f2f;">Erreur lors du chargement des données</p>';
    }
}

/**
 * Afficher la vue détaillée des étudiants
 * @param {Array} students - Liste des étudiants
 * @param {HTMLElement} container - Conteneur
 */
function renderStudentsDetailedView(students, container) {
    // Créer une liste interactive des étudiants
    let html = '<div class="students-detailed-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';

    students.forEach(student => {
        const successRate = student.success_rate || 0;
        const attemptsCount = student.attempts_count || 0;
        const successColor = successRate >= 70 ? '#4caf50' : successRate >= 40 ? '#ff9800' : '#f44336';

        html += `
            <div class="student-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;" onclick="viewStudentDetails('${student.student_id}')">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: ${successColor}; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2em;">
                        ${student.first_name?.charAt(0) || '?'}${student.last_name?.charAt(0) || '?'}
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin: 0; font-size: 1.1em; color: #333;">${student.first_name || ''} ${student.last_name || ''}</h3>
                        <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9em;">${student.email || ''}</p>
                    </div>
                </div>
                <div style="border-top: 1px solid #eee; padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #666;">Taux de réussite</span>
                        <span style="color: ${successColor}; font-weight: bold;">${successRate.toFixed(1)}%</span>
                    </div>
                    <div style="width: 100%; height: 8px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
                        <div style="width: ${successRate}%; height: 100%; background: ${successColor}; transition: width 0.3s;"></div>
                    </div>
                    <p style="margin: 10px 0 0 0; color: #666; font-size: 0.9em;">
                        ${attemptsCount} tentative${attemptsCount > 1 ? 's' : ''}
                    </p>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

/**
 * Afficher la vue détaillée des exercices
 * @param {Array} exercises - Liste des exercices
 * @param {HTMLElement} container - Conteneur
 */
function renderExercisesDetailedView(exercises, container) {
    // Créer une liste interactive des exercices
    let html = '<div class="exercises-detailed-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">';

    exercises.forEach(exercise => {
        const difficulty = exercise.difficulte || 'Non spécifiée';
        const difficultyColor = difficulty === 'Facile' ? '#4caf50' : difficulty === 'Moyen' ? '#ff9800' : '#f44336';

        html += `
            <div class="exercise-card" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s;" onclick="viewExerciseDetails('${exercise.exercise_id}')">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <h3 style="margin: 0; font-size: 1.2em; color: #333; flex: 1;">${exercise.exo_name || exercise.title || 'Exercice'}</h3>
                    <span style="padding: 4px 12px; background: ${difficultyColor}; color: white; border-radius: 12px; font-size: 0.85em; white-space: nowrap;">${difficulty}</span>
                </div>
                <p style="margin: 0 0 15px 0; color: #666; line-height: 1.5;">${exercise.description || 'Aucune description'}</p>
                ${exercise.funcname ? `<p style="margin: 0; color: #667eea; font-family: monospace; font-size: 0.9em;">Fonction: ${exercise.funcname}</p>` : ''}
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

/**
 * Voir les détails d'un étudiant
 * @param {string} studentId - ID de l'étudiant
 */
window.viewStudentDetails = function(studentId) {
    // Rediriger vers le dashboard avec l'étudiant sélectionné
    window.location.href = `${window.BASE_URL}/index.php?action=dashboard&resource_id=${window.RESOURCE_ID}&student=${studentId}`;
};

/**
 * Voir les détails d'un exercice
 * @param {string} exerciseId - ID de l'exercice
 */
window.viewExerciseDetails = function(exerciseId) {
    // Rediriger vers le dashboard avec l'exercice sélectionné
    window.location.href = `${window.BASE_URL}/index.php?action=dashboard&resource_id=${window.RESOURCE_ID}&exercise=${exerciseId}`;
};

