#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
install_dependencies.py
Installe toutes les dépendances Python nécessaires au pipeline de clustering.

Usage:
    python install_dependencies.py
    python install_dependencies.py --upgrade
"""

import subprocess
import sys
import importlib
import argparse

REQUIRED_PACKAGES = [
    # (nom_import,       nom_pip,                    version_min)
    ("mysql.connector", "mysql-connector-python",   "8.0.0"),
    ("numpy",           "numpy",                    "1.21.0"),
    ("sklearn",         "scikit-learn",             "1.0.0"),
    ("gensim",          "gensim",                   "4.0.0"),
    ("smart_open",      "smart_open",               "5.0.0"),
    ("matplotlib",      "matplotlib",               "3.5.0"),
    ("scipy",           "scipy",                    None),
]


def check_package(import_name: str) -> bool:
    """Vérifie si un package est déjà importable."""
    try:
        importlib.import_module(import_name)
        return True
    except ImportError:
        return False


def install_package(pip_name: str, version_min: str = None, upgrade: bool = False) -> bool:
    """Installe (ou met à jour) un package via pip."""
    spec = f"{pip_name}>={version_min}" if version_min else pip_name
    cmd = [sys.executable, "-m", "pip", "install", spec]
    if upgrade:
        cmd.append("--upgrade")

    print(f"  → Exécution : {' '.join(cmd)}")
    result = subprocess.run(cmd, capture_output=True, text=True)

    if result.returncode == 0:
        print(f"  ✓ {pip_name} installé avec succès.")
        return True
    else:
        print(f"  ✗ Échec pour {pip_name} :")
        print(f"    {result.stderr.strip()}")
        return False


def main():
    parser = argparse.ArgumentParser(description="Installe les dépendances Python du projet SAE.")
    parser.add_argument("--upgrade", action="store_true", help="Force la mise à jour de tous les packages.")
    args = parser.parse_args()

    print("=" * 55)
    print(f"  Python     : {sys.executable}")
    print(f"  Version    : {sys.version.split()[0]}")
    print("=" * 55)
    print()

    failed = []

    for import_name, pip_name, version_min in REQUIRED_PACKAGES:
        already = check_package(import_name)
        if already and not args.upgrade:
            print(f"  ✓ {pip_name:<30} déjà installé  (--upgrade pour forcer)")
        else:
            status = "mise à jour" if already else "manquant"
            print(f"  ✗ {pip_name:<30} {status}")
            success = install_package(pip_name, version_min, upgrade=args.upgrade)
            if not success:
                failed.append(pip_name)
        print()

    print("=" * 55)
    if failed:
        print(f"ÉCHEC : {len(failed)} package(s) non installé(s) :")
        for pkg in failed:
            print(f"  - {pkg}")
        print()
        print("Essayez manuellement :")
        print(f"  {sys.executable} -m pip install " + " ".join(failed))
        sys.exit(1)
    else:
        print("✓ Toutes les dépendances sont prêtes !")
        print()
        print("Vous pouvez maintenant lancer :")
        print("  python clustering_pipeline.py --exercise_id <ID> --db_host <host>")
        print("        --db_name <nom_bdd> --db_user <user> --db_pass <mdp>")
        sys.exit(0)


if __name__ == "__main__":
    main()

