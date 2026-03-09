/**
 * resourceSearch.js
 * Barre de recherche dynamique (TP / Élève) dans la page de détail d'une ressource.
 * Consomme les endpoints /api/dashboard/exercises et /api/dashboard/students.
 */

export class ResourceSearch {
    /**
     * @param {object} opts
     * @param {string}      opts.baseUrl     - window.BASE_URL
     * @param {number|null} opts.resourceId  - window.RESOURCE_ID (peut être null)
     */
    constructor({ baseUrl = '', resourceId = null } = {}) {
        this.base = baseUrl;
        this.rid  = resourceId;

        // ── DOM ────────────────────────────────────────────────────────────
        this.inputEl  = document.getElementById('resourceSearchInput');
        this.typeEl   = document.getElementById('resourceSearchType');
        this.clearBtn = document.getElementById('resourceClearBtn');
        this.resDiv   = document.getElementById('resourceSearchResults');
        this.exDiv    = document.getElementById('rsr-exercises');
        this.exList   = document.getElementById('rsr-exercises-list');
        this.stDiv    = document.getElementById('rsr-students');
        this.stList   = document.getElementById('rsr-students-list');
        this.modal    = document.getElementById('rsrStudentModal');
        this.mTitle   = document.getElementById('rsrStudentModalTitle');
        this.mBody    = document.getElementById('rsrStudentModalBody');

        if (!this.inputEl) {
            console.warn('[ResourceSearch] Champ #resourceSearchInput introuvable — module désactivé.');
            return;
        }

        // Binder les méthodes pour garantir le bon contexte `this`
        this._search          = this._search.bind(this);
        this._searchExercises = this._searchExercises.bind(this);
        this._searchStudents  = this._searchStudents.bind(this);
        this._openStudentModal= this._openStudentModal.bind(this);
        this._closeModal      = this._closeModal.bind(this);

        console.log('[ResourceSearch] Initialisé — RID:', this.rid, '| BASE:', this.base);
        this._bindEvents();
        this._exposeGlobals();
    }

    // ── événements ──────────────────────────────────────────────────────────
    _bindEvents() {
        const debounced = this._debounce(this._search, 300);

        this.inputEl.addEventListener('input',  debounced);
        this.inputEl.addEventListener('search', debounced); // bouton clear natif des navigateurs

        this.typeEl.addEventListener('change', () => {
            this.inputEl.placeholder = this.typeEl.value === 'exercises'
                ? 'Rechercher un TP par nom…'
                : 'Rechercher un étudiant par identifiant…';
            debounced();
        });

        this.clearBtn.addEventListener('click', () => {
            this.inputEl.value = '';
            this._reset();
        });

        this.modal?.addEventListener('click', e => {
            if (e.target === this.modal) this._closeModal();
        });
    }

    // ── expose les fonctions nécessaires au HTML (onclick=…) ────────────────
    _exposeGlobals() {
        window.closeRsrStudentModal = () => this._closeModal();
    }

    // ── recherche principale ────────────────────────────────────────────────
    async _search() {
        const q    = this.inputEl.value.trim().toLowerCase();
        const type = this.typeEl.value;

        if (!q) { this._reset(); return; }

        this.resDiv.style.display = 'block';

        if (type === 'exercises') {
            this._show(this.exDiv, this.stDiv);
            this._setLoading(this.exList);
            await this._searchExercises(q);
        } else {
            this._show(this.stDiv, this.exDiv);
            this._setLoading(this.stList);
            await this._searchStudents(q);
        }
    }

    // ── recherche TP ────────────────────────────────────────────────────────
    async _searchExercises(q) {
        let url = `${this.base}/api/dashboard/exercises`;
        if (this.rid) url += `?resource_id=${this.rid}`;

        console.log('[ResourceSearch] Fetch exercises →', url);

        const result = await this._safeFetch(url);
        if (!result) {
            this._appendEmpty(this.exList, '⚠ Impossible de charger les exercices (vérifiez la console).');
            return;
        }

        const exercises = result.exercises ?? [];
        console.log('[ResourceSearch] Exercices reçus :', exercises.length, exercises.slice(0,2));

        const matches = exercises.filter(e =>
            `${e.funcname ?? ''} ${e.exo_name ?? ''} ${e.extention ?? ''}`
                .toLowerCase().includes(q)
        );

        this.exList.innerHTML = '';
        if (!matches.length) {
            this._appendEmpty(this.exList, 'Aucun TP trouvé pour ce mot-clé.');
            return;
        }

        matches.forEach(e => {
            const id   = e.exercise_id ?? e.exercice_id;
            const name = e.funcname || e.exo_name || 'TP sans titre';
            const rate = e.success_rate != null ? ` — ${e.success_rate}% réussite` : '';
            this.exList.appendChild(
                this._liItem(`${name}${rate}`, () => {
                    window.location.href = `${this.base}/exercises/${id}`;
                })
            );
        });
    }

    // ── recherche Élèves ────────────────────────────────────────────────────
    async _searchStudents(q) {
        let url = `${this.base}/api/dashboard/students?page=1&perPage=100000`;
        if (this.rid) url += `&resource_id=${this.rid}`;

        console.log('[ResourceSearch] Fetch students →', url);

        const result = await this._safeFetch(url);
        if (!result) {
            this._appendEmpty(this.stList, '⚠ Impossible de charger les étudiants (vérifiez la console).');
            return;
        }

        const students = result.students ?? [];
        console.log('[ResourceSearch] Étudiants reçus :', students.length, students.slice(0,2));

        const matches = students.filter(s =>
            (s.title || s.identifier || s.id || '').toLowerCase().includes(q)
        );

        this.stList.innerHTML = '';
        if (!matches.length) {
            this._appendEmpty(this.stList, 'Aucun étudiant trouvé pour ce mot-clé.');
            return;
        }

        matches.forEach(s => {
            const label = s.title || s.identifier || s.id;
            const sid   = s.id   || s.identifier || s.title;
            this.stList.appendChild(
                this._liItem(label, () => this._openStudentModal(sid))
            );
        });
    }

