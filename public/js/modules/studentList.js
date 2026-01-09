// Module de gestion de la liste des étudiants et TP

export class StudentListManager {
    constructor() {
        this.currentPage = 1;
        this.studentsPerPage = 50;
        this.isLoading = false;
        this.hasMoreStudents = true;
        this.allStudents = [];
        this.allExercises = [];
        this.filteredStudents = [];
        this.filteredExercises = [];
        this.currentView = 'students'; // 'students' ou 'exercises'
        this.resourceId = this.getResourceIdFromUrl();
        this.currentSort = 'name-asc'; // 'name-asc', 'name-desc'
        this.searchTerm = '';
    }

    // Récupérer l'ID de la ressource depuis l'URL
    getResourceIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('resource_id');
    }

    /**
     * Switch between students and exercises view
     * @param {string} view - The view to switch to ('students' or 'exercises')
     */
    switchView(view) {
        this.currentView = view;
        const btnStudents = document.getElementById('btnStudents');
        const btnExercises = document.getElementById('btnExercises');

        if (view === 'students') {
            btnStudents.classList.add('active');
            btnExercises.classList.remove('active');
            this.renderStudentsList();
        } else if (view === 'exercises') {
            btnStudents.classList.remove('active');
            btnExercises.classList.add('active');
            this.loadAndRenderExercises();
        }
    }

    // Charger les étudiants depuis le serveur
    async loadStudents() {
        if (this.isLoading) return;
        this.isLoading = true;

        const sidebarList = document.getElementById('sidebar-list');
        sidebarList.innerHTML = '<div class="sidebar-message">⏳ Chargement...</div>';

        try {
            // Charger TOUS les étudiants en une seule requête (pas de pagination)
            let url = `/index.php?action=students&page=1&perPage=10000`;
            if (this.resourceId) {
                url += `&resource_id=${this.resourceId}`;
            }

            const response = await fetch(url);
            if (!response.ok) throw new Error('Erreur lors du chargement des étudiants');

            const result = await response.json();

            if (result.success) {
                // Trier par ID numérique
                this.allStudents = result.data.students.sort((a, b) => {
                    const idA = parseInt(a.id) || 0;
                    const idB = parseInt(b.id) || 0;
                    return idA - idB;
                });
                this.filteredStudents = [...this.allStudents];
                this.renderStudentsList();
                window.dispatchEvent(new CustomEvent('studentsUpdated', { detail: this.allStudents }));
                console.log(`Chargé ${this.allStudents.length} étudiants`);
            }
        } catch (error) {
            console.error('Erreur:', error);
            sidebarList.innerHTML = '<div class="sidebar-message" style="color: #e74c3c;">Erreur de chargement</div>';
        } finally {
            this.isLoading = false;
        }

        // Initialisation au chargement de la page: charger les exercices aussi pour le menu burger
        if (this.allExercises.length === 0) {
             this.loadExercisesInBackground();
        }
    }

    // Charger les exercices en arrière-plan pour le menu burger
    async loadExercisesInBackground() {
        if (this.allExercises.length > 0) return;

        try {
            let url = '/index.php?action=exercises';
            if (this.resourceId) {
                url += `&resource_id=${this.resourceId}`;
            }

            const response = await fetch(url);
            if (!response.ok) return;

            const result = await response.json();

            if (result.success) {
                this.allExercises = (result.data.exercises || []).sort((a, b) => {
                    const nameA = a.funcname || a.exo_name || '';
                    const nameB = b.funcname || b.exo_name || '';
                    return nameA.localeCompare(nameB);
                });
                this.filteredExercises = [...this.allExercises];
                window.dispatchEvent(new CustomEvent('exercisesUpdated', { detail: this.allExercises }));
                console.log(`Chargé ${this.allExercises.length} exercices`);
            }
        } catch (error) {
            console.error('Erreur chargement background exercices:', error);
        }
    }

    // Afficher la liste des étudiants
    renderStudentsList() {
        const sidebarList = document.getElementById('sidebar-list');
        sidebarList.innerHTML = '';

        // Ajouter le bouton de filtre/recherche
        const filterButton = document.createElement('button');
        filterButton.className = 'filter-search-btn';
        filterButton.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            Filtrer / Rechercher
        `;
        filterButton.addEventListener('click', () => this.openFilterModal('students'));
        sidebarList.appendChild(filterButton);

        const studentsToDisplay = this.filteredStudents.length > 0 ? this.filteredStudents : this.allStudents;

        if (studentsToDisplay.length === 0) {
            sidebarList.innerHTML += '<div class="sidebar-message">Aucun étudiant trouvé</div>';
            return;
        }

        studentsToDisplay.forEach((student) => {
            const item = document.createElement('div');
            item.className = 'sidebar-list-item';
            item.dataset.studentId = student.id;
            item.textContent = student.title || `Étudiant #${student.id}`;

            item.addEventListener('click', () => {
                document.querySelectorAll('.sidebar-list-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');
                window.dispatchEvent(new CustomEvent('studentSelected', { detail: student.id }));
            });

            sidebarList.appendChild(item);
        });
    }

    // Charger et afficher les exercices (TP)
    async loadAndRenderExercises() {
        if (this.allExercises.length > 0) {
            this.renderExercisesList();
            return;
        }

        const sidebarList = document.getElementById('sidebar-list');
        sidebarList.innerHTML = '<div class="sidebar-message">⏳ Chargement...</div>';

        try {
            let url = '/index.php?action=exercises';
            if (this.resourceId) {
                url += `&resource_id=${this.resourceId}`;
            }

            const response = await fetch(url);
            if (!response.ok) throw new Error('Erreur lors du chargement des exercices');

            const result = await response.json();

            if (result.success) {
                // Trier par nom alphabétique (funcname prioritaire, sinon exo_name)
                this.allExercises = (result.data.exercises || []).sort((a, b) => {
                    const nameA = a.funcname || a.exo_name || '';
                    const nameB = b.funcname || b.exo_name || '';
                    return nameA.localeCompare(nameB);
                });
                this.filteredExercises = [...this.allExercises];
                this.renderExercisesList();
                window.dispatchEvent(new CustomEvent('exercisesUpdated', { detail: this.allExercises }));
                console.log(`Chargé ${this.allExercises.length} exercices`);
            }
        } catch (error) {
            console.error('Erreur:', error);
            sidebarList.innerHTML = '<div class="sidebar-message" style="color: #e74c3c;">Erreur de chargement</div>';
        }
    }

    // Afficher la liste des exercices (TP)
    renderExercisesList() {
        const sidebarList = document.getElementById('sidebar-list');
        sidebarList.innerHTML = '';

        // Ajouter le bouton de filtre/recherche
        const filterButton = document.createElement('button');
        filterButton.className = 'filter-search-btn';
        filterButton.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            Filtrer / Rechercher
        `;
        filterButton.addEventListener('click', () => this.openFilterModal('exercises'));
        sidebarList.appendChild(filterButton);

        const exercisesToDisplay = this.filteredExercises.length > 0 ? this.filteredExercises : this.allExercises;

        if (exercisesToDisplay.length === 0) {
            sidebarList.innerHTML += '<div class="sidebar-message">Aucun TP trouvé</div>';
            return;
        }

        exercisesToDisplay.forEach((exercise) => {
            const item = document.createElement('div');
            item.className = 'sidebar-list-item';
            item.dataset.exerciseId = exercise.exercise_id;

            // Safe name resolution
            let displayName = exercise.funcname ? exercise.funcname.trim() : '';
            if (!displayName) {
                displayName = exercise.exo_name ? exercise.exo_name.trim() : '';
            }
            if (!displayName) {
                displayName = `Exercice #${exercise.exercise_id}`;
            }

            item.textContent = displayName;

            item.addEventListener('click', () => {
                document.querySelectorAll('.sidebar-list-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');
                window.dispatchEvent(new CustomEvent('exerciseSelected', { detail: exercise.exercise_id }));
            });

            sidebarList.appendChild(item);
        });
    }

    // Configuration du scroll infini pour la sidebar (désactivé car on charge tout)
    setupInfiniteScroll() {
        // Scroll infini désactivé - tous les éléments sont chargés d'un coup
    }

    // Ouvrir le modal de filtre/recherche
    openFilterModal(type) {
        // Vérifier si le modal existe déjà
        let modal = document.getElementById('filterSearchModal');
        if (!modal) {
            modal = this.createFilterModal();
            document.body.appendChild(modal);
        }

        const modalTitle = modal.querySelector('.filter-modal-title');
        const searchInput = modal.querySelector('#filterSearchInput');
        const sortOptions = modal.querySelector('.sort-options');

        if (type === 'students') {
            modalTitle.textContent = 'Filtrer / Rechercher des étudiants';
            searchInput.placeholder = 'Rechercher un étudiant...';
            searchInput.value = this.searchTerm;

            // Mettre à jour les options de tri (seulement ordre alphabétique)
            sortOptions.innerHTML = `
                <label>
                    <input type="radio" name="sort" value="name-asc" ${this.currentSort === 'name-asc' || this.currentSort === 'default' ? 'checked' : ''}>
                    Ordre croissant (A → Z)
                </label>
                <label>
                    <input type="radio" name="sort" value="name-desc" ${this.currentSort === 'name-desc' ? 'checked' : ''}>
                    Ordre décroissant (Z → A)
                </label>
            `;
        } else {
            modalTitle.textContent = 'Filtrer / Rechercher des TPs';
            searchInput.placeholder = 'Rechercher un TP...';
            searchInput.value = this.searchTerm;

            sortOptions.innerHTML = `
                <label>
                    <input type="radio" name="sort" value="name-asc" ${this.currentSort === 'name-asc' || this.currentSort === 'default' ? 'checked' : ''}>
                    Ordre croissant (A → Z)
                </label>
                <label>
                    <input type="radio" name="sort" value="name-desc" ${this.currentSort === 'name-desc' ? 'checked' : ''}>
                    Ordre décroissant (Z → A)
                </label>
            `;
        }

        modal.dataset.type = type;
        modal.style.display = 'flex';

        // Focus sur l'input de recherche
        setTimeout(() => searchInput.focus(), 100);
    }

    // Créer le modal de filtre/recherche
    createFilterModal() {
        const modal = document.createElement('div');
        modal.id = 'filterSearchModal';
        modal.className = 'filter-search-modal';
        modal.innerHTML = `
            <div class="filter-modal-content">
                <div class="filter-modal-header">
                    <h3 class="filter-modal-title">Filtrer / Rechercher</h3>
                    <button class="filter-modal-close" onclick="this.closest('.filter-search-modal').style.display='none'">&times;</button>
                </div>
                <div class="filter-modal-body">
                    <div class="search-section">
                        <label for="filterSearchInput">Recherche</label>
                        <input type="text" id="filterSearchInput" placeholder="Rechercher..." />
                    </div>
                    <div class="sort-section">
                        <label>Tri</label>
                        <div class="sort-options">
                            <!-- Options de tri dynamiques -->
                        </div>
                    </div>
                </div>
                <div class="filter-modal-footer">
                    <button class="btn-reset">Réinitialiser</button>
                    <button class="btn-apply">Appliquer</button>
                </div>
            </div>
        `;

        // Événements
        modal.querySelector('.btn-apply').addEventListener('click', () => {
            this.applyFilters(modal);
        });

        modal.querySelector('.btn-reset').addEventListener('click', () => {
            this.resetFilters(modal);
        });

        modal.querySelector('#filterSearchInput').addEventListener('input', (e) => {
            this.searchTerm = e.target.value;
        });

        // Fermer en cliquant à l'extérieur
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        return modal;
    }

    // Appliquer les filtres
    applyFilters(modal) {
        const type = modal.dataset.type;
        const searchTerm = modal.querySelector('#filterSearchInput').value.toLowerCase();
        const sortValue = modal.querySelector('input[name="sort"]:checked').value;

        this.searchTerm = searchTerm;
        this.currentSort = sortValue;

        if (type === 'students') {
            // Filtrer les étudiants
            this.filteredStudents = this.allStudents.filter(student => {
                const title = (student.title || '').toLowerCase();
                return title.includes(searchTerm);
            });

            // Trier
            this.filteredStudents = this.sortItems(this.filteredStudents, sortValue, 'students');
            this.renderStudentsList();
        } else {
            // Filtrer les exercices
            this.filteredExercises = this.allExercises.filter(exercise => {
                const name = (exercise.funcname || exercise.exo_name || '').toLowerCase();
                return name.includes(searchTerm);
            });

            // Trier
            this.filteredExercises = this.sortItems(this.filteredExercises, sortValue, 'exercises');
            this.renderExercisesList();
        }

        modal.style.display = 'none';
    }

    // Réinitialiser les filtres
    resetFilters(modal) {
        const type = modal.dataset.type;

        this.searchTerm = '';
        this.currentSort = 'name-asc'; // Tri alphabétique croissant par défaut

        modal.querySelector('#filterSearchInput').value = '';
        const defaultRadio = modal.querySelector('input[name="sort"][value="name-asc"]');
        if (defaultRadio) defaultRadio.checked = true;

        if (type === 'students') {
            this.filteredStudents = [...this.allStudents];
            this.renderStudentsList();
        } else {
            this.filteredExercises = [...this.allExercises];
            this.renderExercisesList();
        }

        modal.style.display = 'none';
    }

    // Trier les éléments
    sortItems(items, sortValue, type) {
        const sorted = [...items];

        if (type === 'students') {
            if (sortValue === 'name-asc') {
                sorted.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
            } else if (sortValue === 'name-desc') {
                sorted.sort((a, b) => (b.title || '').localeCompare(a.title || ''));
            } else {
                // default: par ID
                sorted.sort((a, b) => (parseInt(a.id) || 0) - (parseInt(b.id) || 0));
            }
        } else {
            if (sortValue === 'name-asc') {
                sorted.sort((a, b) => {
                    const nameA = a.funcname || a.exo_name || '';
                    const nameB = b.funcname || b.exo_name || '';
                    return nameA.localeCompare(nameB);
                });
            } else if (sortValue === 'name-desc') {
                sorted.sort((a, b) => {
                    const nameA = a.funcname || a.exo_name || '';
                    const nameB = b.funcname || b.exo_name || '';
                    return nameB.localeCompare(nameA);
                });
            } else {
                // default: alphabétique
                sorted.sort((a, b) => {
                    const nameA = a.funcname || a.exo_name || '';
                    const nameB = b.funcname || b.exo_name || '';
                    return nameA.localeCompare(nameB);
                });
            }
        }

        return sorted;
    }

    // Charger tous les étudiants pour le menu burger
    async loadAllStudents() {
        await this.loadStudents();
    }

    getAllStudents() {
        return this.allStudents;
    }

    getResourceId() {
        return this.resourceId;
    }

    // Méthode obsolète conservée pour compatibilité
    toggleAccordion(accordionId) {
        // Non utilisé dans le nouveau design
    }
}
