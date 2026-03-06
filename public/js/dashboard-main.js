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

    Utils.setupModalCloseOnOutsideClick();

    // Charger la vue niveau 1 directement
    const dataZone = document.querySelector('.viz-data-zone');
    if (dataZone && window.RESOURCE_ID) {
        vizManager.renderLevel1(dataZone);
    } else if (dataZone) {
        dataZone.innerHTML = '<p style="text-align:center;color:#7f8c8d;padding:3rem;">Aucune ressource sélectionnée.</p>';
    }
}
