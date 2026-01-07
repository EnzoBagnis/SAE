// Module de gestion du menu burger

export class BurgerMenuManager {
    constructor() {
        this.allStudents = [];
        this.allExercises = [];
        this.setupEventListeners();
    }

    setupEventListeners() {
        window.addEventListener('studentsUpdated', (e) => {
            this.allStudents = e.detail;
            this.updateStudentList();
        });

        window.addEventListener('exercisesUpdated', (e) => {
            this.allExercises = e.detail;
            this.updateExerciseList();
        });
    }

    toggleMenu() {
        const burgerNav = document.getElementById('burgerNav');
        const burgerBtn = document.getElementById('burgerBtn');
        // Récupérer le bouton interne s'il existe
        const internalBtn = burgerNav.querySelector('.burger-close-internal');
        const body = document.body;

        burgerNav.classList.toggle('active');
        burgerBtn.classList.toggle('active');

        // Si le bouton interne existe, on s'assure qu'il est en mode "croix" (active) quand le menu est ouvert
        if (internalBtn) {
           // En fait, dans le CSS il est déjà "active" par défaut ou par classe hardcodée,
           // mais on peut vouloir s'assurer qu'il a bien l'apparence "croix" (qui est souvent liée à la classe .active sur .burger-menu)
           internalBtn.classList.add('active');
        }

        let overlay = document.querySelector('.burger-overlay');
        if (burgerNav.classList.contains('active')) {
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'burger-overlay active';
                overlay.onclick = () => this.closeMenu();
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

    closeMenu() {
        const burgerNav = document.getElementById('burgerNav');
        const burgerBtn = document.getElementById('burgerBtn');
        const overlay = document.querySelector('.burger-overlay');

        // Récupérer le bouton interne s'il existe
        const internalBtn = burgerNav.querySelector('.burger-close-internal');

        burgerNav.classList.remove('active');
        burgerBtn.classList.remove('active');
        if (internalBtn) {
            internalBtn.classList.add('active'); // Garder l'état "croix"
        }

        if (overlay) {
            overlay.classList.remove('active');
        }
        document.body.style.overflow = '';
    }

    toggleStudentSubmenu(event) {
        const submenu = document.getElementById('burgerStudentList');
        const arrow = event.currentTarget.querySelector('.submenu-arrow');

        submenu.classList.toggle('active');
        arrow.classList.toggle('rotated');
    }

    toggleExerciseSubmenu(event) {
        const submenu = document.getElementById('burgerExerciseList');
        const arrow = event.currentTarget.querySelector('.submenu-arrow');

        if (submenu && arrow) {
            submenu.classList.toggle('active');
            arrow.classList.toggle('rotated');
        }
    }

    updateStudentList() {
        const burgerStudentList = document.getElementById('burgerStudentList');

        if (!burgerStudentList) return;

        burgerStudentList.innerHTML = '';

        this.allStudents.forEach((student) => {
            const li = document.createElement('li');
            const link = document.createElement('a');
            link.href = '#';
            link.textContent = student.title;
            link.dataset.studentId = student.id;
            link.className = 'burger-submenu-link';

            link.onclick = (e) => {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('studentSelected', { detail: student.id }));
                this.closeMenu();
            };

            li.appendChild(link);
            burgerStudentList.appendChild(li);
        });
    }

    updateExerciseList() {
        const burgerExerciseList = document.getElementById('burgerExerciseList');

        if (!burgerExerciseList) return;

        burgerExerciseList.innerHTML = '';

        this.allExercises.forEach((exercise) => {
            const li = document.createElement('li');
            const link = document.createElement('a');
            link.href = '#';
            link.textContent = exercise.funcname || exercise.exo_name || 'Exercice sans nom';
            link.dataset.exerciseId = exercise.id;
            link.className = 'burger-submenu-link';

            link.onclick = (e) => {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('exerciseSelected', { detail: exercise.id }));
                this.closeMenu();
                // Also switch view to exercises if not already
                window.switchListView('exercises');
            };

            li.appendChild(link);
            burgerExerciseList.appendChild(li);
        });
    }

    updateActiveStudent(studentId) {
        document.querySelectorAll('#burgerStudentList a').forEach((link) => {
            link.classList.remove('active');
            if (link.dataset.studentId === studentId.toString()) {
                link.classList.add('active');
            }
        });
    }
}
