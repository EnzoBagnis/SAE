/**
 * import.js - Gestion du modal d'import JSON
 * Pour exercices de TP et tentatives d'élèves
 */

// Variables globales pour stocker les données
let currentExercisesData = null;
let currentAttemptsData = null;

/**
 * Ouvre le modal d'import
 * @param {number|string|null} resourceId - ID de la ressource cible (optionnel)
 */
function openImportModal(resourceId = null) {
    console.log('openImportModal appelée', resourceId);
    const modal = document.getElementById('importModal');
    console.log('Modal trouvé:', modal);

    if (modal) {
        // Stocker l'ID de la ressource si fourni
        if (resourceId) {
            modal.dataset.resourceId = resourceId;
        } else {
            delete modal.dataset.resourceId;
        }

        console.log('Style avant:', modal.style.display);
        modal.style.display = 'block';
        console.log('Style après:', modal.style.display);
        // Réinitialiser à l'onglet exercices
        switchImportTab('exercises');
    } else {
        console.error('Modal #importModal non trouvé dans le DOM !');
    }
}

/**
 * Ferme le modal d'import
 */
function closeImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.style.display = 'none';
        // Réinitialiser les données
        resetImportForm();
    }
}

/**
 * Change d'onglet dans le modal
 */
function switchImportTab(tabName) {
    // Mettre à jour les boutons
    const tabs = document.querySelectorAll('.import-tab');
    tabs.forEach(tab => {
        if (tab.dataset.tab === tabName) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });

    // Mettre à jour le contenu
    const contents = document.querySelectorAll('.import-tab-content');
    contents.forEach(content => {
        content.classList.remove('active');
    });

    const activeContent = document.getElementById(`${tabName}Tab`);
    if (activeContent) {
        activeContent.classList.add('active');
    }
}

/**
 * Gère la sélection de fichier
 */
function handleFileSelect(event, type) {
    const file = event.target.files[0];
    if (!file) return;

    // Vérifier que c'est un fichier JSON
    if (!file.name.endsWith('.json')) {
        showImportStatus('Veuillez sélectionner un fichier JSON valide.', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            // Robustify: remove BOM and trim
            let text = e.target.result;
            if (typeof text === 'string') {
                text = text.replace(/^\uFEFF/, '').trim();
            }

            const data = JSON.parse(text);

            if (type === 'exercises') {
                currentExercisesData = data;
                displayExercisesPreview(data);
            } else if (type === 'attempts') {
                currentAttemptsData = data;
                displayAttemptsPreview(data);
            }
        } catch (error) {
            // Afficher message plus utile au user
            let msg = error && error.message ? error.message : String(error);
            showImportStatus(`Erreur lors de la lecture du fichier: ${msg}. Vérifiez que le fichier est un JSON valide (tableau d'exercices ou objet avec la clé \"exercises\").`, 'error');
            console.error('JSON parse error:', error);
        }
    };
    reader.readAsText(file);
}

/**
 * Affiche l'aperçu des exercices
 */
function displayExercisesPreview(data) {
    const preview = document.getElementById('exercisesPreview');
    const content = preview.querySelector('.preview-content');

    if (!preview || !content) return;

    let html = '';

    if (Array.isArray(data)) {
        html = `<div class="preview-item"><strong>Nombre d'exercices:</strong> ${data.length}</div>`;

        // Afficher les 3 premiers exercices
        const previewCount = Math.min(3, data.length);
        for (let i = 0; i < previewCount; i++) {
            const ex = data[i];
            html += `
                <div class="preview-item">
                    <strong>Exercice ${i + 1}:</strong> ${ex.title || ex.nom || 'Sans titre'}<br>
                    <small>TP: ${ex.tp_id || ex.tp || 'Non spécifié'}</small>
                </div>
            `;
        }

        if (data.length > 3) {
            html += `<div class="preview-item"><em>... et ${data.length - 3} autre(s) exercice(s)</em></div>`;
        }
    } else if (typeof data === 'object') {
        html = `<div class="preview-item"><strong>Type:</strong> Objet JSON</div>`;
        html += `<div class="preview-item"><strong>Clés:</strong> ${Object.keys(data).join(', ')}</div>`;
    }

    content.innerHTML = html;
    preview.style.display = 'block';
}

/**
 * Affiche l'aperçu des tentatives
 */
