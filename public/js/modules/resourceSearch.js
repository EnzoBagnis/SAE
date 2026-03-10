/**
 * resourceSearch.js
 * Barre de recherche dynamique (TP / Élève) dans la page de détail d'une ressource.
 */

export class ResourceSearch {
    constructor({ baseUrl = '', resourceId = null } = {}) {
        this.base = baseUrl;
        this.rid  = resourceId;

        // Helper that returns the first matching element from a list of possible IDs
        const getEl = (...ids) => {
            for (const id of ids) {
                if (!id) continue;
                const el = document.getElementById(id);
                if (el) return el;
            }
            return null;
        };

        // Support both the new IDs (resourceSearch*) and the legacy ones used in details.php
        this.inputEl  = getEl('resourceSearchInput', 'resourceSearch', 'resource-search-input');
        this.typeEl   = getEl('resourceSearchType', 'searchType');
        this.clearBtn = getEl('resourceClearBtn', 'clearSearchBtn');
        this.resDiv   = getEl('resourceSearchResults', 'searchResults');
        this.resLabel = getEl('rsr-label', null); // not present in legacy view
        this.resList  = getEl('rsr-list', 'studentsList', 'exercisesList');

        // If minimal elements are missing, log and gracefully return (no JS errors on page)
        if (!this.inputEl || !this.resDiv) {
            console.warn('[ResourceSearch] Éléments DOM essentiels introuvables — module désactivé.',
                { input: !!this.inputEl, resDiv: !!this.resDiv, resList: !!this.resList });
            return;
        }

        console.log('[ResourceSearch] Initialisé — RID:', this.rid, '| BASE:', this.base);

        const debounced = this._debounce(() => this._search(), 300);
        this.inputEl.addEventListener('input', debounced);
        this.typeEl?.addEventListener('change', () => {
            if (this.typeEl) {
                if (this.inputEl) this.inputEl.placeholder = this.typeEl.value === 'exercises'
                    ? 'Rechercher un TP par nom…'
                    : 'Rechercher un étudiant par identifiant…';
            }
            debounced();
        });
        this.clearBtn?.addEventListener('click', () => {
            if (this.inputEl) this.inputEl.value = '';
            if (this.resDiv) this.resDiv.style.display = 'none';
            if (this.resList) this.resList.innerHTML = '';
        });
    }

    async _search() {
        const q    = (this.inputEl?.value || '').trim().toLowerCase();
        const type = this.typeEl?.value || 'exercises';

        console.log('[ResourceSearch] search —', { q, type });

        if (!q) {
            if (this.resDiv) this.resDiv.style.display = 'none';
            if (this.resList) this.resList.innerHTML = '';
            return;
        }

        if (this.resDiv) this.resDiv.style.display = 'block';
        if (this.resLabel) this.resLabel.textContent = type === 'exercises' ? 'Travaux Pratiques trouvés :' : 'Étudiants trouvés :';
        if (this.resList) this.resList.innerHTML    = '<li style="color:#888;padding:8px;font-style:italic;">Chargement…</li>';

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
            click: () => {
                const id   = e.exercise_id ?? e.exercice_id;
                const name = e.funcname || e.exo_name || 'TP sans titre';
                // Si on est dans le dashboard (viz-data-zone présente), naviguer sans changer l'URL
                if (typeof window.navigateToExercise === 'function' && document.querySelector('.viz-data-zone')) {
                    if (this.resDiv) this.resDiv.style.display = 'none';
                    if (this.inputEl) this.inputEl.value = '';
                    window.navigateToExercise(id, name);
                } else {
                    window.location.href = `${this.base}/exercises/${id}`;
                }
            }
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
            click: () => {
                const studentId = s.id || s.identifier || s.title;
                const dataZone  = document.querySelector('.viz-data-zone');

                if (dataZone) {
                    // On est dans le dashboard : naviguer directement vers les stats de l'élève
                    if (this.resDiv)  this.resDiv.style.display = 'none';
                    if (this.inputEl) this.inputEl.value = '';

                    const doNavigate = () => {
                        if (typeof window.navigateToStudent === 'function') {
                            window.navigateToStudent(studentId);
                        } else if (window.vizManager) {
                            window.vizManager.renderLevel2Student(dataZone, studentId);
                            dataZone.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    };
                    doNavigate();
                } else {
                    // On est sur une autre page (ex: details.php) : rediriger vers le dashboard
                    const ridPart = this.rid ? `${this.rid}` : '';
                    if (ridPart) {
                        window.location.href = `${this.base}/resources/${ridPart}?open_student=${encodeURIComponent(studentId)}`;
                    }
                }
            }
        })));
    }

    _setList(items) {
        if (!this.resList) return;
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


    async _safeFetch(url) {
        try {
            const resp = await fetch(url, { credentials: 'same-origin' });
            if (!resp.ok) { console.error('[ResourceSearch] HTTP', resp.status, url); return null; }
            const ct = resp.headers.get('content-type') || '';
            const text = await resp.text();
            if (!ct.includes('application/json')) {
                // Try to detect redirects to login or HTML errors and log them
                console.error('[ResourceSearch] non-JSON response', ct, text.slice(0,400));
                return null;
            }
            // Parse JSON safely
            let json;
            try {
                json = JSON.parse(text);
            } catch (err) {
                console.error('[ResourceSearch] JSON parse error', err, text.slice(0,400));
                return null;
            }
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
