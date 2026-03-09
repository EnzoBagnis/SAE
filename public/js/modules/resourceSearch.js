/**
 * resourceSearch.js
 * Barre de recherche dynamique (TP / Élève) dans la page de détail d'une ressource.
 */

export class ResourceSearch {
    constructor({ baseUrl = '', resourceId = null } = {}) {
        this.base = baseUrl;
        this.rid  = resourceId;

        this.inputEl  = document.getElementById('resourceSearchInput');
        this.typeEl   = document.getElementById('resourceSearchType');
        this.clearBtn = document.getElementById('resourceClearBtn');
        this.resDiv   = document.getElementById('resourceSearchResults');
        this.resLabel = document.getElementById('rsr-label');
        this.resList  = document.getElementById('rsr-list');
        this.modal    = document.getElementById('rsrStudentModal');
        this.mTitle   = document.getElementById('rsrStudentModalTitle');
        this.mBody    = document.getElementById('rsrStudentModalBody');

        if (!this.inputEl || !this.resDiv || !this.resList) {
            console.warn('[ResourceSearch] Éléments DOM introuvables — module désactivé.',
                { input: !!this.inputEl, resDiv: !!this.resDiv, resList: !!this.resList });
            return;
        }

        console.log('[ResourceSearch] Initialisé — RID:', this.rid, '| BASE:', this.base);

        const debounced = this._debounce(() => this._search(), 300);
        this.inputEl.addEventListener('input', debounced);
        this.typeEl.addEventListener('change', () => {
            this.inputEl.placeholder = this.typeEl.value === 'exercises'
                ? 'Rechercher un TP par nom…'
                : 'Rechercher un étudiant par identifiant…';
            debounced();
        });
        this.clearBtn.addEventListener('click', () => {
            this.inputEl.value = '';
            this.resDiv.style.display = 'none';
            this.resList.innerHTML = '';
        });
        this.modal?.addEventListener('click', e => {
            if (e.target === this.modal) this._closeModal();
        });

        window.closeRsrStudentModal = () => this._closeModal();
    }

    async _search() {
        const q    = (this.inputEl.value || '').trim().toLowerCase();
        const type = this.typeEl.value;

        console.log('[ResourceSearch] search —', { q, type });

        if (!q) {
            this.resDiv.style.display = 'none';
            this.resList.innerHTML = '';
            return;
        }

        this.resDiv.style.display = 'block';
        this.resLabel.textContent = type === 'exercises' ? 'Travaux Pratiques trouvés :' : 'Étudiants trouvés :';
        this.resList.innerHTML    = '<li style="color:#888;padding:8px;font-style:italic;">Chargement…</li>';

        if (type === 'exercises') {
            await this._searchExercises(q);
        } else {
            await this._searchStudents(q);
        }
    }

    async _searchExercises(q) {
        let url = `${this.base}/api/dashboard/exercises`;
        if (this.rid) url += `?resource_id=${this.rid}`;

        console.log('[ResourceSearch] fetch →', url);
        const data = await this._safeFetch(url);

        if (!data) {
            this._setList([{ text: '⚠ Impossible de charger les exercices.', click: null }]);
            return;
        }

        const exercises = data.exercises ?? [];
        console.log('[ResourceSearch] exercises:', exercises.length);

        const matches = exercises.filter(e =>
            `${e.funcname ?? ''} ${e.exo_name ?? ''} ${e.extention ?? ''}`.toLowerCase().includes(q)
        );

        if (!matches.length) {
            this._setList([{ text: 'Aucun TP trouvé.', click: null }]);
            return;
        }

        this._setList(matches.map(e => ({
            text:  `${e.funcname || e.exo_name || 'TP sans titre'}${e.success_rate != null ? ' — ' + e.success_rate + '% réussite' : ''}`,
            click: () => { window.location.href = `${this.base}/exercises/${e.exercise_id ?? e.exercice_id}`; }
        })));
    }

