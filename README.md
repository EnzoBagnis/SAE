# StudTraj

## Installation

```bash
composer install
```

## Stratégie de Branches et CI/CD

Le projet utilise une stratégie de branches avec intégration continue :

### 📋 Workflow des branches

```
dev → test → prod
```

#### 🔧 Branche `dev` (Développement)
- **Action automatique** : Linting et qualité du code
- **Outils** : PHP_CodeSniffer, PHP-CS-Fixer
- **Déclenchement** : Push sur `dev`
- **Objectif** : Vérifier la qualité du code avant merge

#### 🧪 Branche `test` (Tests)
- **Action automatique** : Tests unitaires
- **Outils** : PHPUnit sur PHP 8.1, 8.2, 8.3
- **Déclenchement** : Push sur `test`
- **Objectif** : Valider le fonctionnement du code
- **Bonus** : Crée automatiquement une PR vers `prod` si tous les tests passent ✅

#### 🚀 Branche `prod` (Production)
- **Action automatique** : Génération de documentation
- **Outils** : phpDocumentor + déploiement GitHub Pages
- **Déclenchement** : Push sur `prod` ou `main`
- **Objectif** : Documenter le code en production

### 🔄 Processus de déploiement

1. **Développer** sur `dev` → Le linter vérifie automatiquement votre code
2. **Merger** vers `test` → Les tests unitaires s'exécutent automatiquement
3. **Si tests OK** → Une PR automatique est créée vers `prod`
4. **Merger la PR** vers `prod` → La documentation est générée et déployée

## Tests

### Exécuter les tests avec PHPUnit

```bash
# Tous les tests
vendor/bin/phpunit

# Tests unitaires
vendor/bin/phpunit --testsuite Unit

# Tests avec couverture de code
vendor/bin/phpunit --coverage-html build/coverage
```

## Documentation

### Générer la documentation avec phpDocumentor

```bash
vendor/bin/phpdoc --config=phpdoc.xml
```

La documentation sera générée dans le dossier `docs/api/`.

## Qualité du code

### PHP_CodeSniffer

```bash
# Vérifier le code
vendor/bin/phpcs

# Corriger automatiquement
vendor/bin/phpcbf
```

### PHP-CS-Fixer

```bash
# Vérifier les problèmes
vendor/bin/php-cs-fixer fix --dry-run --diff

# Corriger automatiquement
vendor/bin/php-cs-fixer fix
```

## Workflow CI/CD

Le projet utilise GitHub Actions pour l'intégration continue :

- **Branche dev** : Linting automatique (PHP_CodeSniffer, PHP-CS-Fixer)
- **Branche test** : Tests unitaires sur PHP 8.1, 8.2 et 8.3 + création automatique de PR vers prod
- **Branche prod/main** : Génération et déploiement de la documentation

Les workflows sont configurés dans `.github/workflows/php-ci.yml`.

## 🎯 Génération automatique de tests

Pour générer automatiquement des tests unitaires basés sur vos classes :

```bash
# Générer des tests pour un modèle
php generate-tests.php User
php generate-tests.php Student
php generate-tests.php Resource
```
