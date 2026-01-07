// Fichier principal du dashboard - Orchestration des modules

import { StudentListManager } from '/public/js/modules/studentList.js';
import { StudentContentManager } from '/public/js/modules/studentContent.js';
import { BurgerMenuManager } from '/public/js/modules/burgerMenu.js';
import { Utils } from '/public/js/modules/utils.js';

// Instances globales
let studentListManager;
let studentContentManager;
let burgerMenuManager;

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

// Fonction d'initialisation principale
function initializeDashboard() {
    // Créer les instances des managers
    studentListManager = new StudentListManager();
    studentContentManager = new StudentContentManager();
    burgerMenuManager = new BurgerMenuManager();

    // Configurer les écouteurs d'événements
    setupEventListeners();

    // Charger les données initiales
    studentListManager.loadStudents();
    studentListManager.setupInfiniteScroll();
    studentListManager.loadAllStudents();

    // Configurer les utilitaires
    Utils.setupModalCloseOnOutsideClick();
}

// Configuration des écouteurs d'événements
function setupEventListeners() {
    // Écouter la sélection d'un étudiant
    window.addEventListener('studentSelected', (e) => {
        const studentId = e.detail;
        studentContentManager.selectStudent(studentId);
        burgerMenuManager.updateActiveStudent(studentId);
    });

    // Écouter la sélection d'un exercice
    window.addEventListener('exerciseSelected', (e) => {
        const exerciseId = e.detail;
        studentContentManager.selectExercise(exerciseId);
    });

    // Listen for chart clicks
    document.addEventListener('student-chart-click', (e) => {
        const studentId = e.detail.studentId;
        studentContentManager.selectStudent(studentId);
    });

    document.addEventListener('exercise-chart-click', (e) => {
        const exerciseId = e.detail.exerciseId;
        studentContentManager.selectExercise(exerciseId);
    });

    // Check if ChartModule is available
    if (typeof ChartModule !== 'undefined') {
        const resourceId = Utils.getUrlParameter('resource_id');

        // Expose switchListView to global for HTML onclick attributes
        window.switchListView = function(viewType) {
            // Update active state of buttons
            document.querySelectorAll('.view-tab').forEach(btn => btn.classList.remove('active'));
            if (viewType === 'students') {
                document.getElementById('btnStudents').classList.add('active');
                studentListManager.loadStudents();

                // Load global student stats
                fetch(`/index.php?action=students_stats&resource_id=${resourceId || ''}`)
                    .then(r => r.json())
                    .then(resp => {
                        if(resp.success) {
                            // Find or create a container for the chart in the main content area
                            // Or better, inject it into the top of the data-zone
                            const dataZone = document.querySelector('.data-zone');
                            let chartContainer = document.getElementById('global-student-chart');
                            if (!chartContainer) {
                                chartContainer = document.createElement('div');
                                chartContainer.id = 'global-student-chart';
                                chartContainer.className = 'chart-container';
                                dataZone.prepend(chartContainer);
                            }
                            // Important: Clear previous chart if switching back and forth
                            chartContainer.innerHTML = '';
                            ChartModule.renderStudentChart(resp.data, 'global-student-chart');
                        }
                    });
            } else {
                document.getElementById('btnExercises').classList.add('active');
                // Assume logic to load exercise list exists or add it
                // For now just load chart

                 // Reuse student list container or switch to exercise list?
                 // The user asked to switch lists. Assuming an ExerciseListManager exists or reusing container.
                 // If ExerciseList doesn't exist, we might need to implement basic list rendering here or in a module.

                 // Load global exercise stats
                fetch(`/index.php?action=exercises_stats&resource_id=${resourceId || ''}`)
                    .then(r => r.json())
                    .then(resp => {
                        if(resp.success) {
                             const dataZone = document.querySelector('.data-zone');
                             let chartContainer = document.getElementById('global-exercise-chart');
                             if (!chartContainer) {
                                chartContainer = document.createElement('div');
                                chartContainer.id = 'global-exercise-chart';
                                chartContainer.className = 'chart-container';
                                // Remove student chart if exists
                                const studentChart = document.getElementById('global-student-chart');
                                if(studentChart) studentChart.remove();

                                dataZone.prepend(chartContainer);
                             }
                             chartContainer.innerHTML = '';
                             ChartModule.renderExerciseChart(resp.data, 'global-exercise-chart');
                        }
                    });
            }
        };

        // Trigger default view
        // window.switchListView('students');
    }
}

// Exposer les fonctions globales nécessaires
window.confirmLogout = Utils.confirmLogout;
window.openSiteMap = Utils.openSiteMap;
window.closeSiteMap = Utils.closeSiteMap;
window.toggleBurgerMenu = () => burgerMenuManager.toggleMenu();
window.toggleStudentSubmenu = (event) => burgerMenuManager.toggleStudentSubmenu(event);
window.switchListView = (view) => studentListManager.switchView(view);
window.toggleAccordion = (accordionId) => studentListManager.toggleAccordion(accordionId);
window.selectExercise = (exerciseId) => studentContentManager.selectExercise(exerciseId);
