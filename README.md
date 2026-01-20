# 📊 StudTraj - Student Trajectory Analysis Platform

**StudTraj** est une plateforme web d'analyse des trajectoires d'apprentissage des étudiants en programmation. Elle permet aux enseignants de suivre et d'analyser les tentatives de résolution d'exercices de leurs étudiants grâce à des visualisations avancées et des analyses vectorielles.

---

## 🎯 Fonctionnalités principales

### Pour les enseignants
- **Gestion de ressources pédagogiques** : Créez et organisez vos ressources d'enseignement
- **Suivi des étudiants** : Visualisez les tentatives et les progrès de chaque étudiant
- **Analyse vectorielle** : Utilisez Code2Vec pour analyser la similarité des codes
- **Visualisations interactives** : Graphiques et tableaux de bord pour comprendre les trajectoires d'apprentissage
- **Import de données** : API pour importer des exercices et des tentatives depuis des plateformes externes
- **Partage de ressources** : Collaborez avec d'autres enseignants

### Pour les administrateurs
- **Gestion des utilisateurs** : Validation, blocage et gestion des comptes
- **Système de vérification par email** : Sécurisation des inscriptions
- **Tableau de bord administrateur** : Vue d'ensemble de la plateforme

---

## 🛠️ Technologies utilisées

### Backend
- **PHP 7.4+** : Langage principal
- **PDO** : Accès base de données sécurisé
- **Architecture MVC** : Organisation claire du code avec namespaces

### Frontend
- **HTML5 / CSS3** : Interface utilisateur moderne
- **JavaScript** : Interactivité et visualisations
- **Chart.js / D3.js** : Graphiques et visualisations de données

### Base de données
- **MySQL / MariaDB** : Stockage des données

### Outils de développement
- **Composer** : Gestion des dépendances PHP
- **PHP_CodeSniffer** : Analyse de code
- **PHPUnit** : Tests unitaires
- **phpDocumentor** : Génération de documentation

---

## 📁 Structure du projet

```
StudTraj/
├── config/              # Configuration et routage
│   └── router.php       # Routeur principal de l'application
├── controllers/         # Contrôleurs MVC
│   ├── Admin/          # Gestion administrative
│   ├── Analysis/       # Analyse et visualisation
│   ├── Auth/           # Authentification et autorisation
│   ├── Import/         # Import de données
│   └── User/           # Fonctionnalités utilisateur
├── models/             # Modèles de données
│   ├── Database.php    # Connexion base de données
│   ├── Student.php     # Gestion des étudiants
│   ├── Exercise.php    # Gestion des exercices
│   ├── Resource.php    # Gestion des ressources
│   ├── User.php        # Gestion des utilisateurs
│   └── Code2VecService.php  # Service d'analyse vectorielle
├── views/              # Vues (templates)
│   ├── admin/         # Vues administrateur
│   └── user/          # Vues utilisateur
├── public/            # Ressources publiques
│   ├── css/          # Feuilles de style
│   └── js/           # Scripts JavaScript
├── docs/             # Documentation générée
├── images/           # Images uploadées
├── cron/             # Tâches planifiées
└── index.php         # Point d'entrée de l'application
```

---

## 🚀 Installation

### Prérequis
- **PHP 7.4** ou supérieur
- **MySQL 5.7** ou **MariaDB 10.3** ou supérieur
- **Composer** (gestionnaire de dépendances PHP)
- **Serveur web** (Apache recommandé avec mod_rewrite)

### Étapes d'installation

