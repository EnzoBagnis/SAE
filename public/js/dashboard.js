// Variables globales
let currentStudentId = null;
let currentPage = 1;
const studentsPerPage = 15;
let isLoading = false;
let hasMoreStudents = true;
let allStudents = []; // Stockage de tous les étudiants pour le menu burger

// Fonction de confirmation de déconnexion
function confirmLogout() {
    if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        window.location.href = '/index.php?action=logout';
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

// Charger les étudiants au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadStudents();
    setupInfiniteScroll();
    loadBurgerStudents(); // Charger les étudiants pour le menu burger
});

// Fonction pour charger la liste des étudiants depuis le serveur
async function loadStudents() {
    if (isLoading || !hasMoreStudents) return;

    isLoading = true;
    const studentList = document.getElementById('student-list');

    // Ajouter un indicateur de chargement
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'loading-message';
    loadingDiv.style.textAlign = 'center';
    loadingDiv.style.padding = '1rem';
    loadingDiv.style.color = '#3498db';
    loadingDiv.innerHTML = '⏳ Chargement...';
    studentList.appendChild(loadingDiv);

    try {
        // Appel à l'API PHP pour récupérer les étudiants
        const response = await fetch('/index.php?action=students&page=' + currentPage + '&perPage=' + studentsPerPage);

        if (!response.ok) {
            throw new Error('Erreur lors du chargement des étudiants');
        }

        const result = await response.json();

        if (result.success) {
            displayStudents(result.data.students);
            hasMoreStudents = result.data.hasMore;
            currentPage++;

            // Afficher un message si tous les étudiants sont chargés
            if (!hasMoreStudents) {
                const endMessage = document.createElement('p');
                endMessage.className = 'end-message';
                endMessage.style.textAlign = 'center';
                endMessage.style.color = '#7f8c8d';
                endMessage.style.padding = '1rem';
                endMessage.style.fontSize = '0.9rem';
                endMessage.textContent = result.data.total + ' étudiants affichés';
                studentList.appendChild(endMessage);
            }
        }
    } catch (error) {
        console.error('Erreur:', error);
        studentList.innerHTML += '<p style="text-align: center; color: #e74c3c;">Erreur de chargement</p>';
    } finally {
        // Supprimer le message de chargement
        const loadingMsg = studentList.querySelector('.loading-message');
        if (loadingMsg) {
            loadingMsg.remove();
        }
        isLoading = false;
    }
}

// Afficher les étudiants dans la sidebar
function displayStudents(students) {
    const studentList = document.getElementById('student-list');

    if (!studentList) return;

    if (students.length === 0 && currentPage === 1) {
        studentList.innerHTML = '<p style="text-align: center; color: #7f8c8d;">Aucun étudiant disponible</p>';
        return;
    }

    students.forEach(function(student) {
        // Stocker dans la liste globale pour le menu burger
        if (!allStudents.find(s => s.id === student.id)) {
            allStudents.push(student);
        }

        const studentItem = document.createElement('div');
        studentItem.className = 'student-item';
        studentItem.dataset.studentId = student.id;

        const title = document.createElement('h3');
        title.textContent = student.title;
        studentItem.appendChild(title);

        studentItem.addEventListener('click', function() {
            selectStudent(student.id);
        });

        studentList.appendChild(studentItem);
    });

    // Mettre à jour le menu burger avec les nouveaux étudiants
    updateBurgerStudentList();
}

// Configuration du scroll infini
function setupInfiniteScroll() {
    const studentList = document.getElementById('student-list');

    if (!studentList) return;

    studentList.addEventListener('scroll', function() {
        // Vérifier si on est proche du bas
        const scrollPosition = studentList.scrollTop + studentList.clientHeight;
        const scrollHeight = studentList.scrollHeight;

        // Si on est à 80% du scroll et qu'on n'est pas en train de charger
        if (scrollPosition >= scrollHeight * 0.8 && !isLoading && hasMoreStudents) {
            loadStudents();
        }
    });
}