    async _searchStudents(q) {
        let url = `${this.base}/api/dashboard/students?page=1&perPage=100000`;
        if (this.rid) url += `&resource_id=${this.rid}`;

        console.log('[ResourceSearch] fetch →', url);
        const data = await this._safeFetch(url);

        if (!data) {
            this._setList([{ text: '⚠ Impossible de charger les étudiants.', click: null }]);
            return;
        }

        const students = data.students ?? [];
        console.log('[ResourceSearch] students:', students.length);

        const matches = students.filter(s =>
            (s.title || s.identifier || s.id || '').toLowerCase().includes(q)
        );

        if (!matches.length) {
            this._setList([{ text: 'Aucun étudiant trouvé.', click: null }]);
            return;
        }

        this._setList(matches.map(s => ({
            text:  s.title || s.identifier || s.id,
            click: () => this._openStudentModal(s.id || s.identifier || s.title)
        })));
    }

    _setList(items) {
        this.resList.innerHTML = '';
        items.forEach(({ text, click }) => {
            const li = document.createElement('li');
            li.textContent = text;
            li.style.cssText = `padding:9px 10px;border-bottom:1px solid #f0f0f0;font-size:.9em;
                                cursor:${click ? 'pointer' : 'default'};transition:background .15s;`;
            if (click) {
                li.style.color = '#3498db';
                li.addEventListener('click', click);
                li.addEventListener('mouseenter', () => { li.style.background = '#f0f7ff'; });
                li.addEventListener('mouseleave', () => { li.style.background = ''; });
            }
            this.resList.appendChild(li);
        });
    }

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
            this.mBody.innerHTML = '<p style="color:#666;padding:1rem;">Aucune tentative pour cet étudiant.</p>';
            return;
        }

        const rate  = stats.success_rate ?? 0;
        const color = rate >= 70 ? '#27ae60' : rate >= 40 ? '#f39c12' : '#e74c3c';

        let html = `
            <div style="display:flex;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
                <div style="flex:1;background:#f8f9fa;border-radius:8px;padding:12px;text-align:center;min-width:80px;">
                    <div style="font-size:1.6rem;font-weight:700;">${stats.total_attempts ?? 0}</div>
                    <div style="font-size:.8rem;color:#777;">Tentatives</div>
                </div>
                <div style="flex:1;background:#f8f9fa;border-radius:8px;padding:12px;text-align:center;min-width:80px;">
                    <div style="font-size:1.6rem;font-weight:700;">${stats.correct_attempts ?? 0}</div>
                    <div style="font-size:.8rem;color:#777;">Réussies</div>
                </div>
                <div style="flex:1;background:#f8f9fa;border-radius:8px;padding:12px;text-align:center;min-width:80px;">
                    <div style="font-size:1.6rem;font-weight:700;color:${color};">${rate}%</div>
                    <div style="font-size:.8rem;color:#777;">Réussite</div>
                </div>
            </div>
            <ul style="list-style:none;padding:0;max-height:360px;overflow-y:auto;">`;

        attempts.forEach(a => {
            html += `<li style="padding:9px 4px;border-bottom:1px solid #f0f0f0;font-size:.9em;">
                        ${a.correct ? '✅' : '❌'} <strong>${a.exercice_name || 'Exercice'}</strong>
                     </li>`;
        });

        html += '</ul>';
        this.mBody.innerHTML = html;
    }

    _closeModal() {
        if (!this.modal) return;
        this.modal.style.display = 'none';
        this.mBody.innerHTML = '';
    }

    async _safeFetch(url) {
        try {
            const resp = await fetch(url, { credentials: 'same-origin' });
            if (!resp.ok) { console.error('[ResourceSearch] HTTP', resp.status, url); return null; }
            const ct = resp.headers.get('content-type') || '';
            if (!ct.includes('application/json')) { console.error('[ResourceSearch] non-JSON response', ct); return null; }
            const json = await resp.json();
            if (!json.success) { console.error('[ResourceSearch] success=false', json); return null; }
            return json.data;
        } catch (err) {
            console.error('[ResourceSearch] fetch error:', err);
            return null;
        }
    }

    _debounce(fn, ms) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }
}
