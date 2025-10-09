// Variables globales
let currentTPId = null;
let currentPage = 1;
const TPsPerPage = 10;
let isLoading = false;
let hasMoreTPs = true;

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
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}

// Charger les TPs au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadTPs();
    setupInfiniteScroll();
});

// Fonction pour charger la liste des TPs depuis le serveur
async function loadTPs() {
    if (isLoading || !hasMoreTPs) return;

    isLoading = true;
    const tpList = document.getElementById('tp-list');

    // Ajouter un indicateur de chargement
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-message';
    loadingDiv.style.textAlign = 'center';
    loadingDiv.style.padding = '1rem';
    loadingDiv.style.color = '#3498db';
    loadingDiv.innerHTML = '⏳ Chargement...';
    tpList.appendChild(loadingDiv);

    try {
        // Appel à l'API PHP pour récupérer les TPs
        const response = await fetch('../controllers/tpController.php?action=list&page=' + currentPage + '&perPage=' + TPsPerPage);

        if (!response.ok) {
            throw new Error('Erreur lors du chargement des TPs');
        }

        const result = await response.json();

        if (result.success) {
            displayTPs(result.data.tps);
            hasMoreTPs = result.data.hasMore;
            currentPage++;

            // Afficher un message si tous les TPs sont chargés
            if (!hasMoreTPs) {
                const endMessage = document.createElement('p');
                endMessage.className = 'end-message';
                endMessage.style.textAlign = 'center';
                endMessage.style.color = '#7f8c8d';
                endMessage.style.padding = '1rem';
                endMessage.style.fontSize = '0.9rem';
                endMessage.textContent = result.data.total + ' TPs affichés';
                tpList.appendChild(endMessage);
            }
        }
    } catch (error) {
        console.error('Erreur:', error);
        tpList.innerHTML += '<p style="text-align: center; color: #e74c3c;">Erreur de chargement</p>';
    } finally {
        // Supprimer le message de chargement
        const loadingMsg = tpList.querySelector('.loading-message');
        if (loadingMsg) {
            loadingMsg.remove();
        }
        isLoading = false;
    }
}

// Afficher les TPs dans la sidebar
function displayTPs(tps) {
    const tpList = document.getElementById('tp-list');

    if (!tpList) return;

    if (tps.length === 0 && currentPage === 1) {
        tpList.innerHTML = '<p style="text-align: center; color: #7f8c8d;">Aucun TP disponible</p>';
        return;
    }

    tps.forEach(function(tp) {
        const tpItem = document.createElement('div');
        tpItem.className = 'tp-item';
        tpItem.dataset.tpId = tp.id;

        const title = document.createElement('h3');
        title.textContent = tp.title;
        tpItem.appendChild(title);

        tpItem.addEventListener('click', function() {
            selectTP(tp.id);
        });

        tpList.appendChild(tpItem);
    });
}

// Configuration du scroll infini
function setupInfiniteScroll() {
    const sidebar = document.querySelector('.sidebar');

    if (!sidebar) return;

    sidebar.addEventListener('scroll', function() {
        // Vérifier si on est proche du bas
        const scrollPosition = sidebar.scrollTop + sidebar.clientHeight;
        const scrollHeight = sidebar.scrollHeight;

        // Si on est à 80% du scroll et qu'on n'est pas en train de charger
        if (scrollPosition >= scrollHeight * 0.8 && !isLoading && hasMoreTPs) {
            loadTPs();
        }
    });
}

// Sélectionner un TP
function selectTP(tpId) {
    currentTPId = tpId;

    // Mettre à jour l'état actif des éléments
    document.querySelectorAll('.tp-item').forEach(function(item) {
        item.classList.remove('active');
    });

    const selectedItem = document.querySelector('[data-tp-id="' + tpId + '"]');
    if (selectedItem) {
        selectedItem.classList.add('active');
    }

    // Charger et afficher le contenu du TP
    loadTPContent(tpId);
}

// Charger le contenu d'un TP
async function loadTPContent(tpId) {
    const dataZone = document.querySelector('.data-zone');

    if (!dataZone) return;

    // Afficher un indicateur de chargement
    dataZone.innerHTML = '<div class="loading-spinner">⏳ Chargement...</div>';

    try {
        // Appel à l'API PHP pour récupérer le contenu du TP
        const response = await fetch('../controllers/tpController.php?action=get&id=' + tpId);

        if (!response.ok) {
            throw new Error('Erreur lors du chargement du TP');
        }

        const result = await response.json();

        if (result.success) {
            const tp = result.data;

            // Afficher simplement le titre du TP
            const titleElement = document.createElement('h2');
            titleElement.textContent = tp.title;

            const descElement = document.createElement('p');
            descElement.style.color = '#7f8c8d';
            descElement.style.marginTop = '2rem';
            descElement.textContent = 'Visualisation des données pour ' + tp.title;

            dataZone.innerHTML = '';
            dataZone.appendChild(titleElement);
            dataZone.appendChild(descElement);

            // Scroll vers le haut du contenu principal
            document.querySelector('.main-content').scrollTop = 0;
        }
    } catch (error) {
        console.error('Erreur:', error);
        dataZone.innerHTML = '<p class="placeholder-message">Erreur lors du chargement du TP</p>';
    }
}

