#! /usr/bin/python3
# -*- coding: UTF-8 -*-

"""
Pipeline de clustering : Doc2Vec + KMeans + t-SNE
Charge les tentatives depuis la BDD MySQL, filtre par exercice,
vectorise avec Doc2Vec, clusterise avec KMeans, réduit avec t-SNE,
et renvoie un graphique PNG encodé en base64 (JSON sur stdout).

Usage:
    python clustering_pipeline.py --exercise_id <ID> [--n_clusters 8] [--db_host localhost]
        [--db_name <name>] [--db_user <user>] [--db_pass <pass>]
"""

import sys
import os
import json
import argparse
import base64
import io
import warnings

warnings.filterwarnings("ignore")

# Ajouter le dossier scripts au path pour importer aes2vec
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))


def load_attempts_from_db(db_host, db_name, db_user, db_pass, exercise_id=None, resource_id=None):
    """
    Charge les tentatives depuis la base de données MySQL.
    Filtre par exercise_id ou resource_id si fourni.
    Retourne une liste de dictionnaires compatibles avec aes2vec.
    """
    import mysql.connector

    conn = mysql.connector.connect(
        host=db_host,
        database=db_name,
        user=db_user,
        password=db_pass,
        charset='utf8mb4'
    )
    cursor = conn.cursor(dictionary=True)

    if exercise_id is not None:
        query = """
            SELECT a.attempt_id, a.exercice_id, a.user, a.correct,
                   a.eval_set, a.upload, a.aes0, a.aes1, a.aes2,
                   e.exercice_name
            FROM attempts a
            INNER JOIN exercices e ON a.exercice_id = e.exercice_id
            WHERE a.exercice_id = %s
            ORDER BY a.attempt_id
        """
        cursor.execute(query, (exercise_id,))
    elif resource_id is not None:
        query = """
            SELECT a.attempt_id, a.exercice_id, a.user, a.correct,
                   a.eval_set, a.upload, a.aes0, a.aes1, a.aes2,
                   e.exercice_name
            FROM attempts a
            INNER JOIN exercices e ON a.exercice_id = e.exercice_id
            WHERE e.ressource_id = %s
            ORDER BY a.attempt_id
        """
        cursor.execute(query, (resource_id,))
    else:
        query = """
            SELECT a.attempt_id, a.exercice_id, a.user, a.correct,
                   a.eval_set, a.upload, a.aes0, a.aes1, a.aes2,
                   e.exercice_name
            FROM attempts a
            INNER JOIN exercices e ON a.exercice_id = e.exercice_id
            ORDER BY a.attempt_id
        """
        cursor.execute(query)

    rows = cursor.fetchall()
    cursor.close()
    conn.close()

    # Convertir en format compatible aes2vec
    data = []
    for row in rows:
        data.append({
            'attempt_id': row['attempt_id'],
            'exercice_id': row['exercice_id'],
            'user': row['user'],
            'correct': row['correct'],
            'eval_set': row['eval_set'] if row['eval_set'] else 'training',
            'upload': row['upload'],
            'aes0': row['aes0'] if row['aes0'] else '',
            'aes1': row['aes1'] if row['aes1'] else '',
            'aes2': row['aes2'] if row['aes2'] else '',
            'exercise_name': row['exercice_name'] if row['exercice_name'] else '',
        })

    return data


