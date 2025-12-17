// Module de gestion de la liste des étudiants et TP

export class StudentListManager {
    constructor() {
        this.currentPage = 1;
        this.studentsPerPage = 50;
        this.isLoading = false;
        this.hasMoreStudents = true;
        this.allStudents = [];
        this.allExercises = [];
        this.currentView = 'students'; // 'students' ou 'exercises'
        this.resourceId = this.getResourceIdFromUrl();
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
            let url = `/index.php?action=students&page=1&perPage=${this.studentsPerPage}`;
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
                this.renderStudentsList();
                window.dispatchEvent(new CustomEvent('studentsUpdated', { detail: this.allStudents }));
            }
        } catch (error) {
            console.error('Erreur:', error);
            sidebarList.innerHTML = '<div class="sidebar-message" style="color: #e74c3c;">Erreur de chargement</div>';
        } finally {
            this.isLoading = false;
        }
    }

    // Afficher la liste des étudiants
    renderStudentsList() {
        const sidebarList = document.getElementById('sidebar-list');
        sidebarList.innerHTML = '';

        if (this.allStudents.length === 0) {
            sidebarList.innerHTML = '<div class="sidebar-message">Aucun étudiant disponible</div>';
            return;
        }

        this.allStudents.forEach((student) => {
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
                this.renderExercisesList();
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

        if (this.allExercises.length === 0) {
            sidebarList.innerHTML = '<div class="sidebar-message">Aucun TP disponible</div>';
            return;
        }

        this.allExercises.forEach((exercise) => {
            const item = document.createElement('div');
            item.className = 'sidebar-list-item';
            item.dataset.exerciseId = exercise.exercise_id;
            // Utiliser funcname si disponible, sinon exo_name
            item.textContent = exercise.funcname || exercise.exo_name || `Exercice #${exercise.exercise_id}`;

            item.addEventListener('click', () => {
                document.querySelectorAll('.sidebar-list-item').forEach(el => el.classList.remove('active'));
                item.classList.add('active');
                window.dispatchEvent(new CustomEvent('exerciseSelected', { detail: exercise.exercise_id }));
            });

            sidebarList.appendChild(item);
        });
    }

    // Configuration du scroll infini pour la sidebar
    setupInfiniteScroll() {
        const sidebarList = document.getElementById('sidebar-list');
        if (!sidebarList) return;

        sidebarList.addEventListener('scroll', () => {
            const scrollPosition = sidebarList.scrollTop + sidebarList.clientHeight;
            const scrollHeight = sidebarList.scrollHeight;

            if (scrollPosition >= scrollHeight * 0.9 && !this.isLoading && this.hasMoreStudents && this.currentView === 'students') {
                // Charger plus si nécessaire
            }
        });
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