// Sélectionner un étudiant
function selectStudent(studentId) {
    currentStudentId = studentId;

    // Mettre à jour l'état actif des éléments dans la sidebar
    document.querySelectorAll('.student-item').forEach(function(item) {
        item.classList.remove('active');
    });

    const selectedItem = document.querySelector('[data-student-id="' + studentId + '"]');
    if (selectedItem) {
        selectedItem.classList.add('active');
    }

    // Mettre à jour le menu burger
    document.querySelectorAll('#burgerStudentList a').forEach(function(link) {
        link.classList.remove('active');
        if (link.dataset.studentId == studentId) {
            link.classList.add('active');
        }
    });

    // Charger et afficher le contenu de l'étudiant
    loadStudentContent(studentId);
}

// Charger le contenu d'un étudiant
async function loadStudentContent(studentId) {
    const dataZone = document.querySelector('.data-zone');

    if (!dataZone) return;

    // Afficher un indicateur de chargement
    dataZone.innerHTML = '<div class="loading-spinner">⏳ Chargement...</div>';

    try {
        // Appel à l'API PHP pour récupérer le contenu de l'étudiant
        const response = await fetch('/index.php?action=student&id=' + studentId);

        if (!response.ok) {
            throw new Error('Erreur lors du chargement de l\'étudiant');
        }

        const result = await response.json();

        if (result.success) {
            const student = result.data;

            // Créer l'affichage des données de l'étudiant
            const titleElement = document.createElement('h2');
            titleElement.textContent = 'Étudiant ' + studentId;

            const contentDiv = document.createElement('div');
            contentDiv.className = 'student-data';
            contentDiv.style.marginTop = '2rem';

            // Afficher toutes les propriétés de l'étudiant
            const table = document.createElement('table');
            table.style.width = '100%';
            table.style.borderCollapse = 'collapse';

            for (const [key, value] of Object.entries(student)) {
                const row = document.createElement('tr');
                row.style.borderBottom = '1px solid #e0e0e0';

                const keyCell = document.createElement('td');
                keyCell.style.padding = '0.75rem';
                keyCell.style.fontWeight = 'bold';
                keyCell.style.width = '30%';
                keyCell.textContent = key;

                const valueCell = document.createElement('td');
                valueCell.style.padding = '0.75rem';
                valueCell.textContent = typeof value === 'object' ? JSON.stringify(value) : value;

                row.appendChild(keyCell);
                row.appendChild(valueCell);
                table.appendChild(row);
            }

            contentDiv.appendChild(table);

            dataZone.innerHTML = '';
            dataZone.appendChild(titleElement);
            dataZone.appendChild(contentDiv);

            // Scroll vers le haut du contenu principal
            document.querySelector('.main-content').scrollTop = 0;
        }
    } catch (error) {
        console.error('Erreur:', error);
        dataZone.innerHTML = '<p class="placeholder-message">Erreur lors du chargement de l\'étudiant</p>';
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

// Toggle du sous-menu des étudiants
function toggleStudentSubmenu(event) {
    event.preventDefault();
    const submenu = document.getElementById('burgerStudentList');
    const arrow = event.currentTarget.querySelector('.submenu-arrow');

    submenu.classList.toggle('active');
    arrow.classList.toggle('rotated');
}

// Charger tous les étudiants pour le menu burger
async function loadBurgerStudents() {
    try {
        // Charger tous les étudiants (on peut limiter à 50 pour l'exemple)
        const response = await fetch('/index.php?action=students&page=1&perPage=50');

        if (!response.ok) {
            throw new Error('Erreur lors du chargement des étudiants');
        }

        const result = await response.json();

        if (result.success) {
            allStudents = result.data.students;
            updateBurgerStudentList();
        }
    } catch (error) {
        console.error('Erreur lors du chargement des étudiants pour le menu burger:', error);
    }
}

// Mettre à jour la liste des étudiants dans le menu burger
function updateBurgerStudentList() {
    const burgerStudentList = document.getElementById('burgerStudentList');

    if (!burgerStudentList) return;

    burgerStudentList.innerHTML = '';

    allStudents.forEach(function(student) {
        const li = document.createElement('li');
        const link = document.createElement('a');
        link.href = '#';
        link.textContent = student.title;
        link.dataset.studentId = student.id;
        link.className = 'burger-submenu-link';

        if (student.id === currentStudentId) {
            link.classList.add('active');
        }

        link.onclick = function(e) {
            e.preventDefault();
            selectStudent(student.id);
            closeBurgerMenu();
        };

        li.appendChild(link);
        burgerStudentList.appendChild(li);
    });
}