function displayAttemptsPreview(data) {
    const preview = document.getElementById('attemptsPreview');
    const content = preview.querySelector('.preview-content');

    if (!preview || !content) return;

    let html = '';

    if (Array.isArray(data)) {
        html = `<div class="preview-item"><strong>Nombre de tentatives:</strong> ${data.length}</div>`;

        // Compter les élèves uniques
        const students = new Set(data.map(a => a.student_id || a.eleve_id || a.user_id));
        html += `<div class="preview-item"><strong>Élèves concernés:</strong> ${students.size}</div>`;

        // Afficher les 3 premières tentatives
        const previewCount = Math.min(3, data.length);
        for (let i = 0; i < previewCount; i++) {
            const attempt = data[i];
            html += `
                <div class="preview-item">
                    <strong>Tentative ${i + 1}:</strong><br>
                    <small>
                        Élève: ${attempt.student_id || attempt.eleve_id || 'N/A'} | 
                        TP: ${attempt.tp_id || attempt.tp || 'N/A'} | 
                        Score: ${attempt.score || attempt.note || 'N/A'}
                    </small>
                </div>
            `;
        }

        if (data.length > 3) {
            html += `<div class="preview-item"><em>... et ${data.length - 3} autre(s) tentative(s)</em></div>`;
        }
    } else if (typeof data === 'object') {
        html = `<div class="preview-item"><strong>Type:</strong> Objet JSON</div>`;
        html += `<div class="preview-item"><strong>Clés:</strong> ${Object.keys(data).join(', ')}</div>`;
    }

    content.innerHTML = html;
    preview.style.display = 'block';
}

/**
 * Importe les exercices
 */
