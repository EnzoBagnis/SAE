<?php
if (!defined('BASE_URL')) { define('BASE_URL', ''); }
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$user_firstname = $_SESSION['user_firstname'] ?? $_SESSION['prenom'] ?? 'Utilisateur';
$user_lastname  = $_SESSION['user_lastname']  ?? $_SESSION['nom']   ?? '';
$initials = strtoupper(substr($user_firstname, 0, 1) . substr($user_lastname, 0, 1));
$title    = 'StudTraj - IA';

$stats     = $stats     ?? [];
$resources = $resources ?? [];
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
        .ia-page {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1100px;
            margin-left: auto;
            margin-right: auto;
        }
        .ia-page h1 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        .ia-page .subtitle {
            color: #7f8c8d;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        /* Cartes stats */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #e8ecef;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .stat-card h3 {
            margin: 0 0 0.4rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #95a5a6;
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #3498db;
        }
        /* Section */
        .ia-section {
            background: #fff;
            border: 1px solid #e8ecef;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            margin-bottom: 2rem;
        }
        .ia-section h2 {
            margin: 0 0 1.25rem;
            font-size: 1.1rem;
            color: #2c3e50;
            padding-bottom: 0.6rem;
            border-bottom: 2px solid #3498db;
        }
        /* Barre de progression */
        .bar-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }
        .bar-label { width: 110px; font-size: 0.85rem; color: #555; flex-shrink: 0; }
        .bar-track {
            flex: 1;
            background: #ecf0f1;
            border-radius: 6px;
            height: 10px;
            overflow: hidden;
        }
        .bar-fill { height: 100%; background: #3498db; border-radius: 6px; transition: width .4s; }
        .bar-pct { width: 45px; font-size: 0.8rem; color: #7f8c8d; text-align: right; flex-shrink: 0; }
        /* Formulaire analyse */
        .form-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 1rem;
        }
        .form-field { display: flex; flex-direction: column; gap: 0.3rem; }
        .form-field label { font-size: 0.82rem; color: #7f8c8d; }
        .form-field input,
        .form-field select {
            padding: 0.45rem 0.7rem;
            border: 1px solid #dce1e7;
            border-radius: 6px;
            font-size: 0.9rem;
            background: #fff;
        }
        .btn-run {
            background: #3498db;
            color: #fff;
            border: none;
            padding: 0.55rem 1.4rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background .25s;
            align-self: flex-end;
        }
        .btn-run:hover   { background: #2980b9; }
        .btn-run:disabled { background: #95a5a6; cursor: not-allowed; }
        /* Résultats */
        .result-box {
            display: none;
            margin-top: 1.25rem;
        }
        .result-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }
        .result-table th,
        .result-table td {
            padding: 0.65rem 1rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .result-table th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: .4px;
        }
        .result-table tbody tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .badge-high { background: #d5f5e3; color: #1e8449; }
        .badge-mid  { background: #fef9e7; color: #b7950b; }
        .badge-low  { background: #fce4e4; color: #c0392b; }
        .spinner {
            display: inline-block;
            width: 18px; height: 18px;
            border: 3px solid #ccc;
            border-top-color: #3498db;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
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

<div class="ia-page" style="margin-top: 100px;">
    <h1>Analyse par IA</h1>
    <p class="subtitle">
        Visualisez les trajectoires d'apprentissage des étudiants grâce au modèle
        <strong>aes2vec</strong> (Doc2Vec), entraîné sur les tentatives importées.
    </p>

    <!-- ── Statistiques globales ── -->
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

    <!-- ── Répartition des jeux de données ── -->
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

    <!-- ── Analyse d'un étudiant ── -->
    <div class="ia-section">
        <h2>Analyse d'un étudiant</h2>
        <p style="color:#7f8c8d; font-size:.9rem; margin-bottom:1rem;">
            Sélectionnez une ressource et un étudiant pour visualiser ses performances
            et sa position dans l'espace d'embeddings.
        </p>

        <div class="form-row">
            <div class="form-field">
                <label for="selResource">Ressource</label>
                <select id="selResource" onchange="loadStudents()">
                    <option value="">— Toutes —</option>
                    <?php foreach ($resources as $r) : ?>
                    <option value="<?= (int)$r['ressource_id'] ?>">
                        <?= htmlspecialchars($r['ressource_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="selStudent">Étudiant</label>
                <select id="selStudent">
                    <option value="">— Choisir —</option>
                </select>
            </div>
            <button class="btn-run" id="btnAnalyse" onclick="runAnalysis()">
                Analyser
            </button>
        </div>

        <div class="result-box" id="resultBox">
            <table class="result-table" id="resultTable">
                <thead>
                    <tr>
                        <th>Exercice</th>
                        <th>Tentatives</th>
                        <th>Réussies</th>
                        <th>Taux de réussite</th>
                    </tr>
                </thead>
                <tbody id="resultBody"></tbody>
            </table>
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

    function toggleBurgerMenu() {
        document.getElementById('burgerNav')?.classList.toggle('active');
        document.getElementById('burgerBtn')?.classList.toggle('open');
    }

    // Charger la liste des étudiants selon la ressource sélectionnée
    function loadStudents() {
        const rid   = document.getElementById('selResource').value;
        const sel   = document.getElementById('selStudent');
        const url   = BASE_URL + '/api/dashboard/students' + (rid ? '?resource_id=' + rid : '');

        sel.innerHTML = '<option value="">Chargement…</option>';

        fetch(url)
            .then(r => r.json())
            .then(res => {
                sel.innerHTML = '<option value="">— Choisir —</option>';
                const students = res.data?.students ?? [];
                students.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value       = s.id;
                    opt.textContent = s.title ?? s.id;
                    sel.appendChild(opt);
                });
                if (students.length === 0) {
                    sel.innerHTML = '<option value="">Aucun étudiant trouvé</option>';
                }
            })
            .catch(() => {
                sel.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }

    // Lancer l'analyse pour un étudiant
    function runAnalysis() {
        const studentId = document.getElementById('selStudent').value;
        if (!studentId) { alert('Veuillez sélectionner un étudiant.'); return; }

        const btn = document.getElementById('btnAnalyse');
        btn.disabled     = true;
        btn.innerHTML    = '<span class="spinner"></span>Analyse…';

        fetch(BASE_URL + '/api/dashboard/student/' + encodeURIComponent(studentId))
            .then(r => r.json())
            .then(res => {
                btn.disabled  = false;
                btn.innerHTML = 'Analyser';

                const attempts = res.data?.attempts ?? [];
                const tbody    = document.getElementById('resultBody');
                tbody.innerHTML = '';

                if (attempts.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="color:#95a5a6;font-style:italic;">Aucune tentative trouvée.</td></tr>';
                } else {
                    // Regrouper par exercice
                    const byExo = {};
                    attempts.forEach(a => {
                        const key = a.exercice_id ?? a.exercice_name ?? '?';
                        if (!byExo[key]) byExo[key] = { name: a.exercice_name ?? key, total: 0, success: 0 };
                        byExo[key].total++;
                        if (a.result === 1 || a.result === '1' || a.success === true) byExo[key].success++;
                    });

                    Object.values(byExo).forEach(exo => {
                        const rate = exo.total > 0 ? Math.round(exo.success / exo.total * 100) : 0;
                        const cls  = rate >= 70 ? 'badge-high' : (rate >= 40 ? 'badge-mid' : 'badge-low');
                        const tr   = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${htmlEsc(exo.name)}</td>
                            <td>${exo.total}</td>
                            <td>${exo.success}</td>
                            <td><span class="badge ${cls}">${rate}%</span></td>`;
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('resultBox').style.display = 'block';
            })
            .catch(err => {
                btn.disabled  = false;
                btn.innerHTML = 'Analyser';
                alert('Erreur lors de l\'analyse : ' + err);
            });
    }

    function htmlEsc(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Charger tous les étudiants au démarrage
    loadStudents();
</script>
</body>
</html>

