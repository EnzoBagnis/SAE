// Variables globales
let currentTPId = null;
let currentPage = 1;
const TPsPerPage = 15;
let isLoading = false;
let hasMoreTPs = true;
let allTPs = []; // Stockage de tous les TPs pour le menu burger

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
    loadBurgerTPs(); // Charger les TPs pour le menu burger
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
        // Stocker dans la liste globale pour le menu burger
        if (!allTPs.find(t => t.id === tp.id)) {
            allTPs.push(tp);
        }

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

    // Mettre à jour le menu burger avec les nouveaux TPs
    updateBurgerTPList();
}

// Configuration du scroll infini
function setupInfiniteScroll() {
    const tpList = document.getElementById('tp-list');

    if (!tpList) return;

    tpList.addEventListener('scroll', function() {
        // Vérifier si on est proche du bas
        const scrollPosition = tpList.scrollTop + tpList.clientHeight;
        const scrollHeight = tpList.scrollHeight;

        // Si on est à 80% du scroll et qu'on n'est pas en train de charger
        if (scrollPosition >= scrollHeight * 0.8 && !isLoading && hasMoreTPs) {
            loadTPs();
        }
    });
}

// Sélectionner un TP
function selectTP(tpId) {
    currentTPId = tpId;

    // Mettre à jour l'état actif des éléments dans la sidebar
    document.querySelectorAll('.tp-item').forEach(function(item) {
        item.classList.remove('active');
    });

    const selectedItem = document.querySelector('[data-tp-id="' + tpId + '"]');
    if (selectedItem) {
        selectedItem.classList.add('active');
    }

    // Mettre à jour le menu burger
    document.querySelectorAll('#burgerTPList a').forEach(function(link) {
        link.classList.remove('active');
        if (link.dataset.tpId == tpId) {
            link.classList.add('active');
        }
    });

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

// ========== FONCTIONS MENU BURGER ==========

// Toggle du menu burger
function toggleBurgerMenu() {
    const burgerNav = document.getElementById('burgerNav');
    const burgerBtn = document.getElementById('burgerBtn');
    const body = document.body;

    burgerNav.classList.toggle('active');
    burgerBtn.classList.toggle('active');

    // Créer/supprimer l'overlay
    let overlay = document.querySelector('.burger-overlay');
    if (burgerNav.classList.contains('active')) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'burger-overlay active';
            overlay.onclick = closeBurgerMenu;
            body.appendChild(overlay);
        } else {
            overlay.classList.add('active');
        }
        body.style.overflow = 'hidden';
    } else {
        if (overlay) {
            overlay.classList.remove('active');
        }
        body.style.overflow = '';
    }
}

// Fermer le menu burger
function closeBurgerMenu() {
    const burgerNav = document.getElementById('burgerNav');
    const burgerBtn = document.getElementById('burgerBtn');
    const overlay = document.querySelector('.burger-overlay');

    burgerNav.classList.remove('active');
    burgerBtn.classList.remove('active');
    if (overlay) {
        overlay.classList.remove('active');
    }
    document.body.style.overflow = '';
}

// Toggle du sous-menu des TPs
function toggleTPSubmenu(event) {
    event.preventDefault();
    const submenu = document.getElementById('burgerTPList');
    const arrow = event.currentTarget.querySelector('.submenu-arrow');

    submenu.classList.toggle('active');
    arrow.classList.toggle('rotated');
}

// Charger tous les TPs pour le menu burger
async function loadBurgerTPs() {
    try {
        // Charger tous les TPs (on peut limiter à 50 pour l'exemple)
        const response = await fetch('../controllers/tpController.php?action=list&page=1&perPage=50');

        if (!response.ok) {
            throw new Error('Erreur lors du chargement des TPs');
        }

        const result = await response.json();

        if (result.success) {
            allTPs = result.data.tps;
            updateBurgerTPList();
        }
    } catch (error) {
        console.error('Erreur lors du chargement des TPs pour le menu burger:', error);
    }
}

// Mettre à jour la liste des TPs dans le menu burger
function updateBurgerTPList() {
    const burgerTPList = document.getElementById('burgerTPList');

    if (!burgerTPList) return;

    burgerTPList.innerHTML = '';

    if (allTPs.length === 0) {
        const emptyItem = document.createElement('li');
        emptyItem.innerHTML = '<a href="#" style="color: #95a5a6; cursor: default;">Aucun TP disponible</a>';
        burgerTPList.appendChild(emptyItem);
        return;
    }

    allTPs.forEach(function(tp) {
        const li = document.createElement('li');
        const link = document.createElement('a');
        link.href = '#';
        link.textContent = tp.title;
        link.dataset.tpId = tp.id;

        if (tp.id === currentTPId) {
            link.classList.add('active');
        }

        link.addEventListener('click', function(e) {
            e.preventDefault();
            selectTP(tp.id);
            closeBurgerMenu();
        });

        li.appendChild(link);
        burgerTPList.appendChild(li);
    });
}
