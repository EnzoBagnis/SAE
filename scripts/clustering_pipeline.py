#!/usr/bin/env python3
# -*- coding: UTF-8 -*-
"""
clustering_pipeline.py
Pipeline Data Science : Doc2Vec -> KMeans -> t-SNE -> scatter plot base64

Usage :
    python clustering_pipeline.py --exercise_id <ID> [--n_clusters 8] [--perplexity 30]

Renvoie un JSON sur stdout :
{
    "success": true,
    "image_base64": "data:image/png;base64,...",
    "n_points": 123,
    "clusters": [0,1,2,...],
    "students": ["stu1","stu2",...],
    "exercise_name": "exo_foo"
}
"""

import sys
import os
import json
import argparse
import base64
import io
import warnings

warnings.filterwarnings("ignore")

# ── Chemins ──────────────────────────────────────────────────────────────────
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(SCRIPT_DIR)

# S'assurer que le dossier utils/ existe pour les fichiers .cor temporaires
os.makedirs(os.path.join(SCRIPT_DIR, 'utils'), exist_ok=True)

# ── Lecture du .env ──────────────────────────────────────────────────────────
def load_env():
    """Lit le fichier config/.env du projet et renvoie un dict."""
    env_path = os.path.join(PROJECT_ROOT, 'config', '.env')
    config = {}
    if not os.path.exists(env_path):
        # Fallback : valeurs par défaut XAMPP
        return {
            'DB_HOST': 'localhost',
            'DB_NAME': 'studtraj',
            'DB_USER': 'root',
            'DB_PASS': '',
        }
    with open(env_path, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#') or line.startswith(';'):
                continue
            if '=' in line:
                key, val = line.split('=', 1)
                key = key.strip()
                val = val.strip().strip('"').strip("'")
                config[key] = val
    return config

# ── Connexion MySQL ──────────────────────────────────────────────────────────
def get_connection(env):
    import mysql.connector
    return mysql.connector.connect(
        host=env.get('DB_HOST', 'localhost'),
        user=env.get('DB_USER', 'root'),
        password=env.get('DB_PASS', ''),
        database=env.get('DB_NAME', 'studtraj'),
        charset='utf8mb4',
    )

# ── Chargement des tentatives depuis la BD ───────────────────────────────────
def load_attempts_from_db(conn, exercise_id):
    """
    Charge les tentatives pour un exercice donné.
    Renvoie une liste de dicts compatibles avec manage.py / aes2vec.py :
        { 'aes2': '...', 'eval_set': 'training'|'test', 'user_id': '...', 'correct': 0|1, ... }
    """
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT
            a.attempt_id,
            a.aes2,
            a.eval_set,
            a.correct,
            a.student_id,
            a.exercice_id,
            e.exercice_name AS exercise_name,
            s.student_identifier AS user_id
        FROM attempts a
        JOIN exercices e ON a.exercice_id = e.exercice_id
        JOIN students s  ON a.student_id  = s.student_id
        WHERE a.exercice_id = %s
          AND a.aes2 IS NOT NULL
          AND a.aes2 != ''
        ORDER BY a.attempt_id
    """, (exercise_id,))
    rows = cursor.fetchall()
    cursor.close()
    return rows

# ── Pipeline principal ───────────────────────────────────────────────────────
def run_pipeline(exercise_id, n_clusters=8, perplexity=30):
    """Exécute le pipeline complet et renvoie le dict résultat."""

    # 1) Charger les données
    env = load_env()
    conn = get_connection(env)
    data = load_attempts_from_db(conn, exercise_id)
    conn.close()

    if len(data) < 5:
        return {
            'success': False,
            'error': f"Pas assez de tentatives avec AES pour cet exercice ({len(data)} trouvées, minimum 5)."
        }

    exercise_name = data[0].get('exercise_name', f'exercise_{exercise_id}')

    # 2) Préparer les données au format attendu par aes2vec
    #    On met toutes les tentatives en "training" pour learnModel,
    #    puis en "test" pour inferVectors (on veut les vecteurs de TOUTES les tentatives).
    for att in data:
        if not att.get('eval_set'):
            att['eval_set'] = 'training'

    # Copie avec toutes les tentatives marquées "training" pour l'entraînement
    train_data = []
    for att in data:
        d = dict(att)
        d['eval_set'] = 'training'
        train_data.append(d)

    # Copie avec toutes les tentatives marquées "test" pour l'inférence
    infer_data = []
    for att in data:
        d = dict(att)
        d['eval_set'] = 'test'
        infer_data.append(d)

    # 3) Doc2Vec : entraînement + inférence
    #    On change le répertoire de travail pour que les .cor soient créés dans scripts/
    old_cwd = os.getcwd()
    os.chdir(SCRIPT_DIR)

    try:
        from aes2vec import learnModel, inferVectors
        import numpy as np

        # Entraîner sur toutes les tentatives
        model = learnModel(
            train_data,
            selectionfield='eval_set',
            selectionsets=['training'],
            valuefield='aes2',
            vsize=100,
            cwindow=5,
            niter=100  # Réduit pour la vitesse en mode interactif
        )

        # Inférer les vecteurs de toutes les tentatives
        vectors = inferVectors(
            model,
            infer_data,
            selectionfield='eval_set',
            selectionsets=['test'],
            valuefield='aes2'
        )

        vectors = np.array(vectors)
    finally:
        os.chdir(old_cwd)

    if len(vectors) < 5:
        return {
            'success': False,
            'error': f"Pas assez de vecteurs générés ({len(vectors)})."
        }

    # 4) KMeans clustering
    from sklearn.cluster import KMeans

    actual_n_clusters = min(n_clusters, len(vectors))
    kmeans = KMeans(n_clusters=actual_n_clusters, random_state=42, n_init=10)
    labels = kmeans.fit_predict(vectors)

    # 5) t-SNE réduction 2D
    from sklearn.manifold import TSNE

    actual_perplexity = min(perplexity, max(1, len(vectors) - 1))
    tsne = TSNE(n_components=2, perplexity=actual_perplexity, random_state=42)
    coords_2d = tsne.fit_transform(vectors)

    # 6) Génération du scatter plot matplotlib → base64
    import matplotlib
    matplotlib.use('Agg')  # Backend sans GUI
    import matplotlib.pyplot as plt
    import matplotlib.cm as cm

    fig, ax = plt.subplots(figsize=(10, 7))

    colors = cm.get_cmap('tab10', actual_n_clusters)
    for cluster_id in range(actual_n_clusters):
        mask = labels == cluster_id
        ax.scatter(
            coords_2d[mask, 0],
            coords_2d[mask, 1],
            c=[colors(cluster_id)],
            label=f'Cluster {cluster_id}',
            alpha=0.7,
            s=50,
            edgecolors='white',
            linewidths=0.5,
        )

    ax.set_title(f'Cartographie des codes — {exercise_name}\n'
                 f'({len(data)} tentatives, {actual_n_clusters} clusters)',
                 fontsize=13, fontweight='bold')
    ax.set_xlabel('t-SNE dimension 1', fontsize=10)
    ax.set_ylabel('t-SNE dimension 2', fontsize=10)
    ax.legend(loc='best', fontsize=8, framealpha=0.9)
    ax.grid(True, alpha=0.3)
    fig.tight_layout()

    # Convertir en base64
    buf = io.BytesIO()
    fig.savefig(buf, format='png', dpi=120, bbox_inches='tight')
    plt.close(fig)
    buf.seek(0)
    img_base64 = 'data:image/png;base64,' + base64.b64encode(buf.read()).decode('utf-8')
    buf.close()

    # 7) Préparer les métadonnées par point (pour le tooltip éventuel côté frontend)
    students = [att.get('user_id', '?') for att in data]
    correct_list = [int(att.get('correct', 0)) for att in data]

    return {
        'success': True,
        'image_base64': img_base64,
        'n_points': len(data),
        'n_clusters': actual_n_clusters,
        'exercise_name': exercise_name,
        'clusters': labels.tolist(),
        'students': students,
        'correct': correct_list,
    }


# ── Point d'entrée CLI ──────────────────────────────────────────────────────
if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Pipeline clustering aes2vec')
    parser.add_argument('--exercise_id', type=int, required=True,
                        help='ID de l\'exercice à analyser')
    parser.add_argument('--n_clusters', type=int, default=8,
                        help='Nombre de clusters KMeans (défaut: 8)')
    parser.add_argument('--perplexity', type=int, default=30,
                        help='Perplexité t-SNE (défaut: 30)')
    args = parser.parse_args()

    try:
        result = run_pipeline(args.exercise_id, args.n_clusters, args.perplexity)
    except Exception as e:
        result = {
            'success': False,
            'error': str(e),
        }

    print(json.dumps(result, ensure_ascii=False))