1. **Cloner le dépôt**
   ```bash
   git clone https://github.com/EnzoBagnis/SAE.git StudTraj
   cd StudTraj
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer la base de données**
   - Créez une base de données MySQL
   - Configurez les paramètres de connexion via un fichier `.env` dans le dossier `config/`
   - Le schéma de base de données est créé automatiquement via les migrations ou scripts d'installation
   
   **Exemple de configuration `.env` :**
   ```ini
   DB_HOST=localhost
   DB_USER=your_username
   DB_PASS=your_secure_password
   DB_NAME=studtraj
   ```
   
   ⚠️ **Sécurité Production** : 
   - N'utilisez JAMAIS de mots de passe vides ou par défaut en production
   - Utilisez des mots de passe forts et uniques (minimum 12 caractères, avec majuscules, minuscules, chiffres et caractères spéciaux)
   - Changez les identifiants par défaut (root, admin, etc.)

4. **Configurer le serveur web**
   - Assurez-vous que le fichier `.htaccess` est activé
   - Configurez le document root vers le dossier du projet
   - Activez `mod_rewrite` pour Apache

5. **Configurer les permissions**
   ```bash
   chmod 755 images/
   chmod 755 cron/
   ```

6. **Accéder à l'application**
   - Ouvrez votre navigateur à l'adresse de votre serveur local
   - Créez un compte administrateur via l'interface

7. **Configurer les services externes**
   
   **Service d'email (PHPMailer) :**
   Ajoutez les paramètres SMTP dans votre fichier `.env` :
   ```ini
   MAIL_HOST=smtp.example.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@example.com
   MAIL_PASSWORD=your-app-specific-password
   MAIL_FROM_NAME=StudTraj
   ```
   
   💡 **Conseil** : Utilisez des mots de passe d'application spécifiques ou OAuth2 plutôt que votre mot de passe de compte principal pour plus de sécurité.
   
   **Service Code2Vec :**
   - Installez Python 3 et les dépendances requises
   - Placez le modèle pré-entraîné dans `data/models/pretrained_code2vec.model`
   - Configurez le chemin Python dans `Code2VecService.php` (ligne 16) si nécessaire
   - Scripts Python requis dans le dossier `python_scripts/` :
     - `generate_aes.py` : Génération des séquences AST
     - `infer_vectors.py` : Inférence des vecteurs de code
     - `process_complete.py` : Traitement complet en arrière-plan

---

## 📖 Utilisation

### Inscription et connexion
1. Créez un compte enseignant via la page d'inscription
2. Vérifiez votre email avec le code reçu
3. Attendez la validation par un administrateur
4. Connectez-vous avec vos identifiants

### Créer une ressource
1. Accédez au tableau de bord
2. Cliquez sur "Nouvelle ressource"
3. Remplissez les informations (nom, description, image)
4. Partagez avec d'autres enseignants si nécessaire

### Importer des données
Utilisez les API d'import pour charger vos données :

**Import d'exercices :**
```bash
POST /index.php?action=import-exercises
Content-Type: application/json

{
  "resource_id": 1,
  "exercises": [...]
}
```

**Import de tentatives :**
```bash
POST /index.php?action=import-attempts
Content-Type: application/json

{
  "resource_id": 1,
  "attempts": [...]
}
```

### Analyser les trajectoires
1. Sélectionnez une ressource
2. Visualisez les statistiques des étudiants
3. Générez des vecteurs Code2Vec pour l'analyse de similarité
4. Explorez les visualisations interactives

---

## 📚 Documentation

### Documentation technique
La documentation complète du code est générée automatiquement avec phpDocumentor :
- Consultez [`docs/README_DOC.md`](docs/README_DOC.md)
- Ouvrez `docs/index.html` dans votre navigateur pour la documentation interactive

### Statistiques du projet
- **Fichiers PHP :** 38
- **Lignes de code :** ~4559
- **Version PHP :** 7.4+

---

## 🧪 Tests et qualité du code

### Lancer les tests
```bash
vendor/bin/phpunit
```

### Vérifier le style de code
```bash
vendor/bin/phpcs
```

### Corriger automatiquement le style
```bash
vendor/bin/php-cs-fixer fix
```

---

## 🔒 Sécurité

- **Protection XSS** : Headers de sécurité configurés
- **Protection CSRF** : ❌ **CRITIQUE** - Non implémentée actuellement. **DOIT être implémentée avant tout déploiement en production** (recommandation : jetons synchronisés sur tous les formulaires)
- **Validation des entrées** : PDO avec requêtes préparées
- **Gestion des sessions** : Sessions PHP sécurisées
- **Vérification par email** : Double authentification pour les inscriptions
- **Configuration sécurisée** : Variables d'environnement via fichier `.env` (ne pas versionner)

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forkez le projet
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Poussez vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

---

## 📝 Licence

Ce projet est développé dans le cadre d'une SAE (Situation d'Apprentissage et d'Évaluation).

---

## 👥 Auteurs

- **Équipe StudTraj**
- **Ilan Stefanovitch**
- **Enzo Bagnis**
- **Hamza Koliaï**
- **Jean-Baptiste Pibouleau**

---

## 📞 Support

Pour toute question ou problème :
- Ouvrez une issue sur GitHub
- Consultez la documentation technique dans `docs/`

---

## 🗺️ Roadmap

- [ ] Amélioration des visualisations

---

**Dernière mise à jour :** 2026-01-20
