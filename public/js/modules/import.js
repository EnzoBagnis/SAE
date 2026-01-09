/**
 * import.js - Gestion du modal d'import JSON avec découpage (Chunks)
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
            showImportStatus(`Erreur JSON : ${error.message}`, 'error');
        }
    };
    reader.readAsText(file);
}

/**
 * IMPORT DES EXERCICES (CORRIGÉ)
 */
async function importExercises() {
    console.log("Données brutes avant import:", currentExercisesData);
    if (!currentExercisesData) return;

    let list = Array.isArray(currentExercisesData) ? currentExercisesData : (currentExercisesData.exercises || currentExercisesData.data || []);
    const modal = document.getElementById('importModal');
    const resourceId = modal?.dataset.resourceId;

    const CHUNK_SIZE = 50;
    for (let i = 0; i < list.length; i += CHUNK_SIZE) {
        const chunk = list.slice(i, i + CHUNK_SIZE);

        // On s'assure que chaque exercice possède les champs minimums attendus par le PHP
        const formattedChunk = chunk.map(ex => ({
            ...ex,
            resource_id: resourceId || ex.resource_id,
            // On double les clés au cas où le PHP attend l'un ou l'autre
            nom: ex.nom || ex.title || "Sans titre",
            title: ex.title || ex.nom || "Sans titre"
        }));

        const payload = { exercises: formattedChunk };
        console.log("Envoi du paquet à l'API:", payload);

        try {
            const response = await fetch('api_import_exercises.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            console.log("Réponse du serveur pour ce paquet:", result);

            if (result.error) {
                showImportStatus(`Erreur : ${result.error}`, 'error');
                return;
            }
        } catch (e) {
            console.error("Erreur lors de l'appel API:", e);
        }
    }
    showImportStatus(`Import terminé. Vérifiez la page.`, 'success');
    setTimeout(() => window.location.reload(), 2000);
}

/**
 * IMPORT DES TENTATIVES (CORRIGÉ)
 */
async function importAttempts() {
    console.log("Démarrage importAttempts...");
    if (!currentAttemptsData) {
        showImportStatus('Aucune donnée chargée.', 'error');
        return;
    }

    let list = Array.isArray(currentAttemptsData) ? currentAttemptsData : (currentAttemptsData.attempts || currentAttemptsData.data || []);

    if (list.length === 0) {
        showImportStatus('Aucune tentative trouvée.', 'error');
        return;
    }

    const modal = document.getElementById('importModal');
    let resourceId = modal?.dataset.resourceId || new URLSearchParams(window.location.search).get('id');

    const CHUNK_SIZE = 100;
    let totalAdded = 0;

    try {
        for (let i = 0; i < list.length; i += CHUNK_SIZE) {
            const chunk = list.slice(i, i + CHUNK_SIZE);
            showImportStatus(`Importation tentatives : ${Math.min(i + CHUNK_SIZE, list.length)} / ${list.length} ...`, 'warning');

            const payload = {
                attempts: chunk,
                resource_id: resourceId,
                dataset_info: (i === 0 && !Array.isArray(currentAttemptsData)) ? currentAttemptsData.dataset_info : null
            };

            const response = await fetch(`api_import_attempts.php${resourceId ? '?id='+resourceId : ''}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error(`Erreur paquet ${i}`);

            const res = await response.json();
            totalAdded += (res.added_count || chunk.length);
        }

        showImportStatus(`✓ Terminé ! ${totalAdded} tentatives traitées.`, 'success');
        setTimeout(() => { window.location.reload(); }, 1500);

    } catch (error) {
        console.error(error);
        showImportStatus(`Erreur : ${error.message}`, 'error');
    }
}

// Fonctions d'aperçu (gardées identiques)
function displayExercisesPreview(data) {
    const preview = document.getElementById('exercisesPreview');
    const content = preview?.querySelector('.preview-content');
    if (!content) return;
    const list = Array.isArray(data) ? data : (data.exercises || []);
    content.innerHTML = `<strong>Exercices détectés :</strong> ${list.length}<br><small>Prêt pour l'import.</small>`;
    preview.style.display = 'block';
}

function displayAttemptsPreview(data) {
    const preview = document.getElementById('attemptsPreview');
    const content = preview?.querySelector('.preview-content');
    if (!content) return;
    const list = Array.isArray(data) ? data : (data.attempts || []);
    content.innerHTML = `<strong>Tentatives détectées :</strong> ${list.length}<br><small>Prêt pour l'import.</small>`;
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

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('click', (e) => {
        if (e.target.id === 'importModal') closeImportModal();
    });
});