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
}

// Exposer les fonctions globales nécessaires
window.confirmLogout = Utils.confirmLogout;
window.openSiteMap = Utils.openSiteMap;
window.closeSiteMap = Utils.closeSiteMap;
window.toggleBurgerMenu = () => burgerMenuManager.toggleMenu();
window.toggleStudentSubmenu = (event) => burgerMenuManager.toggleStudentSubmenu(event);

