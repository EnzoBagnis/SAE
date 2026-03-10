// Fichier principal du dashboard - Orchestration des modules (nouveau layout sans sidebar)

import { BurgerMenuManager } from '/public/js/modules/burgerMenu.js';
import { Utils } from '/public/js/modules/utils.js';
import { VizManager } from '/public/js/modules/vizManager.js';
// Instances globales
let burgerMenuManager;
let vizManager;

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

// Fonction d'initialisation principale
function initializeDashboard() {
    burgerMenuManager = new BurgerMenuManager();
    vizManager = new VizManager();


    // Exposer les fonctions globales pour le HTML
    window.toggleBurgerMenu = () => burgerMenuManager.toggleMenu();
    window.confirmLogout = Utils.confirmLogout;
    window.openSiteMap = Utils.openSiteMap;
    window.closeSiteMap = Utils.closeSiteMap;

    // Exposer vizManager et une fonction de navigation pour les autres modules (ex: barre de recherche)
    window.vizManager = vizManager;
    window.navigateToExercise = function(exerciseId, exerciseName) {
        const dataZone = document.querySelector('.viz-data-zone');
        if (dataZone && vizManager) {
            vizManager.renderLevel2TP(dataZone, exerciseId, exerciseName);
            dataZone.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };
    window.navigateToStudent = function(studentId) {
        const dataZone = document.querySelector('.viz-data-zone');
        if (dataZone && vizManager) {
            vizManager.renderLevel2Student(dataZone, studentId);
            dataZone.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    Utils.setupModalCloseOnOutsideClick();

    // Charger la vue niveau 1 directement
    const dataZone = document.querySelector('.viz-data-zone');
    if (dataZone && window.RESOURCE_ID) {
        // Vérifier si on doit ouvrir directement un TP ou un élève (ex: venant de la barre de recherche)
        const urlParams = new URLSearchParams(window.location.search);
        const openExerciseId   = urlParams.get('open_exercise');
        const openExerciseName = urlParams.get('exercise_name') || '';
        const openStudentId    = urlParams.get('open_student');

        if (openExerciseId) {
            // Nettoyer l'URL sans recharger la page
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, '', cleanUrl);

            // Charger d'abord le niveau 1 en arrière-plan puis naviguer vers le TP
            vizManager.renderLevel1(dataZone).then(() => {
                vizManager.renderLevel2TP(dataZone, parseInt(openExerciseId, 10), decodeURIComponent(openExerciseName));
            }).catch(() => {
                vizManager.renderLevel2TP(dataZone, parseInt(openExerciseId, 10), decodeURIComponent(openExerciseName));
            });
        } else if (openStudentId) {
            // Nettoyer l'URL sans recharger la page
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, '', cleanUrl);

            // Charger d'abord le niveau 1 en arrière-plan puis naviguer vers l'élève
            vizManager.renderLevel1(dataZone).then(() => {
                vizManager.renderLevel2Student(dataZone, decodeURIComponent(openStudentId));
            }).catch(() => {
                vizManager.renderLevel2Student(dataZone, decodeURIComponent(openStudentId));
            });
        } else {
            vizManager.renderLevel1(dataZone);
        }
    } else if (dataZone) {
        dataZone.innerHTML = '<p style="text-align:center;color:#7f8c8d;padding:3rem;">Aucune ressource sélectionnée.</p>';
    }
}
