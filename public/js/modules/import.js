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

    // Extraction de la liste des exercices
    let exercisesList = [];
    if (Array.isArray(currentExercisesData)) {
        exercisesList = currentExercisesData;
    } else if (currentExercisesData.exercises && Array.isArray(currentExercisesData.exercises)) {
        exercisesList = currentExercisesData.exercises;
    } else {
        showImportStatus('Format de données non supporté.', 'error');
        return;
    }

    const modal = document.getElementById('importModal');
    const resourceId = modal && modal.dataset.resourceId;

    // Paramètres du découpage
    const CHUNK_SIZE = 50; // On envoie 50 exercices à la fois
    const total = exercisesList.length;
    let successCount = 0;

    showImportStatus(`Préparation de l'import (${total} exercices)...`, 'warning');

    try {
        for (let i = 0; i < total; i += CHUNK_SIZE) {
            const chunk = exercisesList.slice(i, i + CHUNK_SIZE);

            // Injecter resource_id dans chaque exercice du paquet
            const processedChunk = chunk.map(ex => ({
                ...ex,
                resource_id: resourceId || ex.resource_id
            }));

            showImportStatus(`Import en cours : ${i} à ${Math.min(i + CHUNK_SIZE, total)} / ${total} <span class="loading-spinner"></span>`, 'warning');

            const response = await fetch('api_import_exercises.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ exercises: processedChunk })
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Erreur au paquet ${i}: ${errorText}`);
            }

            successCount += processedChunk.length;
        }

        showImportStatus(`✓ Import réussi ! ${successCount} exercice(s) importé(s).`, 'success');

        setTimeout(() => {
            closeImportModal();
            window.location.reload();
        }, 2000);

    } catch (error) {
        console.error('Erreur import exercices:', error);
        showImportStatus(`Erreur lors de l'importation : ${error.message}`, 'error');
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

    let attemptsList = [];
    let datasetInfo = null;

    // Extraction des données
    if (Array.isArray(currentAttemptsData)) {
        attemptsList = currentAttemptsData;
    } else if (typeof currentAttemptsData === 'object') {
        attemptsList = currentAttemptsData.attempts || currentAttemptsData.data || [];
        datasetInfo = currentAttemptsData.dataset_info || null;
    }

    const modal = document.getElementById('importModal');
    let resourceId = modal && modal.dataset.resourceId;
    if (!resourceId) {
        const urlParams = new URLSearchParams(window.location.search);
        resourceId = urlParams.get('resource_id') || urlParams.get('id');
    }

    const CHUNK_SIZE = 100; // Les tentatives sont plus légères, on peut en envoyer 100
    const total = attemptsList.length;
    let addedTotal = 0;

    try {
        for (let i = 0; i < total; i += CHUNK_SIZE) {
            const chunk = attemptsList.slice(i, i + CHUNK_SIZE);

            const payload = {
                attempts: chunk,
                resource_id: resourceId,
                is_chunk: true // Optionnel : pour informer le PHP que c'est un envoi partiel
            };

            // On envoie les infos de dataset uniquement avec le premier paquet
            if (i === 0 && datasetInfo) {
                payload.dataset_info = datasetInfo;
            }

            showImportStatus(`Import des tentatives : ${i} / ${total} <span class="loading-spinner"></span>`, 'warning');

            const response = await fetch(`api_import_attempts.php${resourceId ? '?id='+resourceId : ''}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`Erreur serveur au paquet débutant à ${i}`);
            }

            const result = await response.json();
            addedTotal += (result.added_count || 0);
        }

        showImportStatus(`✓ Import terminé ! ${addedTotal} nouvelles tentatives ajoutées.`, 'success');

        setTimeout(() => {
            closeImportModal();
            window.location.reload();
        }, 2500);

    } catch (error) {
        console.error('Erreur import tentatives:', error);
        showImportStatus(`Erreur : ${error.message}`, 'error');
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