async function importExercises() {
    if (!currentExercisesData) {
        showImportStatus('Aucune donnée à importer.', 'error');
        return;
    }

    showImportStatus('Import en cours... <span class="loading-spinner"></span>', 'warning');

    try {
        // Préparer le payload attendu par le serveur
        let payload;
        if (Array.isArray(currentExercisesData)) {
            payload = { exercises: currentExercisesData };
        } else if (typeof currentExercisesData === 'object' && currentExercisesData !== null) {
            // Si l'objet contient déjà la clé exercises, l'utiliser sinon tenter de l'envelopper
            if (Array.isArray(currentExercisesData.exercises)) {
                payload = currentExercisesData;
            } else {
                // Tentative : s'il s'agit d'un tableau sous une autre clé commune, essayer de le détecter
                const possibleKeys = ['data', 'exos', 'exercises_list'];
                let found = false;
                for (const k of possibleKeys) {
                    if (Array.isArray(currentExercisesData[k])) {
                        payload = { exercises: currentExercisesData[k] };
                        found = true;
                        break;
                    }
                }
                if (!found) payload = { exercises: [currentExercisesData] };
            }
        } else {
            throw new Error('Format des données non reconnu');
        }

        // Vérifier si un resourceId est défini dans le modal
        const modal = document.getElementById('importModal');
        const resourceId = modal && modal.dataset.resourceId;

        // Si resourceId existe, l'injecter dans chaque exercice
        if (resourceId && payload.exercises) {
            payload.exercises = payload.exercises.map(ex => ({
                ...ex,
                resource_id: resourceId
            }));
        }

        // Appel API pour importer les exercices
        const response = await fetch('api_import_exercises.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });

        const text = await response.text();
        let result = null;
        try { result = JSON.parse(text); } catch (e) { /* ignore, will handle below */ }

        if (response.ok) {
            const count = (result && (result.count || result.success_count)) || (payload.exercises ? payload.exercises.length : 0);
            showImportStatus(`✓ Import réussi ! ${count} exercice(s) importé(s).`, 'success');

            // Rafraîchir la liste des TPs après 2 secondes
            setTimeout(() => {
                closeImportModal();
                if (typeof loadTPList === 'function') {
                    loadTPList();
                }
                window.location.reload();
            }, 2000);
        } else {
            // essayer de récupérer le message d'erreur du serveur
            const serverMsg = (result && (result.error || (result.errors && result.errors.join('; ')))) || text || 'Erreur lors de l\'import';
            throw new Error(serverMsg);
        }
    } catch (error) {
        console.error('Erreur import exercices:', error);

        // Mode démo: stocker localement
        console.log('Mode démo activé - Données stockées localement');
        localStorage.setItem('exercises_import', JSON.stringify(currentExercisesData));
        showImportStatus(`Erreur serveur ou réseau: ${error.message}. Données sauvegardées localement.`, 'error');

        setTimeout(() => {
            closeImportModal();
        }, 2000);
    }
}

/**
 * Importe les tentatives
 */
async function importAttempts() {
    if (!currentAttemptsData) {
        showImportStatus('Aucune donnée à importer.', 'error');
        return;
    }

    showImportStatus('Import en cours... <span class="loading-spinner"></span>', 'warning');

    try {
        // Préparer le payload attendu par le serveur
        let payload;
        if (Array.isArray(currentAttemptsData)) {
            payload = { attempts: currentAttemptsData };
        } else if (typeof currentAttemptsData === 'object' && currentAttemptsData !== null) {
            if (Array.isArray(currentAttemptsData.attempts)) {
                payload = currentAttemptsData;
            } else {
                // Si l'objet contient dataset_info + attempts sous d'autres clés
                const possibleKeys = ['data', 'attempts_list'];
                let found = false;
                for (const k of possibleKeys) {
                    if (Array.isArray(currentAttemptsData[k])) {
                        payload = { attempts: currentAttemptsData[k] };
                        if (currentAttemptsData.dataset_info) payload.dataset_info = currentAttemptsData.dataset_info;
                        found = true;
                        break;
                    }
                }
                if (!found) payload = { attempts: [currentAttemptsData] };
            }
        } else {
            throw new Error('Format des données non reconnu');
        }

        // Vérifier si un resourceId est défini dans le modal
        const modal = document.getElementById('importModal');
        const resourceId = modal && modal.dataset.resourceId;

        // Si resourceId existe, l'ajouter au payload (peut être utile pour lier au dataset ou autre)
        if (resourceId) {
            payload.resource_id = resourceId;
        }

        // Appel API pour importer les tentatives
        const response = await fetch('api_import_attempts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });

        const text = await response.text();
        let result = null;
        try { result = JSON.parse(text); } catch (e) { /* ignore */ }

        if (response.ok) {
            const count = (result && (result.count || result.success_count)) || (payload.attempts ? payload.attempts.length : 0);
            showImportStatus(`✓ Import réussi ! ${count} tentative(s) importée(s).`, 'success');

            // Fermer le modal après 2 secondes
            setTimeout(() => {
                closeImportModal();
            }, 2000);
        } else {
            const serverMsg = (result && (result.error || (result.errors && result.errors.join('; ')))) || text || 'Erreur lors de l\'import';
            throw new Error(serverMsg);
        }
    } catch (error) {
        console.error('Erreur import tentatives:', error);

        // Mode démo: stocker localement
        console.log('Mode démo activé - Données stockées localement');
        localStorage.setItem('attempts_import', JSON.stringify(currentAttemptsData));
        showImportStatus(`Erreur serveur ou réseau: ${error.message}. Données sauvegardées localement.`, 'error');

        setTimeout(() => {
            closeImportModal();
        }, 2000);
    }
}

/**
 * Affiche un message de statut
 */
function showImportStatus(message, type) {
    const status = document.getElementById('importStatus');
    if (!status) return;

    status.innerHTML = message;
    status.className = 'import-status ' + type;
    status.style.display = 'block';
}

/**
 * Réinitialise le formulaire d'import
 */
function resetImportForm() {
    currentExercisesData = null;
    currentAttemptsData = null;

    const exercisesInput = document.getElementById('exercisesFileInput');
    const attemptsInput = document.getElementById('attemptsFileInput');

    if (exercisesInput) exercisesInput.value = '';
    if (attemptsInput) attemptsInput.value = '';

    const exercisesPreview = document.getElementById('exercisesPreview');
    const attemptsPreview = document.getElementById('attemptsPreview');
    const importStatus = document.getElementById('importStatus');

    if (exercisesPreview) exercisesPreview.style.display = 'none';
    if (attemptsPreview) attemptsPreview.style.display = 'none';
    if (importStatus) importStatus.style.display = 'none';
}

/**
 * Configure le drag & drop
 */
function setupDragAndDrop() {
    ['exercisesDropZone', 'attemptsDropZone'].forEach(zoneId => {
        const dropZone = document.getElementById(zoneId);
        if (!dropZone) return;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('dragover');
            }, false);
        });

        dropZone.addEventListener('drop', function(e) {
            const files = e.dataTransfer.files;
            const type = zoneId === 'exercisesDropZone' ? 'exercises' : 'attempts';
            const inputId = type === 'exercises' ? 'exercisesFileInput' : 'attemptsFileInput';
            const input = document.getElementById(inputId);

            if (input && files.length > 0) {
                input.files = files;
                handleFileSelect({ target: input }, type);
            }
        }, false);
    });
}

/**
 * Fermer le modal en cliquant en dehors
 */
function handleModalOutsideClick(event) {
    const modal = document.getElementById('importModal');
    if (event.target === modal) {
        closeImportModal();
    }
}

/**
 * Initialisation au chargement de la page
 */
function initImportModal() {
    console.log('Import modal initialized');

    // Configurer le drag & drop
    setupDragAndDrop();

    // Ajouter l'événement de clic en dehors du modal
    window.addEventListener('click', handleModalOutsideClick);
}

// Initialiser quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initImportModal);
} else {
    // DOM déjà chargé
    initImportModal();
}