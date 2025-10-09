// Variables globales
let currentTPId = null;
let tpData = [];

// Fonction de confirmation de déconnexion
function confirmLogout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        window.location.href = '../controllers/deconnexion.php';
    }
}

// Fonctions pour le modal du plan du site
function openSiteMap() {
    document.getElementById('sitemapModal').style.display = 'block';
}

function closeSiteMap() {
    document.getElementById('sitemapModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('sitemapModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Charger les TPs au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadTPs();
});

// Fonction pour charger la liste des TPs
function loadTPs() {
    // Génération de TPs simples pour l'exemple
    tpData = [];

    for (let i = 1; i <= 25; i++) {
        tpData.push({
            id: i,
            title: `TP ${i}`
        });
    }

    displayTPs();
}

// Afficher les TPs dans la sidebar
function displayTPs() {
    const tpList = document.getElementById('tp-list');

    if (!tpList) return;

    tpList.innerHTML = '';

    if (tpData.length === 0) {
        tpList.innerHTML = '<p style="text-align: center; color: #7f8c8d;">Aucun TP disponible</p>';
        return;
    }

    tpData.forEach(tp => {
        const tpItem = document.createElement('div');
        tpItem.className = 'tp-item';
        tpItem.dataset.tpId = tp.id;

        tpItem.innerHTML = `
            <h3>${tp.title}</h3>
        `;

        tpItem.addEventListener('click', () => selectTP(tp.id));

        tpList.appendChild(tpItem);
    });
}

// Sélectionner un TP
function selectTP(tpId) {
    currentTPId = tpId;

    // Mettre à jour l'état actif des éléments
    document.querySelectorAll('.tp-item').forEach(item => {
        item.classList.remove('active');
    });

    const selectedItem = document.querySelector(`[data-tp-id="${tpId}"]`);
    if (selectedItem) {
        selectedItem.classList.add('active');
    }

    // Charger et afficher le contenu du TP
    loadTPContent(tpId);
}

// Charger le contenu d'un TP
function loadTPContent(tpId) {
    const dataZone = document.querySelector('.data-zone');

    if (!dataZone) return;

    const tp = tpData.find(t => t.id == tpId);

    if (!tp) {
        dataZone.innerHTML = '<p class="placeholder-message">TP non trouvé</p>';
        return;
    }

    // Afficher simplement le titre du TP
    dataZone.innerHTML = `
        <h2>${tp.title}</h2>
        <p style="color: #7f8c8d; margin-top: 2rem;">Visualisation des données pour ${tp.title}</p>
    `;

    // Scroll vers le haut du contenu principal
    document.querySelector('.main-content').scrollTop = 0;
}