def run_pipeline(data, n_clusters=8, perplexity=30):
    """
    Pipeline principal :
    1. Vectorisation Doc2Vec (via aes2vec)
    2. Clustering KMeans
    3. Réduction t-SNE
    4. Génération du graphique
    Retourne un dict avec image base64 et métadonnées.
    """
    import numpy as np
    from sklearn.cluster import KMeans
    from sklearn.manifold import TSNE
    import matplotlib
    matplotlib.use('Agg')  # Backend non-interactif
    import matplotlib.pyplot as plt
    import matplotlib.cm as cm

    if len(data) < 5:
        return {
            'success': False,
            'error': f'Pas assez de tentatives pour le clustering ({len(data)} trouvées, minimum 5).'
        }

    # Filtrer les tentatives ayant un champ aes2 non vide
    filtered_data = [d for d in data if d.get('aes2', '').strip() != '']

    if len(filtered_data) < 5:
        return {
            'success': False,
            'error': f'Pas assez de tentatives avec des données AES ({len(filtered_data)} valides, minimum 5).'
        }

    # ── Étape 1 : Vectorisation Doc2Vec ──────────────────────────────────────
    # On force eval_set='training' pour toutes les données afin d'entraîner le modèle
    # puis on infère les vecteurs sur les mêmes données
    for d in filtered_data:
        d['eval_set'] = 'training'

    from aes2vec import learnModel, inferVectors

    # S'assurer que le dossier utils existe pour les fichiers .cor
    utils_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'utils')
    os.makedirs(utils_dir, exist_ok=True)

    # Changer le répertoire de travail vers scripts/ pour les fichiers .cor
    original_cwd = os.getcwd()
    os.chdir(os.path.dirname(os.path.abspath(__file__)))

    try:
        model = learnModel(
            filtered_data,
            selectionfield='eval_set',
            selectionsets=['training'],
            valuefield='aes2',
            vsize=100,
            cwindow=5,
            niter=100  # Réduit pour la vitesse en production
        )

        # Inférer les vecteurs (on utilise les mêmes données)
        vectors = inferVectors(
            model,
            filtered_data,
            selectionfield='eval_set',
            selectionsets=['training'],
            valuefield='aes2'
        )
    finally:
        os.chdir(original_cwd)

    if len(vectors) == 0:
        return {
            'success': False,
            'error': 'La vectorisation n\'a produit aucun vecteur.'
        }

    vectors_array = np.array(vectors)

    # ── Étape 2 : Clustering KMeans ──────────────────────────────────────────
    actual_clusters = min(n_clusters, len(vectors_array))
    kmeans = KMeans(n_clusters=actual_clusters, random_state=42, n_init=10)
    labels = kmeans.fit_predict(vectors_array)

    # ── Étape 3 : Réduction t-SNE ───────────────────────────────────────────
    actual_perplexity = min(perplexity, max(1, len(vectors_array) - 1))
    tsne = TSNE(
        n_components=2,
        perplexity=actual_perplexity,
        random_state=42,
        max_iter=1000
    )
    coords_2d = tsne.fit_transform(vectors_array)

    # ── Étape 4 : Génération du graphique ────────────────────────────────────
    fig, ax = plt.subplots(figsize=(12, 8))

    colors = cm.get_cmap('tab10', actual_clusters)

    for cluster_id in range(actual_clusters):
        mask = labels == cluster_id
        count = int(np.sum(mask))
        ax.scatter(
            coords_2d[mask, 0],
            coords_2d[mask, 1],
            c=[colors(cluster_id)],
            label=f'Cluster {cluster_id} ({count})',
            alpha=0.7,
            s=50,
            edgecolors='white',
            linewidth=0.5
        )

    ax.set_title('Cartographie des stratégies — t-SNE + KMeans', fontsize=14, fontweight='bold')
    ax.set_xlabel('t-SNE dimension 1', fontsize=11)
    ax.set_ylabel('t-SNE dimension 2', fontsize=11)
    ax.legend(
        title='Clusters',
        bbox_to_anchor=(1.05, 1),
        loc='upper left',
        fontsize=9
    )
    ax.grid(True, alpha=0.3)
    fig.tight_layout()

    # Encoder en base64
    buf = io.BytesIO()
    fig.savefig(buf, format='png', dpi=150, bbox_inches='tight')
    plt.close(fig)
    buf.seek(0)
    img_base64 = base64.b64encode(buf.read()).decode('utf-8')

    # ── Métadonnées ──────────────────────────────────────────────────────────
    cluster_stats = []
    for cluster_id in range(actual_clusters):
        mask = labels == cluster_id
        indices = np.where(mask)[0]
        cluster_users = list(set(filtered_data[i]['user'] for i in indices))
        correct_count = sum(1 for i in indices if filtered_data[i].get('correct', 0))
        total_in_cluster = int(np.sum(mask))
        cluster_stats.append({
            'cluster_id': cluster_id,
            'count': total_in_cluster,
            'users': cluster_users[:10],  # Limiter pour la réponse JSON
            'success_rate': round((correct_count / total_in_cluster) * 100, 1) if total_in_cluster > 0 else 0,
        })

    # Données des points pour un éventuel graphique interactif côté client
    points = []
    for i in range(len(filtered_data)):
        points.append({
            'x': float(coords_2d[i, 0]),
            'y': float(coords_2d[i, 1]),
            'cluster': int(labels[i]),
            'user': filtered_data[i]['user'],
            'correct': bool(filtered_data[i].get('correct', 0)),
            'attempt_id': filtered_data[i].get('attempt_id', ''),
        })

    return {
        'success': True,
        'image': img_base64,
        'total_attempts': len(filtered_data),
        'n_clusters': actual_clusters,
        'cluster_stats': cluster_stats,
        'points': points,
    }


def main():
    parser = argparse.ArgumentParser(description='Pipeline Clustering t-SNE + KMeans')
    parser.add_argument('--exercise_id', type=int, default=None, help='ID de l\'exercice à filtrer')
    parser.add_argument('--resource_id', type=int, default=None, help='ID de la ressource à filtrer')
    parser.add_argument('--n_clusters', type=int, default=8, help='Nombre de clusters KMeans')
    parser.add_argument('--db_host', type=str, default='localhost', help='Hôte MySQL')
    parser.add_argument('--db_name', type=str, required=True, help='Nom de la base de données')
    parser.add_argument('--db_user', type=str, required=True, help='Utilisateur MySQL')
    parser.add_argument('--db_pass', type=str, default='', help='Mot de passe MySQL')

    args = parser.parse_args()

    try:
        # Charger les données depuis la BDD
        data = load_attempts_from_db(
            db_host=args.db_host,
            db_name=args.db_name,
            db_user=args.db_user,
            db_pass=args.db_pass,
            exercise_id=args.exercise_id,
            resource_id=args.resource_id
        )

        if not data:
            result = {
                'success': False,
                'error': 'Aucune tentative trouvée pour les critères donnés.'
            }
        else:
            result = run_pipeline(data, n_clusters=args.n_clusters)

    except Exception as e:
        result = {
            'success': False,
            'error': str(e)
        }

    # Sortie JSON sur stdout
    print(json.dumps(result, ensure_ascii=False))


if __name__ == '__main__':
    main()

