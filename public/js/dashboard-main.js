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

    // Exposer les fonctions globales pour le HTML
    window.toggleBurgerMenu = () => burgerMenuManager.toggleMenu();
    window.toggleStudentSubmenu = (e) => burgerMenuManager.toggleStudentSubmenu(e);
    window.toggleExerciseSubmenu = (e) => burgerMenuManager.toggleExerciseSubmenu(e);
    // window.switchListView est géré dans setupEventListeners si les charts sont actifs, ou en fallback en bas de fichier
    if (!window.switchListView) {
         window.switchListView = (view) => studentListManager.switchView(view);
    }
    window.closeBurgerMenu = () => burgerMenuManager.closeMenu();

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
        window.switchListView = function(viewType, loadContent = true) {
            // Update the sidebar list content using the manager
            studentListManager.switchView(viewType);

            // Update active state of buttons
            document.querySelectorAll('.view-tab').forEach(btn => btn.classList.remove('active'));
            if (viewType === 'students') {
                document.getElementById('btnStudents').classList.add('active');

                if (loadContent) {
                    // Load global student stats
                    fetch(`/index.php?action=students_stats&resource_id=${resourceId || ''}`)
                        .then(r => r.json())
                        .then(resp => {
                            if(resp.success) {
                                // Find the data-zone and clear specific content to avoid overlap
                                const dataZone = document.querySelector('.data-zone');

                                // Clear content but keep structure if needed, or fully reset
                                dataZone.innerHTML = '';

                                let chartContainer = document.createElement('div');
                                chartContainer.id = 'global-student-chart';
                                chartContainer.className = 'chart-container';

                                // Add a placeholder message or title if desired
                                const title = document.createElement('h2');
                                title.textContent = 'Statistiques Globales des Étudiants';
                                title.style.marginBottom = '1rem';
                                dataZone.appendChild(title);

                                dataZone.appendChild(chartContainer);

                                ChartModule.renderStudentChart(resp.data, 'global-student-chart');
                            }
                        });
                }
            } else {
                document.getElementById('btnExercises').classList.add('active');

                 if (loadContent) {
                     // Load global exercise stats
                    fetch(`/index.php?action=exercises_stats&resource_id=${resourceId || ''}`)
                        .then(r => r.json())
                        .then(resp => {
                            if(resp.success) {
                                 const dataZone = document.querySelector('.data-zone');

                                 // Clear content to avoid overlap
                                 dataZone.innerHTML = '';

                                 let chartContainer = document.createElement('div');
                                 chartContainer.id = 'global-exercise-chart';
                                 chartContainer.className = 'chart-container';

                                 const title = document.createElement('h2');
                                 title.textContent = 'Statistiques Globales des Exercices';
                                 title.style.marginBottom = '1rem';
                                 dataZone.appendChild(title);

                                 dataZone.appendChild(chartContainer);

                                 ChartModule.renderExerciseChart(resp.data, 'global-exercise-chart');
                            }
                        });
                 }
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
// window.switchListView est déjà défini plus haut si ChartModule est présent, sinon on peut mettre un fallback simple
if (!window.switchListView) {
    window.switchListView = (view) => studentListManager.switchView(view);
}
window.toggleAccordion = (accordionId) => studentListManager.toggleAccordion(accordionId);
window.selectExercise = (exerciseId) => studentContentManager.selectExercise(exerciseId);
