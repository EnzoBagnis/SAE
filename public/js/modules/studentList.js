// Module de gestion de la liste des étudiants

export class StudentListManager {
    constructor() {
        this.currentPage = 1;
        this.studentsPerPage = 15;
        this.isLoading = false;
        this.hasMoreStudents = true;
        this.allStudents = [];
        this.resourceId = this.getResourceIdFromUrl();
    }

    // Récupérer l'ID de la ressource depuis l'URL
    getResourceIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('resource_id');
    }

    // Charger les étudiants depuis le serveur
    async loadStudents() {
        if (this.isLoading || !this.hasMoreStudents) return;

        this.isLoading = true;
        const studentList = document.getElementById('student-list');

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading-message';
        loadingDiv.style.textAlign = 'center';
        loadingDiv.style.padding = '1rem';
        loadingDiv.style.color = '#3498db';
        loadingDiv.innerHTML = '⏳ Chargement...';
        studentList.appendChild(loadingDiv);

        try {
            // Construire l'URL avec le resource_id si disponible
            let url = `/index.php?action=students&page=${this.currentPage}&perPage=${this.studentsPerPage}`;
            if (this.resourceId) {
                url += `&resource_id=${this.resourceId}`;
            }

            console.log('🔍 [StudentList] Chargement des étudiants:', url);
            console.log('🔍 [StudentList] Resource ID détecté:', this.resourceId);

            const response = await fetch(url);

            console.log('📡 [StudentList] Réponse HTTP:', response.status);

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des étudiants');
            }

            const result = await response.json();

            console.log('📦 [StudentList] Données reçues:', result);

            if (result.success) {
                console.log('✅ [StudentList] Nombre d\'étudiants:', result.data.students.length);
                this.displayStudents(result.data.students);
                this.hasMoreStudents = result.data.hasMore;
                this.currentPage++;

                if (!this.hasMoreStudents) {
                    const endMessage = document.createElement('p');
                    endMessage.className = 'end-message';
                    endMessage.style.textAlign = 'center';
                    endMessage.style.color = '#7f8c8d';
                    endMessage.style.padding = '1rem';
                    endMessage.style.fontSize = '0.9rem';
                    endMessage.textContent = result.data.total + ' étudiants affichés';
                    studentList.appendChild(endMessage);
                }
            } else {
                console.error('❌ [StudentList] Échec:', result.message);
            }
        } catch (error) {
            console.error('❌ [StudentList] Erreur:', error);
            studentList.innerHTML += '<p style="text-align: center; color: #e74c3c;">Erreur de chargement</p>';
        } finally {
            const loadingMsg = studentList.querySelector('.loading-message');
            if (loadingMsg) {
                loadingMsg.remove();
            }
            this.isLoading = false;
        }
    }

    // Afficher les étudiants dans la sidebar
    displayStudents(students) {
        const studentList = document.getElementById('student-list');

        if (!studentList) return;

        if (students.length === 0 && this.currentPage === 1) {
            studentList.innerHTML = '<p class="empty-message">Aucun étudiant disponible</p>';
            return;
        }

        // Sort students by ID (user_id numeric sort)
        students.sort((a, b) => {
            const idA = parseInt(a.id) || 0;
            const idB = parseInt(b.id) || 0;
            return idA - idB;
        });

        students.forEach((student) => {
            if (!this.allStudents.find(s => s.id === student.id)) {
                this.allStudents.push(student);
            }

            const studentItem = document.createElement('div');
            studentItem.className = 'student-item list-item';
            studentItem.dataset.studentId = student.id;

            const title = document.createElement('span');
            title.className = 'item-title';
            title.textContent = student.title;
            studentItem.appendChild(title);

            studentItem.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('studentSelected', { detail: student.id }));
                // Update active state
                document.querySelectorAll('.student-item').forEach(item => item.classList.remove('active'));
                studentItem.classList.add('active');
            });

            studentList.appendChild(studentItem);
        });

        window.dispatchEvent(new CustomEvent('studentsUpdated', { detail: this.allStudents }));
    }

    // Configuration du scroll infini
    setupInfiniteScroll() {
        const studentList = document.getElementById('student-list');

        if (!studentList) return;

        studentList.addEventListener('scroll', () => {
            const scrollPosition = studentList.scrollTop + studentList.clientHeight;
            const scrollHeight = studentList.scrollHeight;

            if (scrollPosition >= scrollHeight * 0.8 && !this.isLoading && this.hasMoreStudents) {
                this.loadStudents();
            }
        });
    }

    // Charger tous les étudiants pour le menu burger
    async loadAllStudents() {
        try {
            // Construire l'URL avec le resource_id si disponible
            let url = '/index.php?action=students&page=1&perPage=50';
            if (this.resourceId) {
                url += `&resource_id=${this.resourceId}`;
            }

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des étudiants');
            }

            const result = await response.json();

            if (result.success) {
                this.allStudents = result.data.students;
                window.dispatchEvent(new CustomEvent('studentsUpdated', { detail: this.allStudents }));
            }
        } catch (error) {
            console.error('Erreur lors du chargement des étudiants:', error);
        }
    }

    getAllStudents() {
        return this.allStudents;
    }

    getResourceId() {
        return this.resourceId;
    }

    /**
     * Switch between students and exercises view
     * @param {string} view - The view to switch to ('students' or 'exercises')
     */
    switchView(view) {
        const studentsContainer = document.getElementById('students-list-container');
        const exercisesContainer = document.getElementById('exercises-list-container');
        const btnStudents = document.getElementById('btnStudents');
        const btnExercises = document.getElementById('btnExercises');

        if (view === 'students') {
            studentsContainer.style.display = 'block';
            exercisesContainer.style.display = 'none';
            btnStudents.classList.add('active');
            btnExercises.classList.remove('active');
        } else if (view === 'exercises') {
            studentsContainer.style.display = 'none';
            exercisesContainer.style.display = 'block';
            btnStudents.classList.remove('active');
            btnExercises.classList.add('active');
            // Load exercises if not already loaded
            this.loadExercises();
        }
    }

    /**
     * Toggle accordion section
     * @param {string} accordionId - The ID of the accordion content to toggle
     */
    toggleAccordion(accordionId) {
        const accordionContent = document.getElementById(accordionId);
        const accordionArrow = document.getElementById(accordionId + '-arrow');

        if (accordionContent && accordionArrow) {
            accordionContent.classList.toggle('active');
            accordionArrow.classList.toggle('rotated');
        }
    }

    /**
     * Load exercises from the server
     */
    async loadExercises() {
        const exerciseList = document.getElementById('exercise-list');

        // Skip if already loaded
        if (exerciseList.children.length > 0) return;

        exerciseList.innerHTML = '<div class="loading-message" style="text-align: center; padding: 1rem; color: #3498db;">⏳ Chargement...</div>';

        try {
            let url = '/index.php?action=exercises';
            if (this.resourceId) {
                url += `&resource_id=${this.resourceId}`;
            }

            console.log('🔍 [ExerciseList] Chargement des exercices:', url);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des exercices');
            }

            const result = await response.json();
            console.log('📦 [ExerciseList] Données reçues:', result);

            if (result.success) {
                this.displayExercises(result.data.exercises);
            } else {
                exerciseList.innerHTML = '<p style="text-align: center; color: #e74c3c;">Erreur de chargement</p>';
            }
        } catch (error) {
            console.error('❌ [ExerciseList] Erreur:', error);
            exerciseList.innerHTML = '<p style="text-align: center; color: #e74c3c;">Erreur de chargement</p>';
        }
    }

    /**
     * Display exercises in the sidebar
     * @param {Array} exercises - Array of exercise objects
     */
    displayExercises(exercises) {
        const exerciseList = document.getElementById('exercise-list');
        exerciseList.innerHTML = '';

        if (!exercises || exercises.length === 0) {
            exerciseList.innerHTML = '<p class="empty-message">Aucun exercice disponible</p>';
            return;
        }

        // Sort exercises by name
        exercises.sort((a, b) => (a.exo_name || '').localeCompare(b.exo_name || ''));

        exercises.forEach((exercise) => {
            const exerciseItem = document.createElement('div');
            exerciseItem.className = 'list-item exercise-item';
            exerciseItem.dataset.exerciseId = exercise.exercise_id;

            // Difficulty badge
            const difficultyClass = exercise.difficulte || 'moyen';
            const difficultyLabels = {
                'facile': 'Facile',
                'moyen': 'Moyen',
                'difficile': 'Difficile'
            };

            exerciseItem.innerHTML = `
                <div class="item-content">
                    <span class="item-title">${exercise.exo_name || 'Exercice sans nom'}</span>
                    ${exercise.difficulte ? `<span class="difficulty-badge ${difficultyClass}">${difficultyLabels[exercise.difficulte] || exercise.difficulte}</span>` : ''}
                </div>
            `;

            exerciseItem.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('exerciseSelected', { detail: exercise.exercise_id }));
                // Update active state
                document.querySelectorAll('.exercise-item').forEach(item => item.classList.remove('active'));
                exerciseItem.classList.add('active');
            });

            exerciseList.appendChild(exerciseItem);
        });
    }
}
