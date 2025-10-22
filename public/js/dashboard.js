// Variables globales
let currentStudentId = null;
let currentPage = 1;
const studentsPerPage = 15;
let isLoading = false;
let hasMoreStudents = true;
let allStudents = []; // Stockage de tous les étudiants pour le menu burger
let currentTab = 'raw'; // 'raw' ou 'visualization'

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
        if (link.dataset.studentId === studentId.toString()) {
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

            // Vider la zone de données
            dataZone.innerHTML = '';

            // Créer l'affichage des données de l'étudiant
            const titleElement = document.createElement('h2');
            titleElement.textContent = userId;
            titleElement.style.marginBottom = '1.5rem';
            dataZone.appendChild(titleElement);

            // Créer les onglets
            const tabsContainer = document.createElement('div');
            tabsContainer.className = 'tabs-container';
            tabsContainer.style.marginBottom = '2rem';
            tabsContainer.style.borderBottom = '2px solid #e0e0e0';
            tabsContainer.style.display = 'flex';
            tabsContainer.style.gap = '0.5rem';

            const rawDataTab = document.createElement('button');
            rawDataTab.className = 'tab-button active';
            rawDataTab.textContent = '📊 Données brutes';
            rawDataTab.onclick = () => switchTab('raw');
            rawDataTab.style.padding = '0.75rem 1.5rem';
            rawDataTab.style.border = 'none';
            rawDataTab.style.background = 'transparent';
            rawDataTab.style.cursor = 'pointer';
            rawDataTab.style.fontSize = '1rem';
            rawDataTab.style.fontWeight = '600';
            rawDataTab.style.color = '#3498db';
            rawDataTab.style.borderBottom = '3px solid #3498db';
            rawDataTab.style.transition = 'all 0.3s ease';

            const visualizationTab = document.createElement('button');
            visualizationTab.className = 'tab-button';
            visualizationTab.textContent = '📈 Visualisation';
            visualizationTab.onclick = () => switchTab('visualization');
            visualizationTab.style.padding = '0.75rem 1.5rem';
            visualizationTab.style.border = 'none';
            visualizationTab.style.background = 'transparent';
            visualizationTab.style.cursor = 'pointer';
            visualizationTab.style.fontSize = '1rem';
            visualizationTab.style.fontWeight = '600';
            visualizationTab.style.color = '#7f8c8d';
            visualizationTab.style.borderBottom = '3px solid transparent';
            visualizationTab.style.transition = 'all 0.3s ease';

            tabsContainer.appendChild(rawDataTab);
            tabsContainer.appendChild(visualizationTab);
            dataZone.appendChild(tabsContainer);

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
                if (attempt.correct === 1 || attempt.correct === '1') {
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
                    valueCell.textContent = typeof detail.value === 'string' ? detail.value : String(detail.value);

                    row.appendChild(labelCell);
                    row.appendChild(valueCell);
                    detailsTable.appendChild(row);
                });

                attemptCard.appendChild(attemptHeader);
                attemptCard.appendChild(detailsTable);

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
                    codeBlock.style.maxHeight = '300px';
                    codeBlock.style.whiteSpace = 'pre-wrap';
                    codeBlock.style.wordBreak = 'break-word';

                    let uploadContent = attempt.upload;
                    if (typeof uploadContent === 'object') {
                        uploadContent = JSON.stringify(uploadContent, null, 2);
                    }
                    codeBlock.textContent = uploadContent;

                    attemptCard.appendChild(codeTitle);
                    attemptCard.appendChild(codeBlock);
                }

                // Test Cases
                if (attempt.test_cases && Array.isArray(attempt.test_cases) && attempt.test_cases.length > 0) {
                    const testCasesTitle = document.createElement('div');
                    testCasesTitle.style.fontWeight = 'bold';
                    testCasesTitle.style.marginTop = '1.5rem';
                    testCasesTitle.style.marginBottom = '0.75rem';
                    testCasesTitle.style.color = '#2c3e50';
                    testCasesTitle.style.fontSize = '1.1rem';
                    testCasesTitle.textContent = '📋 Test Cases de l\'exercice';

                    attemptCard.appendChild(testCasesTitle);

                    const testCasesContainer = document.createElement('div');
                    testCasesContainer.style.display = 'grid';
                    testCasesContainer.style.gap = '0.75rem';
                    testCasesContainer.style.marginTop = '0.5rem';

                    const allPassed = attempt.correct === 1 || attempt.correct === '1';
                    let individualResults = [];

                    if (attempt.aes1 !== undefined) individualResults.push(attempt.aes1 === '1' || attempt.aes1 === 1);
                    if (attempt.aes2 !== undefined) individualResults.push(attempt.aes2 === '1' || attempt.aes2 === 1);
                    if (attempt.aes3 !== undefined) individualResults.push(attempt.aes3 === '1' || attempt.aes3 === 1);

                    let passedCount = 0;

                    attempt.test_cases.forEach((testCase, tcIndex) => {
                        let testPassed = allPassed;
                        if (!allPassed && individualResults.length > tcIndex) {
                            testPassed = individualResults[tcIndex];
                        }

                        if (testPassed) passedCount++;

                        const testCaseCard = document.createElement('div');
                        testCaseCard.style.background = testPassed ? '#f0f9ff' : '#fff5f5';
                        testCaseCard.style.border = testPassed ? '2px solid #bfdbfe' : '2px solid #fecaca';
                        testCaseCard.style.borderRadius = '8px';
                        testCaseCard.style.padding = '1rem';
                        testCaseCard.style.transition = 'all 0.2s';

                        const testHeader = document.createElement('div');
                        testHeader.style.display = 'flex';
                        testHeader.style.justifyContent = 'space-between';
                        testHeader.style.alignItems = 'center';
                        testHeader.style.marginBottom = '0.75rem';

                        const testNumber = document.createElement('span');
                        testNumber.style.fontWeight = 'bold';
                        testNumber.style.color = '#1f2937';
                        testNumber.style.fontSize = '0.95rem';
                        testNumber.textContent = `Test Case #${tcIndex + 1}`;

                        const statusBadge = document.createElement('span');
                        statusBadge.style.padding = '0.3rem 0.75rem';
                        statusBadge.style.borderRadius = '12px';
                        statusBadge.style.fontSize = '0.8rem';
                        statusBadge.style.fontWeight = 'bold';

                        if (testPassed) {
                            statusBadge.style.background = '#10b981';
                            statusBadge.style.color = 'white';
                            statusBadge.textContent = '✓ Réussi';
                        } else {
                            statusBadge.style.background = '#ef4444';
                            statusBadge.style.color = 'white';
                            statusBadge.textContent = '✗ Échoué';
                        }

                        testHeader.appendChild(testNumber);
                        testHeader.appendChild(statusBadge);
                        testCaseCard.appendChild(testHeader);

                        const inputLabel = document.createElement('div');
                        inputLabel.style.fontWeight = '600';
                        inputLabel.style.color = '#4b5563';
                        inputLabel.style.fontSize = '0.85rem';
                        inputLabel.style.marginBottom = '0.4rem';
                        inputLabel.textContent = 'Entrée(s) :';
                        testCaseCard.appendChild(inputLabel);

                        const inputValue = document.createElement('div');
                        inputValue.style.background = '#f9fafb';
                        inputValue.style.padding = '0.6rem';
                        inputValue.style.borderRadius = '4px';
                        inputValue.style.fontFamily = 'monospace';
                        inputValue.style.fontSize = '0.85rem';
                        inputValue.style.color = '#1f2937';
                        inputValue.style.overflowX = 'auto';
                        inputValue.style.whiteSpace = 'pre-wrap';
                        inputValue.style.wordBreak = 'break-word';

                        let formattedInput;
                        if (typeof testCase === 'object' && testCase !== null) {
                            if (testCase.__tuple__ && Array.isArray(testCase.items)) {
                                if (attempt.funcname) {
                                    formattedInput = attempt.funcname + '(' + testCase.items.map(item => JSON.stringify(item)).join(', ') + ')';
                                } else {
                                    formattedInput = 'Arguments: ' + testCase.items.map(item => JSON.stringify(item)).join(', ');
                                }
                            } else {
                                formattedInput = JSON.stringify(testCase, null, 2);
                            }
                        } else {
                            if (attempt.funcname) {
                                formattedInput = attempt.funcname + '(' + JSON.stringify(testCase) + ')';
                            } else {
                                formattedInput = JSON.stringify(testCase);
                            }
                        }

                        inputValue.textContent = formattedInput;
                        testCaseCard.appendChild(inputValue);

                        testCasesContainer.appendChild(testCaseCard);

                        testCaseCard.addEventListener('mouseenter', function() {
                            testCaseCard.style.transform = 'translateY(-2px)';
                            testCaseCard.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                        });
                        testCaseCard.addEventListener('mouseleave', function() {
                            testCaseCard.style.transform = 'translateY(0)';
                            testCaseCard.style.boxShadow = 'none';
                        });
                    });

                    attemptCard.appendChild(testCasesContainer);

                    const totalCount = attempt.test_cases.length;
                    const summary = document.createElement('div');
                    summary.style.marginTop = '1rem';
                    summary.style.padding = '0.75rem 1rem';
                    summary.style.background = passedCount === totalCount ? '#ecfdf5' : '#fef3c7';
                    summary.style.border = passedCount === totalCount ? '2px solid #a7f3d0' : '2px solid #fde68a';
                    summary.style.borderRadius = '8px';
                    summary.style.fontSize = '0.95rem';
                    summary.style.fontWeight = '600';
                    summary.style.color = passedCount === totalCount ? '#065f46' : '#92400e';
                    summary.style.textAlign = 'center';

                    const icon = passedCount === totalCount ? '✅' : '⚠️';
                    summary.textContent = `${icon} Résultat : ${passedCount}/${totalCount} test case(s) réussi(s)`;

                    attemptCard.appendChild(summary);
                }

                attemptsContainer.appendChild(attemptCard);
            });

            // Créer les conteneurs pour les deux vues
            const rawDataContent = document.createElement('div');
            rawDataContent.id = 'raw-data-content';
            rawDataContent.className = 'tab-content active';
            rawDataContent.appendChild(statsDiv);
            rawDataContent.appendChild(attemptsTitle);
            rawDataContent.appendChild(attemptsContainer);

            const visualizationContent = document.createElement('div');
            visualizationContent.id = 'visualization-content';
            visualizationContent.className = 'tab-content';
            visualizationContent.style.display = 'none';

            // Placeholder pour la visualisation
            const visualizationPlaceholder = document.createElement('div');
            visualizationPlaceholder.style.textAlign = 'center';
            visualizationPlaceholder.style.padding = '3rem 1rem';
            visualizationPlaceholder.style.color = '#7f8c8d';
            visualizationPlaceholder.style.fontSize = '1.1rem';
            visualizationPlaceholder.innerHTML = `
                <div style="margin-bottom: 1rem; font-size: 3rem;">📈</div>
                <div style="font-weight: 600; margin-bottom: 0.5rem; color: #2c3e50;">Visualisation des données</div>
                <div>Cet espace est prêt pour votre visualisation personnalisée.</div>
                <div style="margin-top: 1rem; font-size: 0.9rem;">Les données sont disponibles et peuvent être affichées sous forme de graphiques.</div>
            `;
            visualizationContent.appendChild(visualizationPlaceholder);

            dataZone.appendChild(rawDataContent);
            dataZone.appendChild(visualizationContent);

            // Scroll vers le haut du contenu principal
            document.querySelector('.main-content').scrollTop = 0;
        }
    } catch (error) {
        console.error('Erreur:', error);
        dataZone.innerHTML = '<p class="placeholder-message">Erreur lors du chargement de l\'étudiant</p>';
    }
}

// Fonction pour changer d'onglet
function switchTab(tabName) {
    currentTab = tabName;

    // Mettre à jour les boutons d'onglets
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
        button.style.color = '#7f8c8d';
        button.style.borderBottom = '3px solid transparent';
    });

    // Activer le bon bouton
    const activeButton = tabButtons[tabName === 'raw' ? 0 : 1];
    if (activeButton) {
        activeButton.classList.add('active');
        activeButton.style.color = '#3498db';
        activeButton.style.borderBottom = '3px solid #3498db';
    }

    // Mettre à jour les contenus
    const rawContent = document.getElementById('raw-data-content');
    const vizContent = document.getElementById('visualization-content');

    if (tabName === 'raw') {
        if (rawContent) {
            rawContent.style.display = 'block';
            rawContent.classList.add('active');
        }
        if (vizContent) {
            vizContent.style.display = 'none';
            vizContent.classList.remove('active');
        }
    } else {
        if (rawContent) {
            rawContent.style.display = 'none';
            rawContent.classList.remove('active');
        }
        if (vizContent) {
            vizContent.style.display = 'block';
            vizContent.classList.add('active');
        }
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

