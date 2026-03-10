/**
 * resourceSearch.js
 * Barre de recherche dynamique générale (TP + Élève) dans la page de détail d'une ressource.
 */

export class ResourceSearch {
    constructor({ baseUrl = '', resourceId = null } = {}) {
        this.base = baseUrl;
        this.rid  = resourceId;

        const getEl = (...ids) => {
            for (const id of ids) {
                if (!id) continue;
                const el = document.getElementById(id);
                if (el) return el;
            }
            return null;
        };

        this.inputEl  = getEl('resourceSearchInput', 'resourceSearch', 'resource-search-input');
        this.clearBtn = getEl('resourceClearBtn', 'clearSearchBtn');
        this.resDiv   = getEl('resourceSearchResults', 'searchResults');
        this.resLabel = getEl('rsr-label', null);
        this.resList  = getEl('rsr-list', 'studentsList', 'exercisesList');

        if (!this.inputEl || !this.resDiv) {
            console.warn('[ResourceSearch] Éléments DOM essentiels introuvables — module désactivé.',
                { input: !!this.inputEl, resDiv: !!this.resDiv });
            return;
        }

        console.log('[ResourceSearch] Initialisé — RID:', this.rid, '| BASE:', this.base);

        const debounced = this._debounce(() => this._search(), 300);
        this.inputEl.addEventListener('input', debounced);
        this.clearBtn?.addEventListener('click', () => {
            if (this.inputEl) this.inputEl.value = '';
            if (this.resDiv)  this.resDiv.style.display = 'none';
            if (this.resList) this.resList.innerHTML = '';
        });
    }

    async _search() {
        const q = (this.inputEl?.value || '').trim().toLowerCase();

        if (!q) {
            if (this.resDiv)  this.resDiv.style.display = 'none';
            if (this.resList) this.resList.innerHTML = '';
            return;
        }

        if (this.resDiv)   this.resDiv.style.display = 'block';
        if (this.resLabel) this.resLabel.textContent = 'Résultats :';
        if (this.resList)  this.resList.innerHTML = '<li style="color:#888;padding:8px;font-style:italic;">Chargement…</li>';

        const [dataEx, dataSt] = await Promise.all([
            this._safeFetch(`${this.base}/api/dashboard/exercises${this.rid ? '?resource_id=' + this.rid : ''}`),
            this._safeFetch(`${this.base}/api/dashboard/students?page=1&perPage=100000${this.rid ? '&resource_id=' + this.rid : ''}`)
        ]);

        const results = [];

        // --- TP ---
        const exercises = dataEx?.exercises ?? [];
        exercises.filter(e =>
            `${e.funcname ?? ''} ${e.exo_name ?? ''} ${e.extention ?? ''}`.toLowerCase().includes(q)
        ).forEach(e => {
            const id   = e.exercise_id ?? e.exercice_id;
            const name = e.funcname || e.exo_name || 'TP sans titre';
            const rate = e.success_rate != null ? ` — ${e.success_rate}% réussite` : '';
            results.push({
                text: `📝 ${name}${rate}`,
                click: () => {
                    if (typeof window.navigateToExercise === 'function' && document.querySelector('.viz-data-zone')) {
                        if (this.resDiv)  this.resDiv.style.display = 'none';
                        if (this.inputEl) this.inputEl.value = '';
                        window.navigateToExercise(id, name);
                    } else {
                        window.location.href = `${this.base}/exercises/${id}`;
                    }
                }
            });
        });

        // --- Étudiants ---
        const students = dataSt?.students ?? [];
        students.filter(s =>
            (s.title || s.identifier || s.id || '').toLowerCase().includes(q)
        ).forEach(s => {
            const label     = s.title || s.identifier || s.id;
            const studentId = s.id || s.identifier || s.title;
            results.push({
                text: `👤 ${label}`,
                click: () => {
                    const dataZone = document.querySelector('.viz-data-zone');
                    if (dataZone) {
                        if (this.resDiv)  this.resDiv.style.display = 'none';
                        if (this.inputEl) this.inputEl.value = '';
                        if (typeof window.navigateToStudent === 'function') {
                            window.navigateToStudent(studentId);
                        } else if (window.vizManager) {
                            window.vizManager.renderLevel2Student(dataZone, studentId);
                            dataZone.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    } else if (this.rid) {
                        window.location.href = `${this.base}/resources/${this.rid}?open_student=${encodeURIComponent(studentId)}`;
                    }
                }
            });
        });

        this._setList(results.length ? results : [{ text: 'Aucun résultat.', click: null }]);
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
            const ct   = resp.headers.get('content-type') || '';
            const text = await resp.text();
            if (!ct.includes('application/json')) {
                console.error('[ResourceSearch] non-JSON response', ct, text.slice(0, 400));
                return null;
            }
            let json;
            try { json = JSON.parse(text); } catch (err) {
                console.error('[ResourceSearch] JSON parse error', err, text.slice(0, 400));
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

