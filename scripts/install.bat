@echo off
:: Script d'installation des dépendances Python pour le pipeline de clustering
:: À exécuter une seule fois depuis n'importe quel répertoire

set SCRIPT_DIR=%~dp0

echo [1/2] Creation du virtualenv...
python -m venv "%SCRIPT_DIR%venv"

echo [2/2] Installation des dependances...
"%SCRIPT_DIR%venv\Scripts\pip.exe" install -r "%SCRIPT_DIR%requirements.txt"

echo.
echo Installation terminee. Le virtualenv est dans scripts/venv/
pause

