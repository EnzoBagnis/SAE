#!/usr/bin/env python3
# -*- coding: UTF-8 -*-
"""
clustering_pipeline.py
Pipeline Data Science : Doc2Vec -> KMeans -> t-SNE -> scatter plot base64

Trois modes d'utilisation :
  1) --from-stdin : reçoit les données JSON depuis stdin (envoyé par PHP)
     Le champ "mode" dans le JSON détermine le comportement :
       - "micro"  (défaut) : clustering + t-SNE pour UN exercice, renvoie coordonnées individuelles
       - "global" : t-SNE global sur TOUS les exercices, renvoie centroïdes par TD
  2) --exercise_id <ID> : se connecte directement à MySQL (usage CLI autonome)

Renvoie un JSON sur stdout.
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


# ── Pipeline MICRO (un seul exercice) ────────────────────────────────────────
def run_pipeline_micro(data, n_clusters=8, perplexity=30, exercise_id=None):
    """Pipeline micro : clustering K-Means + t-SNE pour un exercice.
    Renvoie les coordonnées individuelles avec cluster, user_id, date."""

    if len(data) < 5:
        return {
            'success': False,
            'error': f"Pas assez de tentatives avec AES ({len(data)} trouvées, minimum 5)."
        }

    exercise_name = data[0].get('exercise_name', f'exercise_{exercise_id}')

    # 1) Préparer les données au format attendu par aes2vec
    for att in data:
        if not att.get('eval_set'):
            att['eval_set'] = 'training'

    train_data = [dict(att, eval_set='training') for att in data]
    infer_data = [dict(att, eval_set='test') for att in data]

    # 2) Doc2Vec : entraînement + inférence
    old_cwd = os.getcwd()
    os.chdir(SCRIPT_DIR)

    try:
        from aes2vec import learnModel, inferVectors
        import numpy as np

        model = learnModel(
            train_data,
            selectionfield='eval_set',
            selectionsets=['training'],
            valuefield='aes2',
            vsize=100,
            cwindow=5,
            niter=100
        )

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

    # 3) KMeans clustering
    from sklearn.cluster import KMeans

    actual_n_clusters = min(n_clusters, len(vectors))
    kmeans = KMeans(n_clusters=actual_n_clusters, random_state=42, n_init=10)
    labels = kmeans.fit_predict(vectors)

    # 4) t-SNE réduction 2D
    from sklearn.manifold import TSNE
    from sklearn.preprocessing import normalize

    # Normaliser les vecteurs avant t-SNE pour améliorer la séparation
    vectors_normed = normalize(vectors, norm='l2')

    actual_perplexity = min(perplexity, max(5, len(vectors_normed) // 3))
    tsne = TSNE(
        n_components=2,
        perplexity=actual_perplexity,
        random_state=42,
        max_iter=1500,
        learning_rate='auto',
        init='pca',
        early_exaggeration=12.0,
        metric='cosine',
    )
    coords_2d = tsne.fit_transform(vectors_normed)

    # 5) Construire les points individuels (pour Plotly côté JS)
    points = []
    for i, att in enumerate(data):
        points.append({
            'x': float(coords_2d[i, 0]),
            'y': float(coords_2d[i, 1]),
            'cluster': int(labels[i]),
            'user_id': str(att.get('user_id', att.get('student_identifier', '?'))),
            'attempt_id': att.get('attempt_id', i),
            'correct': int(att.get('correct', 0)),
            'date': str(att.get('submission_date', att.get('date', ''))),
        })

    # 6) Génération du scatter plot matplotlib → base64 (rétro-compatibilité)
    import matplotlib
    matplotlib.use('Agg')
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

    buf = io.BytesIO()
    fig.savefig(buf, format='png', dpi=120, bbox_inches='tight')
    plt.close(fig)
    buf.seek(0)
    img_base64 = 'data:image/png;base64,' + base64.b64encode(buf.read()).decode('utf-8')
    buf.close()

    students = [str(att.get('user_id', att.get('student_identifier', '?'))) for att in data]
    correct_list = [int(att.get('correct', 0)) for att in data]

    return {
        'success': True,
        'mode': 'micro',
        'image_base64': img_base64,
        'n_points': len(data),
        'n_clusters': actual_n_clusters,
        'exercise_name': exercise_name,
        'clusters': labels.tolist(),
        'students': students,
        'correct': correct_list,
        'points': points,
    }


# ── Pipeline GLOBAL (tous les exercices → centroïdes par TD) ─────────────────
def run_pipeline_global(data, perplexity=30):
    """Pipeline global : Doc2Vec + t-SNE sur TOUTES les tentatives.
    Renvoie les centroïdes par exercise_name pour la vue macro."""

    if len(data) < 5:
        return {
            'success': False,
            'error': f"Pas assez de tentatives avec AES ({len(data)} trouvées, minimum 5)."
        }

    # 1) Préparer les données
    for att in data:
        if not att.get('eval_set'):
            att['eval_set'] = 'training'

    train_data = [dict(att, eval_set='training') for att in data]
    infer_data = [dict(att, eval_set='test') for att in data]

    # 2) Doc2Vec
    old_cwd = os.getcwd()
    os.chdir(SCRIPT_DIR)

    try:
        from aes2vec import learnModel, inferVectors
        import numpy as np

        model = learnModel(
            train_data,
            selectionfield='eval_set',
            selectionsets=['training'],
            valuefield='aes2',
            vsize=100,
            cwindow=5,
            niter=100
        )

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

    # 3) t-SNE global
    from sklearn.manifold import TSNE
    from sklearn.preprocessing import normalize
    import numpy as np

    # Normaliser les vecteurs avant t-SNE pour améliorer la séparation
    vectors_normed = normalize(vectors, norm='l2')

    actual_perplexity = min(perplexity, max(5, len(vectors_normed) // 3))
    tsne = TSNE(
        n_components=2,
        perplexity=actual_perplexity,
        random_state=42,
        max_iter=1500,
        learning_rate='auto',
        init='pca',
        early_exaggeration=12.0,
        metric='cosine',
    )
    coords_2d = tsne.fit_transform(vectors_normed)

    # 4) Regrouper par exercise_name et calculer les centroïdes
    exercise_points = {}  # exercise_name -> list of (x, y, exercice_id)
    all_points = []

    for i, att in enumerate(data):
        ex_name = att.get('exercise_name', 'Inconnu')
        ex_id = att.get('exercice_id', att.get('exercise_id', 0))
        x = float(coords_2d[i, 0])
        y = float(coords_2d[i, 1])

        if ex_name not in exercise_points:
            exercise_points[ex_name] = {'xs': [], 'ys': [], 'exercice_id': ex_id}
        exercise_points[ex_name]['xs'].append(x)
        exercise_points[ex_name]['ys'].append(y)

        all_points.append({
            'x': x,
            'y': y,
            'exercise_name': ex_name,
            'exercice_id': int(ex_id),
        })

    # Centroïdes par TD
    centroids = []
    for ex_name, pts in exercise_points.items():
        cx = float(np.mean(pts['xs']))
        cy = float(np.mean(pts['ys']))
        centroids.append({
            'exercise_name': ex_name,
            'exercice_id': int(pts['exercice_id']),
            'x': cx,
            'y': cy,
            'n_attempts': len(pts['xs']),
        })

    return {
        'success': True,
        'mode': 'global',
        'n_points': len(data),
        'n_exercises': len(centroids),
        'centroids': centroids,
        'all_points': all_points,
    }


# ── Lecture depuis stdin (mode appelé par PHP) ───────────────────────────────
def run_from_stdin():
    """Lit le JSON depuis stdin et lance le pipeline approprié."""
    raw = sys.stdin.read()
    payload = json.loads(raw)

    mode        = payload.get('mode', 'micro')
    data        = payload['attempts']
    n_clusters  = int(payload.get('n_clusters', 8))
    perplexity  = int(payload.get('perplexity', 30))
    exercise_id = payload.get('exercise_id')

    if mode == 'global':
        return run_pipeline_global(data, perplexity)
    else:
        return run_pipeline_micro(data, n_clusters, perplexity, exercise_id)


# ── Lecture depuis MySQL (mode CLI autonome) ─────────────────────────────────
def run_from_db(exercise_id, n_clusters=8, perplexity=30):
    """Se connecte à MySQL, charge les données et lance le pipeline."""
    env = _load_env()
    conn = _get_connection(env)
    data = _load_attempts(conn, exercise_id)
    conn.close()
    return run_pipeline_micro(data, n_clusters, perplexity, exercise_id)


def _load_env():
    possible_paths = [
        os.path.join(PROJECT_ROOT, 'config', '.env'),
        os.path.join(PROJECT_ROOT, '..', 'config', '.env'),
        os.path.join(PROJECT_ROOT, '.env'),
    ]
    config = {}
    for env_path in possible_paths:
        if os.path.exists(env_path):
            with open(env_path, 'r', encoding='utf-8') as f:
                for line in f:
                    line = line.strip()
                    if not line or line.startswith('#') or line.startswith(';'):
                        continue
                    if '=' in line:
                        key, val = line.split('=', 1)
                        config[key.strip()] = val.strip().strip('"').strip("'")
            return config
    return {'DB_HOST': '127.0.0.1', 'DB_NAME': 'studtraj', 'DB_USER': 'root', 'DB_PASS': ''}


def _get_connection(env):
    import mysql.connector
    host = env.get('DB_HOST', '127.0.0.1')
    if host == 'localhost':
        host = '127.0.0.1'
    return mysql.connector.connect(
        host=host,
        port=int(env.get('DB_PORT', 3306)),
        user=env.get('DB_USER', 'root'),
        password=env.get('DB_PASS', ''),
        database=env.get('DB_NAME', 'studtraj'),
        charset='utf8mb4',
    )


def _load_attempts(conn, exercise_id):
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT a.attempt_id, a.aes2, a.eval_set, a.correct,
               a.student_id, a.exercice_id,
               e.exercice_name AS exercise_name,
               s.student_identifier AS user_id
        FROM attempts a
        JOIN exercices e ON a.exercice_id = e.exercice_id
        JOIN students s  ON a.student_id  = s.student_id
        WHERE a.exercice_id = %s AND a.aes2 IS NOT NULL AND a.aes2 != ''
        ORDER BY a.attempt_id
    """, (exercise_id,))
    rows = cursor.fetchall()
    cursor.close()
    return rows


# ── Point d'entrée CLI ──────────────────────────────────────────────────────
if __name__ == '__main__':
    parser = argparse.ArgumentParser(description='Pipeline clustering aes2vec')
    parser.add_argument('--from-stdin', action='store_true',
                        help='Lire les données JSON depuis stdin (mode PHP)')
    parser.add_argument('--exercise_id', type=int, default=0,
                        help='ID de l\'exercice (mode CLI direct)')
    parser.add_argument('--n_clusters', type=int, default=8)
    parser.add_argument('--perplexity', type=int, default=30)
    args = parser.parse_args()

    try:
        if args.from_stdin:
            result = run_from_stdin()
        elif args.exercise_id > 0:
            result = run_from_db(args.exercise_id, args.n_clusters, args.perplexity)
        else:
            result = {'success': False, 'error': 'Spécifiez --from-stdin ou --exercise_id <ID>'}
    except Exception as e:
        result = {'success': False, 'error': str(e)}

    print(json.dumps(result, ensure_ascii=False))
