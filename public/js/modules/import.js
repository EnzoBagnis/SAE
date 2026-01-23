/**
 * import.js - Version corrigée (Anti-plantage et Découpage par paquets)
 */

let currentExercisesData = null;
let currentAttemptsData = null;

function openImportModal(resourceId = null) {
    const modal = document.getElementById('importModal');
    if (modal) {
        if (resourceId) modal.dataset.resourceId = resourceId;
        else delete modal.dataset.resourceId;
        modal.style.display = 'block';
        switchImportTab('exercises');
    }
}

function closeImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.style.display = 'none';
        resetImportForm();
    }
}

function switchImportTab(tabName) {
    document.querySelectorAll('.import-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.tab === tabName);
    });
    document.querySelectorAll('.import-tab-content').forEach(content => {
        content.classList.toggle('active', content.id === `${tabName}Tab`);
    });
}

function handleFileSelect(event, type) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            let text = e.target.result.replace(/^\uFEFF/, '').trim();
            const data = JSON.parse(text);
            if (type === 'exercises') {
                currentExercisesData = data;
                displayExercisesPreview(data);
            } else {
                currentAttemptsData = data;
                displayAttemptsPreview(data);
            }
        } catch (error) {
            showImportStatus(`Erreur lecture JSON : ${error.message}`, 'error');
        }
    };
    reader.readAsText(file);
}

/**
 * IMPORT DES TENTATIVES - VERSION ROBUSTE (CHUNKED)
 */
async function importAttempts() {
    if (!currentAttemptsData) {
        showImportStatus('Aucune donnée à importer.', 'error');
        return;
    }

    // Extraction sécurisée de la liste
    let list = [];
    let datasetInfo = null;
    if (Array.isArray(currentAttemptsData)) {
        list = currentAttemptsData;
    } else if (typeof currentAttemptsData === 'object' && currentAttemptsData !== null) {
        list = currentAttemptsData.attempts || currentAttemptsData.data || [];
        datasetInfo = currentAttemptsData.dataset_info || null;
    }

    if (list.length === 0) {
        showImportStatus('Le fichier est vide ou mal formaté.', 'error');
        return;
    }

    const modal = document.getElementById('importModal');
    let resourceId = modal?.dataset.resourceId || new URLSearchParams(window.location.search).get('id');

    // CONFIGURATION : On envoie par paquets de 50 pour être sûr que Alwaysdata accepte
    const CHUNK_SIZE = 50;
    let totalAdded = 0;

    showImportStatus(`Démarrage de l'import (${list.length} lignes)...`, 'warning');

    try {
        for (let i = 0; i < list.length; i += CHUNK_SIZE) {
            const chunk = list.slice(i, i + CHUNK_SIZE);
            const progress = Math.min(i + CHUNK_SIZE, list.length);

            showImportStatus(`Import : ${progress} / ${list.length} <span class="loading-spinner"></span>`, 'warning');

            const payload = {
                attempts: chunk,
                resource_id: resourceId,
                dataset_info: (i === 0) ? datasetInfo : null // Uniquement au premier paquet
            };

            const response = await fetch(`${window.BASE_URL}/api_import_attempts.php${resourceId ? '?id='+resourceId : ''}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            // On vérifie si la réponse est bien du JSON
            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error("Le serveur a répondu :", text);
                throw new Error("Le serveur a renvoyé une erreur (fichier trop gros ou erreur PHP).");
            }

            if (!response.ok) {
                throw new Error(result?.error || `Erreur serveur ${response.status}`);
            }

            totalAdded += (result?.added_count || chunk.length);
        }

        showImportStatus(`✓ Import réussi ! ${totalAdded} lignes traitées.`, 'success');
        setTimeout(() => window.location.reload(), 2000);

    } catch (error) {
        console.error('Erreur import:', error);
        showImportStatus(`Erreur : ${error.message}`, 'error');
        // NOTE: J'ai supprimé le localStorage.setItem qui faisait planter votre navigateur ici.
    }
}

/**
 * IMPORT DES EXERCICES - VERSION ROBUSTE (CHUNKED)
 */
async function importExercises() {
    if (!currentExercisesData) {
        showImportStatus('Aucun exercice à importer.', 'error');
        return;
    }

    let list = Array.isArray(currentExercisesData) ? currentExercisesData : (currentExercisesData.exercises || []);
    const modal = document.getElementById('importModal');
    const resourceId = modal?.dataset.resourceId;

    const CHUNK_SIZE = 50;
    showImportStatus(`Importation des exercices...`, 'warning');

    try {
        for (let i = 0; i < list.length; i += CHUNK_SIZE) {
            const chunk = list.slice(i, i + CHUNK_SIZE);
            const payload = {
                exercises: chunk.map(ex => ({ ...ex, resource_id: resourceId || ex.resource_id }))
            };

            const response = await fetch(`${window.BASE_URL}/api_import_exercises.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error("Erreur lors de l'envoi d'un paquet d'exercices.");
        }
        showImportStatus(`✓ Import terminé avec succès !`, 'success');
        setTimeout(() => window.location.reload(), 2000);
    } catch (error) {
        showImportStatus(`Erreur : ${error.message}`, 'error');
    }
}

// Utilitaires d'aperçu
function displayExercisesPreview(data) {
    const preview = document.getElementById('exercisesPreview');
    if (!preview) return;
    const list = Array.isArray(data) ? data : (data.exercises || []);
    preview.querySelector('.preview-content').innerHTML = `Exercices trouvés : ${list.length}`;
    preview.style.display = 'block';
}

function displayAttemptsPreview(data) {
    const preview = document.getElementById('attemptsPreview');
    if (!preview) return;
    const list = Array.isArray(data) ? data : (data.attempts || []);
    preview.querySelector('.preview-content').innerHTML = `Tentatives trouvées : ${list.length}`;
    preview.style.display = 'block';
}

function showImportStatus(message, type) {
    const status = document.getElementById('importStatus');
    if (status) {
        status.innerHTML = message;
        status.className = 'import-status ' + type;
        status.style.display = 'block';
    }
}

function resetImportForm() {
    currentExercisesData = null;
    currentAttemptsData = null;
    ['exercisesFileInput', 'attemptsFileInput'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    document.getElementById('exercisesPreview').style.display = 'none';
    document.getElementById('attemptsPreview').style.display = 'none';
    document.getElementById('importStatus').style.display = 'none';
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('click', (e) => {
        if (e.target.id === 'importModal') closeImportModal();
    });
});