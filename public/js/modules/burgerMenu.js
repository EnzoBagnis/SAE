// Module de gestion du menu burger

export class BurgerMenuManager {
    constructor() {
        this.allStudents = [];
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Écouter les mises à jour de la liste des étudiants
        window.addEventListener('studentsUpdated', (e) => {
            this.allStudents = e.detail;
            this.updateStudentList();
        });
    }

    // Toggle du menu burger
    toggleMenu() {
        const burgerNav = document.getElementById('burgerNav');
        const burgerBtn = document.getElementById('burgerBtn');
        const body = document.body;

        burgerNav.classList.toggle('active');
        burgerBtn.classList.toggle('active');

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

    // Fermer le menu burger
    closeMenu() {
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
    toggleStudentSubmenu(event) {
        const submenu = document.getElementById('burgerStudentList');
        const arrow = event.currentTarget.querySelector('.submenu-arrow');

        submenu.classList.toggle('active');
        arrow.classList.toggle('rotated');
    }

    // Mettre à jour la liste des étudiants
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

    // Mettre à jour l'étudiant actif
    updateActiveStudent(studentId) {
        document.querySelectorAll('#burgerStudentList a').forEach((link) => {
            link.classList.remove('active');
            if (link.dataset.studentId === studentId.toString()) {
                link.classList.add('active');
            }
        });
    }
}

