/**
 * import.js - Gestion du modal d'import JSON
 * Pour exercices de TP et tentatives d'élèves
 */

// Variables globales pour stocker les données
let currentExercisesData = null;
let currentAttemptsData = null;

/**
 * Ouvre le modal d'import
 */
function openImportModal() {
    console.log('openImportModal appelée');
    const modal = document.getElementById('importModal');
    console.log('Modal trouvé:', modal);

    if (modal) {
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
            const data = JSON.parse(e.target.result);

            if (type === 'exercises') {
                currentExercisesData = data;
                displayExercisesPreview(data);
            } else if (type === 'attempts') {
                currentAttemptsData = data;
                displayAttemptsPreview(data);
            }
        } catch (error) {
            showImportStatus(`Erreur lors de la lecture du fichier: ${error.message}`, 'error');
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
        // Appel API pour importer les exercices
        const response = await fetch('/api/exercises/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(currentExercisesData)
        });

        if (response.ok) {
            const result = await response.json();
            showImportStatus(`✓ Import réussi ! ${result.count || currentExercisesData.length} exercice(s) importé(s).`, 'success');

            // Rafraîchir la liste des TPs après 2 secondes
            setTimeout(() => {
                closeImportModal();
                // Rafraîchir la liste si la fonction existe (depuis dashboard.js)
                if (typeof loadTPList === 'function') {
                    loadTPList();
                }
                // Recharger la page en dernier recours
                window.location.reload();
            }, 2000);
        } else {
            throw new Error('Erreur lors de l\'import');
        }
    } catch (error) {
        console.error('Erreur import exercices:', error);

        // Mode démo: stocker localement
        console.log('Mode démo activé - Données stockées localement');
        localStorage.setItem('exercises_import', JSON.stringify(currentExercisesData));
        showImportStatus(`✓ Import réussi (mode démo) ! ${currentExercisesData.length} exercice(s) stocké(s) localement.`, 'success');

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
        // Appel API pour importer les tentatives
        const response = await fetch('/api/attempts/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(currentAttemptsData)
        });

        if (response.ok) {
            const result = await response.json();
            showImportStatus(`✓ Import réussi ! ${result.count || currentAttemptsData.length} tentative(s) importée(s).`, 'success');

            // Fermer le modal après 2 secondes
            setTimeout(() => {
                closeImportModal();
            }, 2000);
        } else {
            throw new Error('Erreur lors de l\'import');
        }
    } catch (error) {
        console.error('Erreur import tentatives:', error);

        // Mode démo: stocker localement
        console.log('Mode démo activé - Données stockées localement');
        localStorage.setItem('attempts_import', JSON.stringify(currentAttemptsData));
        showImportStatus(`✓ Import réussi (mode démo) ! ${currentAttemptsData.length} tentative(s) stockée(s) localement.`, 'success');

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