    // ── modale détail étudiant ───────────────────────────────────────────────
    async _openStudentModal(studentId) {
        if (!this.modal) return;
        this.modal.style.display = 'block';
        this.mTitle.textContent  = `Étudiant : ${studentId}`;
        this.mBody.innerHTML     = '<p style="color:#888;text-align:center;padding:1rem;">⏳ Chargement…</p>';

        let url = `${this.base}/api/dashboard/student/${encodeURIComponent(studentId)}`;
        if (this.rid) url += `?resource_id=${this.rid}`;

        const data = await this._safeFetch(url);
        if (!data) {
            this.mBody.innerHTML = '<p style="color:#e74c3c;padding:1rem;">Erreur lors du chargement.</p>';
            return;
        }

        const attempts = data.attempts ?? [];
        const stats    = data.stats    ?? {};

        if (!attempts.length) {
            this.mBody.innerHTML = '<p style="color:#666;padding:1rem;">Aucune tentative enregistrée pour cet étudiant dans cette ressource.</p>';
            return;
        }

        const rate  = stats.success_rate ?? 0;
        const color = rate >= 70 ? '#27ae60' : rate >= 40 ? '#f39c12' : '#e74c3c';

        let html = `
            <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
                <div style="flex:1;background:#f8f9fa;border-radius:8px;padding:12px 16px;text-align:center;min-width:90px;">
                    <div style="font-size:1.6rem;font-weight:700;">${stats.total_attempts ?? 0}</div>
                    <div style="font-size:.8rem;color:#777;">Tentatives</div>
                </div>
                <div style="flex:1;background:#f8f9fa;border-radius:8px;padding:12px 16px;text-align:center;min-width:90px;">
                    <div style="font-size:1.6rem;font-weight:700;">${stats.correct_attempts ?? 0}</div>
                    <div style="font-size:.8rem;color:#777;">Réussies</div>
                </div>
                <div style="flex:1;background:#f8f9fa;border-radius:8px;padding:12px 16px;text-align:center;min-width:90px;">
                    <div style="font-size:1.6rem;font-weight:700;color:${color};">${rate}%</div>
                    <div style="font-size:.8rem;color:#777;">Taux de réussite</div>
                </div>
            </div>
            <ul style="list-style:none;padding:0;max-height:360px;overflow-y:auto;">`;

        attempts.forEach(a => {
            const icon = a.correct ? '✅' : '❌';
            html += `<li style="padding:9px 4px;border-bottom:1px solid #f0f0f0;font-size:.9em;">
                        ${icon} <strong>${a.exercice_name || 'Exercice'}</strong>
                     </li>`;
        });

        html += '</ul>';
        this.mBody.innerHTML = html;
    }

    _closeModal() {
        if (!this.modal) return;
        this.modal.style.display = 'none';
        this.mBody.innerHTML     = '';
    }

    // ── utilitaires DOM ──────────────────────────────────────────────────────
    _reset() {
        this.resDiv.style.display = 'none';
        this.exDiv.style.display  = 'none';
        this.stDiv.style.display  = 'none';
        this.exList.innerHTML     = '';
        this.stList.innerHTML     = '';
    }

    _show(visible, hidden) {
        visible.style.display = 'block';
        hidden.style.display  = 'none';
    }

    _setLoading(ul) {
        ul.innerHTML = '<li style="color:#888;padding:8px;font-style:italic;">Chargement…</li>';
    }

    _appendEmpty(ul, msg) {
        ul.innerHTML = '';
        ul.appendChild(this._liItem(msg));
    }

    _liItem(text, onClick = null) {
        const li = document.createElement('li');
        li.style.cssText = `padding:9px 10px;border-bottom:1px solid #f0f0f0;
                            font-size:.9em;cursor:${onClick ? 'pointer' : 'default'};
                            transition:background .15s;`;
        li.textContent = text;
        if (onClick) {
            li.style.color = '#3498db';
            li.addEventListener('click', onClick);
            li.addEventListener('mouseenter', () => { li.style.background = '#f0f7ff'; });
            li.addEventListener('mouseleave', () => { li.style.background = ''; });
        }
        return li;
    }

    // ── fetch sécurisé (ne throw jamais, retourne null en cas d'erreur) ──────
    async _safeFetch(url) {
        try {
            const resp = await fetch(url, { credentials: 'same-origin' });

            if (!resp.ok) {
                console.error('[ResourceSearch] HTTP', resp.status, url);
                return null;
            }

            // Vérifier que la réponse est bien du JSON (pas une redirection HTML)
            const ct = resp.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                console.error('[ResourceSearch] Réponse non-JSON (probablement redirection login). Content-Type:', ct);
                return null;
            }

            const json = await resp.json();
            if (!json.success) {
                console.error('[ResourceSearch] success=false', json);
                return null;
            }
            return json.data;
        } catch (err) {
            console.error('[ResourceSearch] fetch error:', err);
            return null;
        }
    }

    // ── debounce ─────────────────────────────────────────────────────────────
    _debounce(fn, ms) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }
}

