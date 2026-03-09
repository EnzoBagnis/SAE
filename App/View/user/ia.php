<?php
if (!defined('BASE_URL')) { define('BASE_URL', ''); }
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$user_firstname = $_SESSION['user_firstname'] ?? $_SESSION['prenom'] ?? 'Utilisateur';
$user_lastname  = $_SESSION['user_lastname']  ?? $_SESSION['nom']   ?? '';
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$title    = 'StudTraj - IA';

$stats     = $stats     ?? [];
$resources = $resources ?? [];
$exercises = $exercises ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>/images/favicon.ico">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/footer.css">
    <style>
        .ia-page { margin-top: 100px; padding: 2rem; max-width: 1200px; margin-left: auto; margin-right: auto; }
        .ia-page h1 { font-size: 1.8rem; color: #2c3e50; margin-bottom: 0.25rem; }
        .ia-page .subtitle { color: #7f8c8d; margin-bottom: 2rem; font-size: 0.95rem; }

        /* Onglets principaux */
        .ia-tabs { display: flex; gap: 0; border-bottom: 2px solid #e8ecef; margin-bottom: 2rem; }
        .ia-tab-btn {
            background: transparent; border: none; padding: 0.85rem 1.5rem; cursor: pointer;
            color: #7f8c8d; font-size: 0.95rem; font-weight: 500; border-bottom: 3px solid transparent;
            transition: all .25s; position: relative; bottom: -2px;
        }
        .ia-tab-btn:hover { color: #2c3e50; background: #f8f9fa; }
        .ia-tab-btn.active { color: #3498db; border-bottom-color: #3498db; font-weight: 600; }
        .ia-tab-content { display: none; }
        .ia-tab-content.active { display: block; }

        /* Cartes stats */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 2.5rem; }
        .stat-card { background: #fff; border: 1px solid #e8ecef; border-radius: 10px; padding: 1.25rem 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); }
        .stat-card h3 { margin: 0 0 0.4rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: .5px; color: #95a5a6; }
        .stat-card .stat-value { font-size: 2rem; font-weight: 700; color: #3498db; }

        /* Sections */
        .ia-section { background: #fff; border: 1px solid #e8ecef; border-radius: 10px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,.06); margin-bottom: 2rem; }
        .ia-section h2 { margin: 0 0 1.25rem; font-size: 1.1rem; color: #2c3e50; padding-bottom: 0.6rem; border-bottom: 2px solid #3498db; }

        /* Barres de progression */
        .bar-row { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
        .bar-label { width: 110px; font-size: 0.85rem; color: #555; flex-shrink: 0; }
        .bar-track { flex: 1; background: #ecf0f1; border-radius: 6px; height: 10px; overflow: hidden; }
        .bar-fill { height: 100%; background: #3498db; border-radius: 6px; transition: width .4s; }
        .bar-pct { width: 60px; font-size: 0.8rem; color: #7f8c8d; text-align: right; flex-shrink: 0; }

        /* Formulaire clustering */
        .cluster-form { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 1.5rem; }
        .form-field { display: flex; flex-direction: column; gap: 0.3rem; }
        .form-field label { font-size: 0.82rem; color: #7f8c8d; font-weight: 500; }
        .form-field select, .form-field input {
            padding: 0.5rem 0.75rem; border: 1px solid #dce1e7; border-radius: 6px;
            font-size: 0.9rem; background: #fff; min-width: 180px;
        }
        .form-field select:focus, .form-field input:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,.15); }

        .btn-generate {
            background: linear-gradient(135deg, #3498db, #2980b9); color: #fff; border: none;
            padding: 0.6rem 1.6rem; border-radius: 6px; cursor: pointer; font-size: 0.9rem;
            font-weight: 600; transition: all .25s; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-generate:hover { background: linear-gradient(135deg, #2980b9, #1f6da0); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(52,152,219,.3); }
        .btn-generate:disabled { background: #95a5a6; cursor: not-allowed; transform: none; box-shadow: none; }

        /* Zone résultat */
        .cluster-result { display: none; margin-top: 1.5rem; }
        .cluster-result.visible { display: block; }

        .loading-overlay {
            display: none; align-items: center; justify-content: center; flex-direction: column;
            padding: 3rem; background: #f8f9fa; border-radius: 10px; border: 2px dashed #dce1e7; margin-top: 1.5rem;
        }
        .loading-overlay.visible { display: flex; }
        .loading-spinner { width: 48px; height: 48px; border: 4px solid #ecf0f1; border-top-color: #3498db; border-radius: 50%; animation: spin .8s linear infinite; margin-bottom: 1rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-text { color: #7f8c8d; font-size: 0.95rem; }
        .loading-detail { color: #bdc3c7; font-size: 0.82rem; margin-top: 0.3rem; }

        .chart-container {
            background: #fff; border-radius: 10px; border: 1px solid #e8ecef; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }
        .chart-container img { width: 100%; height: auto; display: block; }

        .chart-meta {
            display: flex; gap: 1.5rem; padding: 1rem 1.5rem; background: #f8f9fa;
            border-top: 1px solid #e8ecef; flex-wrap: wrap;
        }
        .meta-item { font-size: 0.85rem; color: #555; }
        .meta-item strong { color: #2c3e50; }

        .error-box {
            display: none; padding: 1rem 1.5rem; background: #fce4e4; border: 1px solid #f5c6c6;
            border-radius: 8px; color: #c0392b; font-size: 0.9rem; margin-top: 1rem;
        }
        .error-box.visible { display: block; }

        .empty-msg { color: #95a5a6; font-style: italic; padding: 0.5rem 0; }
    </style>
</head>
<body>
<header class="top-menu">
    <div class="logo"><h1>StudTraj</h1></div>
    <button class="burger-menu" id="burgerBtn" onclick="toggleBurgerMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <nav class="nav-menu">
        <a href="<?= BASE_URL ?>/resources">Ressources</a>
        <a href="<?= BASE_URL ?>/ia" class="active">IA</a>
    </nav>
    <div class="header-right">
        <div class="user-profile">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <span><?= htmlspecialchars($user_firstname) ?> <?= htmlspecialchars($user_lastname) ?></span>
        </div>
        <a href="<?= BASE_URL ?>/auth/logout" class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span class="logout-text">Déconnexion</span>
        </a>
    </div>
</header>

<div class="ia-page">
    <h1>Intelligence Artificielle</h1>
    <p class="subtitle">
        Analyse des trajectoires d'apprentissage par vectorisation <strong>Doc2Vec</strong>,
        clustering <strong>K-Means</strong> et visualisation <strong>t-SNE</strong>.
    </p>

    <!-- ═══ Onglets ═══ -->
    <div class="ia-tabs">
        <button class="ia-tab-btn active" onclick="switchIaTab('overview')">Vue d'ensemble</button>
        <button class="ia-tab-btn" onclick="switchIaTab('clustering')">Cartographie des codes</button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         ONGLET 1 : Vue d'ensemble
         ═══════════════════════════════════════════════════════════════════ -->
    <div id="tab-overview" class="ia-tab-content active">

        <div class="stat-grid">
            <div class="stat-card">
                <h3>Tentatives</h3>
                <div class="stat-value"><?= number_format($stats['total_attempts'] ?? 0) ?></div>
            </div>
            <div class="stat-card">
                <h3>Exercices</h3>
                <div class="stat-value"><?= number_format($stats['total_exercises'] ?? 0) ?></div>
            </div>
            <div class="stat-card">
                <h3>Étudiants</h3>
                <div class="stat-value"><?= number_format($stats['total_students'] ?? 0) ?></div>
            </div>
        </div>

        <div class="ia-section">
            <h2>Répartition des jeux de données</h2>
            <?php
            $sets  = $stats['eval_sets'] ?? [];
            $total = array_sum(array_column($sets, 'count'));
            if (!empty($sets)) :
                foreach ($sets as $s) :
                    $pct = $total > 0 ? round($s['count'] / $total * 100, 1) : 0;
            ?>
            <div class="bar-row">
                <span class="bar-label"><?= htmlspecialchars($s['eval_set'] ?? '—') ?></span>
                <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
                <span class="bar-pct"><?= number_format($s['count']) ?> (<?= $pct ?>%)</span>
            </div>
            <?php   endforeach;
            else : ?>
            <p class="empty-msg">Aucune donnée disponible. Importez des tentatives pour commencer.</p>
            <?php endif; ?>
        </div>

        <div class="ia-section">
            <h2>Exercices analysables</h2>
            <p style="color:#7f8c8d; font-size:.88rem; margin-bottom:1rem;">
                Seuls les exercices dont les tentatives possèdent une séquence AES peuvent être analysés.
            </p>
            <?php if (!empty($exercises)) : ?>
            <table style="width:100%; border-collapse:collapse; font-size:.88rem;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th style="padding:.65rem 1rem; text-align:left; border-bottom:1px solid #ecf0f1; font-weight:600; color:#2c3e50; text-transform:uppercase; font-size:.78rem;">Exercice</th>
                        <th style="padding:.65rem 1rem; text-align:left; border-bottom:1px solid #ecf0f1; font-weight:600; color:#2c3e50; text-transform:uppercase; font-size:.78rem;">Ressource</th>
                        <th style="padding:.65rem 1rem; text-align:center; border-bottom:1px solid #ecf0f1; font-weight:600; color:#2c3e50; text-transform:uppercase; font-size:.78rem;">Tentatives AES</th>
                        <th style="padding:.65rem 1rem; text-align:center; border-bottom:1px solid #ecf0f1; font-weight:600; color:#2c3e50; text-transform:uppercase; font-size:.78rem;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($exercises as $exo) : ?>
                    <tr style="border-bottom:1px solid #f0f0f0;">
                        <td style="padding:.65rem 1rem;"><?= htmlspecialchars($exo['exo_name'] ?? '') ?></td>
                        <td style="padding:.65rem 1rem; color:#7f8c8d;"><?= htmlspecialchars($exo['resource_name'] ?? '—') ?></td>
                        <td style="padding:.65rem 1rem; text-align:center;">
                            <?php $nb = (int)($exo['nb_attempts'] ?? 0); ?>
                            <span style="font-weight:600; color:<?= $nb >= 5 ? '#27ae60' : '#e74c3c' ?>;"><?= $nb ?></span>
                        </td>
                        <td style="padding:.65rem 1rem; text-align:center;">
                            <?php if ($nb >= 5) : ?>
                            <button onclick="goToCluster(<?= (int)$exo['exercise_id'] ?>)"
                                    style="background:#3498db; color:#fff; border:none; padding:.35rem .9rem; border-radius:4px; cursor:pointer; font-size:.82rem;">
                                Cartographier
                            </button>
                            <?php else : ?>
                            <span style="color:#bdc3c7; font-size:.82rem;">Min. 5 tentatives</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else : ?>
            <p class="empty-msg">Aucun exercice trouvé.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════
         ONGLET 2 : Cartographie des codes
         ═══════════════════════════════════════════════════════════════════ -->
    <div id="tab-clustering" class="ia-tab-content">

        <div class="ia-section">
            <h2>Cartographie des codes</h2>
            <p style="color:#7f8c8d; font-size:.9rem; margin-bottom:1.25rem;">
                Sélectionnez un exercice pour regrouper les tentatives des élèves par stratégie/erreur.
                Le pipeline vectorise les séquences AES avec <strong>Doc2Vec</strong>, regroupe avec
                <strong>K-Means</strong>, puis projette en 2D avec <strong>t-SNE</strong>.
            </p>

            <div class="cluster-form">
                <div class="form-field">
                    <label for="clusterResource">Ressource</label>
                    <select id="clusterResource" onchange="filterExercises()">
                        <option value="">— Toutes —</option>
                        <?php foreach ($resources as $r) : ?>
                        <option value="<?= (int)$r['resource_id'] ?>">
                            <?= htmlspecialchars($r['resource_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="clusterExercise">Exercice</label>
                    <select id="clusterExercise">
                        <option value="">— Choisir un exercice —</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="clusterK">Clusters (K)</label>
                    <input type="number" id="clusterK" value="8" min="2" max="20" style="min-width:80px;">
                </div>
                <div class="form-field">
                    <label for="clusterPerplexity">Perplexité t-SNE</label>
                    <input type="number" id="clusterPerplexity" value="30" min="2" max="100" style="min-width:80px;">
                </div>
                <button class="btn-generate" id="btnGenerate" onclick="generateClusters()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    Générer les clusters
                </button>
            </div>

            <!-- Loading -->
            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-spinner"></div>
                <div class="loading-text">Analyse en cours…</div>
                <div class="loading-detail">Entraînement Doc2Vec → K-Means → t-SNE (peut prendre 10-30 secondes)</div>
            </div>

            <!-- Erreur -->
            <div class="error-box" id="errorBox"></div>

            <!-- Résultat -->
            <div class="cluster-result" id="clusterResult">
                <div class="chart-container">
                    <img id="clusterImage" src="" alt="Scatter plot t-SNE des clusters" />
                    <div class="chart-meta" id="chartMeta"></div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Footer -->
<footer class="main-footer">
    <div class="footer-content">
        <p>&copy; <?= date('Y') ?> StudTraj - Tous droits réservés</p>
        <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/mentions-legales">Mentions légales</a></li>
        </ul>
    </div>
</footer>

<script>
const BASE_URL = '<?= BASE_URL ?>';

// ── Données exercices injectées depuis PHP ──────────────────────────────────
const ALL_EXERCISES = <?= json_encode($exercises, JSON_UNESCAPED_UNICODE) ?>;

// ── Onglets ─────────────────────────────────────────────────────────────────
function switchIaTab(tabName) {
    document.querySelectorAll('.ia-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ia-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}

// ── Raccourci depuis le tableau : aller à l'onglet clustering + pré-sélectionner ──
function goToCluster(exerciseId) {
    // Trouver l'exercice
    const exo = ALL_EXERCISES.find(e => parseInt(e.exercise_id) === exerciseId);
    if (exo) {
        document.getElementById('clusterResource').value = exo.resource_id ?? '';
    }
    filterExercises();

    // Basculer vers l'onglet
    document.querySelectorAll('.ia-tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.ia-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-clustering').classList.add('active');
    document.querySelectorAll('.ia-tab-btn')[1].classList.add('active');

    // Pré-sélectionner l'exercice (après un tick pour laisser le select se remplir)
    setTimeout(() => {
        document.getElementById('clusterExercise').value = exerciseId;
    }, 50);
}

// ── Filtrer exercices par ressource ─────────────────────────────────────────
function filterExercises() {
    const rid = document.getElementById('clusterResource').value;
    const sel = document.getElementById('clusterExercise');
    sel.innerHTML = '<option value="">— Choisir un exercice —</option>';

    const filtered = ALL_EXERCISES.filter(e => {
        if (rid && String(e.resource_id) !== String(rid)) return false;
        return parseInt(e.nb_attempts) >= 5;
    });

    filtered.forEach(e => {
        const opt = document.createElement('option');
        opt.value = e.exercise_id;
        const res = e.resource_name ? ` [${e.resource_name}]` : '';
        opt.textContent = `${e.exo_name}${res} — ${e.nb_attempts} tentatives`;
        sel.appendChild(opt);
    });

    if (filtered.length === 0) {
        sel.innerHTML = '<option value="">Aucun exercice analysable</option>';
    }
}

// ── Générer les clusters ────────────────────────────────────────────────────
function generateClusters() {
    const exerciseId = document.getElementById('clusterExercise').value;
    if (!exerciseId) {
        alert('Veuillez sélectionner un exercice.');
        return;
    }

    const nClusters  = parseInt(document.getElementById('clusterK').value) || 8;
    const perplexity = parseInt(document.getElementById('clusterPerplexity').value) || 30;
    const btn        = document.getElementById('btnGenerate');
    const loading    = document.getElementById('loadingOverlay');
    const errorBox   = document.getElementById('errorBox');
    const resultDiv  = document.getElementById('clusterResult');

    // Reset
    btn.disabled = true;
    loading.classList.add('visible');
    errorBox.classList.remove('visible');
    resultDiv.classList.remove('visible');

    fetch(BASE_URL + '/api/ia/clustering', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            exercise_id: parseInt(exerciseId),
            n_clusters: nClusters,
            perplexity: perplexity,
        })
    })
    .then(r => r.json())
    .then(res => {
        loading.classList.remove('visible');
        btn.disabled = false;

        if (res.success) {
            // Afficher l'image
            document.getElementById('clusterImage').src = res.image_base64;

            // Métadonnées
            const meta = document.getElementById('chartMeta');
            meta.innerHTML = `
                <div class="meta-item"><strong>${res.n_points}</strong> tentatives analysées</div>
                <div class="meta-item"><strong>${res.n_clusters}</strong> clusters identifiés</div>
                <div class="meta-item">Exercice : <strong>${htmlEsc(res.exercise_name)}</strong></div>
            `;

            resultDiv.classList.add('visible');
        } else {
            errorBox.textContent = '❌ ' + (res.message || res.error || 'Erreur inconnue');
            errorBox.classList.add('visible');
        }
    })
    .catch(err => {
        loading.classList.remove('visible');
        btn.disabled = false;
        errorBox.textContent = '❌ Erreur réseau : ' + err;
        errorBox.classList.add('visible');
    });
}

function htmlEsc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function toggleBurgerMenu() {
    document.getElementById('burgerNav')?.classList.toggle('active');
    document.getElementById('burgerBtn')?.classList.toggle('open');
}

// Initialiser la liste des exercices
filterExercises();
</script>
</body>
</html>
