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

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des étudiants');
            }

            const result = await response.json();

            if (result.success) {
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
            }
        } catch (error) {
            console.error('Erreur:', error);
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
            studentList.innerHTML = '<p style="text-align: center; color: #7f8c8d;">Aucun étudiant disponible</p>';
            return;
        }

        students.forEach((student) => {
            if (!this.allStudents.find(s => s.id === student.id)) {
                this.allStudents.push(student);
            }

            const studentItem = document.createElement('div');
            studentItem.className = 'student-item';
            studentItem.dataset.studentId = student.id;

            const title = document.createElement('h3');
            title.textContent = student.title;
            studentItem.appendChild(title);

            studentItem.addEventListener('click', () => {
                window.dispatchEvent(new CustomEvent('studentSelected', { detail: student.id }));
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
}
