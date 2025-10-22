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
            const data = result.data;
            const userId = data.userId;
            const attempts = data.attempts;
            const stats = data.stats;

            // Créer l'affichage des données de l'étudiant
            const titleElement = document.createElement('h2');
            titleElement.textContent = userId;
            titleElement.style.marginBottom = '1.5rem';

            // Section des statistiques
            const statsDiv = document.createElement('div');
            statsDiv.className = 'student-stats';
            statsDiv.style.display = 'grid';
            statsDiv.style.gridTemplateColumns = 'repeat(auto-fit, minmax(200px, 1fr))';
            statsDiv.style.gap = '1rem';
            statsDiv.style.marginBottom = '2rem';

            // Cartes de statistiques
            const statCards = [
                { label: 'Tentatives totales', value: stats.total_attempts, color: '#3498db' },
                { label: 'Tentatives réussies', value: stats.correct_attempts, color: '#2ecc71' },
                { label: 'Taux de réussite', value: stats.success_rate + '%', color: '#e74c3c' },
                { label: 'Exercices uniques', value: stats.unique_exercises, color: '#f39c12' }
            ];

            statCards.forEach(stat => {
                const card = document.createElement('div');
                card.style.background = '#f8f9fa';
                card.style.padding = '1.5rem';
                card.style.borderRadius = '8px';
                card.style.borderLeft = '4px solid ' + stat.color;
                card.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';

                const valueEl = document.createElement('div');
                valueEl.style.fontSize = '2rem';
                valueEl.style.fontWeight = 'bold';
                valueEl.style.color = stat.color;
                valueEl.style.marginBottom = '0.5rem';
                valueEl.textContent = stat.value;

                const labelEl = document.createElement('div');
                labelEl.style.color = '#7f8c8d';
                labelEl.style.fontSize = '0.9rem';
                labelEl.textContent = stat.label;

                card.appendChild(valueEl);
                card.appendChild(labelEl);
                statsDiv.appendChild(card);
            });

            // Section des tentatives
            const attemptsTitle = document.createElement('h3');
            attemptsTitle.textContent = 'Historique des tentatives (' + attempts.length + ')';
            attemptsTitle.style.marginTop = '2rem';
            attemptsTitle.style.marginBottom = '1rem';
            attemptsTitle.style.color = '#2c3e50';

            const attemptsContainer = document.createElement('div');
            attemptsContainer.className = 'attempts-container';

            // Afficher chaque tentative
            attempts.forEach((attempt, index) => {
                const attemptCard = document.createElement('div');
                attemptCard.style.background = 'white';
                attemptCard.style.border = '1px solid #e0e0e0';
                attemptCard.style.borderRadius = '8px';
                attemptCard.style.padding = '1.5rem';
                attemptCard.style.marginBottom = '1rem';
                attemptCard.style.boxShadow = '0 2px 4px rgba(0,0,0,0.05)';

                // Header de la tentative
                const attemptHeader = document.createElement('div');
                attemptHeader.style.display = 'flex';
                attemptHeader.style.justifyContent = 'space-between';
                attemptHeader.style.alignItems = 'center';
                attemptHeader.style.marginBottom = '1rem';
                attemptHeader.style.paddingBottom = '1rem';
                attemptHeader.style.borderBottom = '2px solid #f0f0f0';

                const attemptNumber = document.createElement('div');
                attemptNumber.style.fontSize = '1.2rem';
                attemptNumber.style.fontWeight = 'bold';
                attemptNumber.style.color = '#2c3e50';
                attemptNumber.textContent = 'Tentative #' + (index + 1);

                const correctBadge = document.createElement('span');
                correctBadge.style.padding = '0.5rem 1rem';
                correctBadge.style.borderRadius = '20px';
                correctBadge.style.fontSize = '0.85rem';
                correctBadge.style.fontWeight = 'bold';
                if (attempt.correct == 1) {
                    correctBadge.style.background = '#d4edda';
                    correctBadge.style.color = '#155724';
                    correctBadge.textContent = '✓ Réussi';
                } else {
                    correctBadge.style.background = '#f8d7da';
                    correctBadge.style.color = '#721c24';
                    correctBadge.textContent = '✗ Échoué';
                }

                attemptHeader.appendChild(attemptNumber);
                attemptHeader.appendChild(correctBadge);

                // Détails de la tentative
                const detailsTable = document.createElement('table');
                detailsTable.style.width = '100%';
                detailsTable.style.borderCollapse = 'collapse';

                const details = [
                    { label: 'Date', value: attempt.date || 'N/A' },
                    { label: 'Exercice', value: attempt.exercise_name || 'N/A' },
                    { label: 'Extension', value: attempt.extension || 'N/A' },
                    { label: 'Eval Set', value: attempt.eval_set || 'N/A' }
                ];

                details.forEach(detail => {
                    const row = document.createElement('tr');

                    const labelCell = document.createElement('td');
                    labelCell.style.padding = '0.5rem';
                    labelCell.style.fontWeight = 'bold';
                    labelCell.style.color = '#555';
                    labelCell.style.width = '150px';
                    labelCell.textContent = detail.label;

                    const valueCell = document.createElement('td');
                    valueCell.style.padding = '0.5rem';
                    valueCell.style.color = '#333';
                    valueCell.textContent = detail.value;

                    row.appendChild(labelCell);
                    row.appendChild(valueCell);
                    detailsTable.appendChild(row);
                });

                // Code soumis (si présent)
                if (attempt.upload) {
                    const codeTitle = document.createElement('div');
                    codeTitle.style.fontWeight = 'bold';
                    codeTitle.style.marginTop = '1rem';
                    codeTitle.style.marginBottom = '0.5rem';
                    codeTitle.style.color = '#2c3e50';
                    codeTitle.textContent = 'Code soumis :';

                    const codeBlock = document.createElement('pre');
                    codeBlock.style.background = '#f4f4f4';
                    codeBlock.style.padding = '1rem';
                    codeBlock.style.borderRadius = '4px';
                    codeBlock.style.overflow = 'auto';
                    codeBlock.style.fontSize = '0.85rem';
                    codeBlock.style.maxHeight = '200px';
                    codeBlock.textContent = attempt.upload;

                    attemptCard.appendChild(attemptHeader);
                    attemptCard.appendChild(detailsTable);
                    attemptCard.appendChild(codeTitle);
                    attemptCard.appendChild(codeBlock);
                } else {
                    attemptCard.appendChild(attemptHeader);
                    attemptCard.appendChild(detailsTable);
                }

                // AST (si présent)
                if (attempt.aes1 || attempt.aes2 || attempt.aes3) {
                    const astTitle = document.createElement('div');
                    astTitle.style.fontWeight = 'bold';
                    astTitle.style.marginTop = '1rem';
                    astTitle.style.marginBottom = '0.5rem';
                    astTitle.style.color = '#2c3e50';
                    astTitle.textContent = 'AST Info :';

                    const astInfo = document.createElement('div');
                    astInfo.style.fontSize = '0.85rem';
                    astInfo.style.color = '#555';
                    const astData = [];
                    if (attempt.aes1) astData.push('AES1: ' + attempt.aes1);
                    if (attempt.aes2) astData.push('AES2: ' + attempt.aes2);
                    if (attempt.aes3) astData.push('AES3: ' + attempt.aes3);
                    astInfo.textContent = astData.join(' | ');

                    attemptCard.appendChild(astTitle);
                    attemptCard.appendChild(astInfo);
                }

                attemptsContainer.appendChild(attemptCard);
            });

            dataZone.innerHTML = '';
            dataZone.appendChild(titleElement);
            dataZone.appendChild(statsDiv);
            dataZone.appendChild(attemptsTitle);
            dataZone.appendChild(attemptsContainer);

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
