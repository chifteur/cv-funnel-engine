# CV Funnel Engine

**CV Funnel Engine** est une plateforme PHP moderne pour gérer vos candidatures professionnelles. Elle offre un système de suivi complet avec télémétrie, gestion des sessions de visiteurs, et un tableau de bord administrateur modulaire.

## 🎯 Fonctionnalités principales

- **Gestion des candidatures** : Suivi des demandes d'emploi avec différents statuts (envoyé, entretien, rejeté, accepté)
- **Système de télémétrie** : Suivi des interactions des visiteurs avec vos candidatures
- **Sessions de visiteurs** : Gestion automatique des UUIDs de visiteurs et des sessions
- **Tableau de bord admin** : Interface modulaire pour administrer le système
  - Module Dashboard
  - Module Logs
  - Module Query Explorer
  - Module Session Detail
  - Module CRM
- **Multi-lentilles (Lens)** : Affichage différencié selon les rôles (management, ops, tech)
- **Logging centralisé** : Système de logs complet pour DEBUG, INFO, ERROR
- **API de télémétrie** : Endpoint pour collecter les données de comportement

## 📋 Structure du projet

```
cv-funnel-engine/
├── core/                      # Cœur de l'application
│   ├── config.exemple.php     # Configuration (à adapter)
│   ├── router.php             # Logique de routage centralisé
│   ├── logger.php             # Système de logging
│   ├── tools.php              # Utilitaires (UUID, helpers)
│   ├── templates/             # Templates PHP
│   │   ├── admin_module_*.php  # Modules administrateur
│   │   └── cv_interactive.php  # Template interactif du CV
│   └── sql/                   # Scripts de base de données
│       ├── structure.sql       # Structure des tables
│       ├── seed.sql           # Données initiales
│       └── migration_*.sql    # Migrations
├── public/                    # Racine web publique
│   ├── index.php              # Front Controller
│   ├── debug.php              # Page de debug
│   ├── download.php           # Gestion des téléchargements
│   ├── api/
│   │   └── telemetry.php      # Endpoint télémétrie
│   ├── assets/
│   │   ├── css/
│   │   │   └── theme.css      # Feuille de styles
│   │   └── js/
│   │       └── telemetry.js   # Client télémétrie
│   └── mockups/               # Pages mockup pour tester
├── logs/                      # Dossier des logs (auto-créé)
├── storage/docs/              # Stockage des documents
└── .htaccess                  # Configuration Apache
```

## ⚙️ Installation

### Prérequis

- **PHP** 8.4+
- **MySQL** 8.0+
- **Apache** avec mod_rewrite active

### Étapes d'installation

1. **Cloner le projet**
   ```bash
   git clone <votre-repo>
   cd cv-funnel-engine
   ```

2. **Configurer la base de données**
   ```bash
   cp core/config.exemple.php core/config.php
   ```

3. **Éditer `core/config.php`** avec vos paramètres :
   ```php
   define('DB_HOST', 'votre-hote-mysql');
   define('DB_NAME', 'nom-de-la-bdd');
   define('DB_USER', 'utilisateur-mysql');
   define('DB_PASS', 'mot-de-passe');
   define('ADMIN_ACCESS_KEY', 'votre-clé-secrète');
   define('SITE_URL', 'https://votre-domaine.ch');
   ```

4. **Créer la base de données**
   ```bash
   mysql -h [DB_HOST] -u [DB_USER] -p [DB_NAME] < core/sql/structure.sql
   mysql -h [DB_HOST] -u [DB_USER] -p [DB_NAME] < core/sql/seed.sql
   ```

5. **Créer les dossiers nécessaires**
   ```bash
   mkdir -p logs storage/docs
   chmod 755 logs storage
   ```

6. **Configurer le serveur web** pour pointer vers le dossier `public/`

## 🚀 Utilisation

### Accéder au tableau de bord admin

Rendez-vous sur :
```
https://votre-domaine.ch/manage?key=VOTRE_CLÉ_SECRÈTE&module=dashboard
```

### Modules disponibles

- **dashboard** : Vue générale du système
- **logs** : Consultation des logs
- **query_explorer** : Explorateur SQL personnalisé
- **session_detail** : Détails d'une session de visiteur
- **crm** : Gestion des candidatures (CRM)

### API Télémétrie

L'endpoint `/api/telemetry.php` collecte les données de comportement des visiteurs :

