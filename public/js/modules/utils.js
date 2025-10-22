// Module utilitaire

export class Utils {
    // Confirmation de déconnexion
    static confirmLogout() {
        if (confirm('Êtes-vous sûr de vouloir vous déconnexter ?')) {
            window.location.href = '/index.php?action=logout';
        }
    }

    // Ouvrir le plan du site
    static openSiteMap() {
        const modal = document.getElementById('sitemapModal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    // Fermer le plan du site
    static closeSiteMap() {
        const modal = document.getElementById('sitemapModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Gestionnaire de clic sur la fenêtre
    static setupModalCloseOnOutsideClick() {
        window.onclick = function(event) {
            const modal = document.getElementById('sitemapModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    }
}

