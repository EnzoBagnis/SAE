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
                     // Load both exercise stats charts
                     const dataZone = document.querySelector('.data-zone');
                     dataZone.innerHTML = '';

                     // Create title
                     const title = document.createElement('h2');
                     title.textContent = 'Statistiques Globales des Exercices';
                     title.style.marginBottom = '1rem';
                     dataZone.appendChild(title);

                     // Create container for two charts side by side
                     const chartsWrapper = document.createElement('div');
                     chartsWrapper.style.display = 'grid';
                     chartsWrapper.style.gridTemplateColumns = 'repeat(auto-fit, minmax(400px, 1fr))';
                     chartsWrapper.style.gap = '2rem';
                     chartsWrapper.style.marginBottom = '2rem';

                     // Create container for success rate chart
                     const successRateContainer = document.createElement('div');
                     successRateContainer.id = 'global-exercise-chart';
                     successRateContainer.className = 'chart-container';
                     chartsWrapper.appendChild(successRateContainer);

                     // Create container for completion chart
                     const completionContainer = document.createElement('div');
                     completionContainer.id = 'exercise-completion-chart';
                     completionContainer.className = 'chart-container';
                     chartsWrapper.appendChild(completionContainer);

                     dataZone.appendChild(chartsWrapper);

                     // Fetch and render success rate chart
                     fetch(`/index.php?action=exercises_stats&resource_id=${resourceId || ''}`)
                         .then(r => r.json())
                         .then(resp => {
                             if(resp.success) {
                                 ChartModule.renderExerciseChart(resp.data, 'global-exercise-chart');
                             }
                         })
                         .catch(err => {
                             console.error('Error loading exercise stats:', err);
                             successRateContainer.innerHTML = '<p>Erreur lors du chargement des statistiques</p>';
                         });

                     // Fetch and render completion chart
                     fetch(`/index.php?action=exercise_completion_stats&resource_id=${resourceId || ''}`)
                         .then(r => r.json())
                         .then(resp => {
                             if(resp.success) {
                                 ChartModule.renderExerciseCompletionChart(resp.data, 'exercise-completion-chart');
                             }
                         })
                         .catch(err => {
                             console.error('Error loading completion stats:', err);
                             completionContainer.innerHTML = '<p>Erreur lors du chargement des statistiques</p>';
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