```javascript
// Exemple d'utilisation (depuis telemetry.js)
fetch('/api/telemetry.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        event: 'page_view',
        page: 'cv_interactive',
        timestamp: new Date().toISOString()
    })
});
```

## 🏗️ Architecture

### Flux de requête

1. **Front Controller** (`public/index.php`)
   - Initialisation de la session
   - Chargement des dépendances
   - Gestion centralisée des erreurs

2. **Routeur** (`core/router.php`)
   - Parsing de l'URL
   - Dispatch vers les modules
   - Gestion des sessions de visiteurs

3. **Modules**
   - Logique métier
   - Rendu des templates

### Gestion des sessions

- Chaque visiteur reçoit un **UUID unique** stocké en cookie (`mg_v_uuid`)
- Une **session de télémétrie** est créée automatiquement et persiste 1 heure
- Liaison automatique entre visiteurs et interactions

### Système de logs

Trois niveaux de log :
- **DEBUG** : Informations de débogage (si `DEBUG = true` dans config)
- **INFO** : Événements normaux
- **ERROR** : Erreurs et exceptions

Les logs sont stockés dans `logs/app.log` avec timestamp et contexte JSON.

## 🗄️ Structure de données

### Tables principales

#### `applications`
```sql
- id : Identifiant unique
- slug : Identifiant lisible (ex: 'jenov-test')
- company_name : Nom de l'entreprise
- job_title : Intitulé du poste
- job_url : Lien vers l'offre
- custom_pitch : Message personnalisé
- default_lens : Lentille par défaut (management/ops/tech)
- status : Statut (sent/interview/rejected/accepted)
- created_at : Date de création
```

#### `telemetry_sessions`
```sql
- id : UUID de session (stocké en binaire)
- visitor_uuid : UUID du visiteur
- started_at : Date de démarrage
- ended_at : Date de fin (nullable)
```

#### `telemetry_events`
```sql
- id : Identifiant unique
- session_id : Référence à la session
- event_type : Type d'événement
- page : Page/module visité
- data : Données JSON supplémentaires
- created_at : Timestamp de l'événement
```

## 🛠️ Utilitaires

### UUID Management

```php
generate_uuid()           // Génère un UUID v4
uuid_to_bin($uuid)       // Convertit UUID en binaire MySQL
bin_to_uuid($bin)        // Convertit binaire MySQL en UUID
```

### Logger

```php
Logger::debug($msg, $context)    // Log de débogage
Logger::info($msg, $context)     // Log informatif
Logger::error($msg, $context)    // Log d'erreur
```

## 🔒 Sécurité

- **Clé d'accès admin** : Toutes les routes `/manage` requièrent une clé secrète
- **Gestion des erreurs** : Centralisée avec logging automatique
- **Préparation des requêtes** : Utilisation de PDO avec requêtes préparées
- **Session sécurisée** : UUID aléatoire par visiteur

⚠️ **À faire en production** :
- Déplacer `config.php` en dehors de la racine web
- Désactiver `display_errors` (mettre à 0)
- Utiliser des certificats SSL/TLS
- Configurer les permissions des fichiers restrictives

## 📝 Configuration avancée

### Paramètres de `config.php`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `DB_HOST` | string | Hôte MySQL |
| `DB_NAME` | string | Nom de la base de données |
| `DB_USER` | string | Utilisateur MySQL |
| `DB_PASS` | string | Mot de passe MySQL |
| `DEBUG` | bool | Activer les logs DEBUG |
| `ADMIN_ACCESS_KEY` | string | Clé d'accès admin |
| `SITE_URL` | string | URL du site |
| `PATH_STORAGE` | string | Chemin stockage documents |

## 🐛 Débogage

### Activer le mode debug

Modifier `core/config.php` :
```php
define('DEBUG', true);
```

### Consulter les logs

```bash
tail -f logs/app.log
```

### Utiliser la page de debug

Rendez-vous sur : `https://votre-domaine.ch/debug.php`

## 📦 Technologies utilisées

- **PHP** 8.4+
- **MySQL** 8.0+
- **Apache** (avec mod_rewrite)
- **JavaScript** (télémétrie côté client)
- **CSS** (customizable)

## 🤝 Contribution

1. Créer une branche `feature/ma-fonctionnalite`
2. Commiter les changements
3. Pousser vers la branche
4. Ouvrir une Pull Request

## 📜 Licence

GNU General Public License v3

## 📧 Contact & Support

Pour toute question ou support, veuillez contacter l'administrateur du projet.

---

**Dernière mise à jour** : 10 avril 2026
