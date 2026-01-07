// Module utilitaire

export class Utils {
    static confirmLogout() {
        if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
            window.location.href = '/index.php?action=logout';
        }
    }

    static openSiteMap() {
        const modal = document.getElementById('sitemapModal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    static closeSiteMap() {
        const modal = document.getElementById('sitemapModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    static setupModalCloseOnOutsideClick() {
        window.onclick = function(event) {
            const modal = document.getElementById('sitemapModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    }

    static getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }
}
