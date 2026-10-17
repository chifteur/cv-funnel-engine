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

2. **Choisir un mode d’installation**

   **Provisioning web (recommandé)** : ne crée pas `core/config.php`. Configure le serveur web pour servir `public/`, puis visite le site. Le formulaire de provisioning crée la configuration et la base, puis affiche une fois le lien d’activation à transmettre au propriétaire.

   **Installation manuelle** : crée `core/config.php` depuis le modèle, puis remplace les constantes de connexion et d’URL par celles de ton instance :
   ```bash
   cp core/config.exemple.php core/config.php
   ```
   ```php
   define('DB_HOST', 'votre-hote-mysql');
   define('DB_NAME', 'nom-de-la-bdd');
   define('DB_USER', 'utilisateur-mysql');
   define('DB_PASS', 'mot-de-passe');
   define('SITE_URL', 'https://votre-domaine.ch');
   ```

   Importe `core/sql/structure.sql` et `core/sql/seed.sql`, crée `logs/` et `storage/docs/`, puis génère un lien d’activation à usage unique :
   ```bash
   mysql -h [DB_HOST] -u [DB_USER] -p [DB_NAME] < core/sql/structure.sql
   mysql -h [DB_HOST] -u [DB_USER] -p [DB_NAME] < core/sql/seed.sql
   mkdir -p logs storage/docs
   chmod 755 logs storage
   php core/create_admin_activation_link.php
   ```

3. **Configurer les emails SMTP** selon la section ci-dessous avant que le propriétaire ne termine l’activation. Si le SMTP n’est pas prêt, il pourra demander le renvoi de l’email après configuration.

4. **Transmettre le lien d’activation** au propriétaire par un canal sûr. Il expire après 48 heures ; l’opérateur peut en créer un nouveau en CLI si nécessaire.

## 🚀 Utilisation

### Accéder au tableau de bord admin

Rendez-vous sur :
```
https://votre-domaine.ch/manage
```

La clé d’administration est définie lors de l’activation, puis conservée uniquement sous forme de hash dans `settings`. L’adresse email de récupération doit être vérifiée avant la connexion et peut être modifiée dans l’onglet **Sécurité**. La page de connexion permet de demander un lien de remplacement de clé ; il est à usage unique, expire après 30 minutes et invalide les sessions admin existantes. La clé n’est jamais enregistrée en clair dans `config.php`.

Pour une installation existante, les migrations `1.0.2` et `1.0.3` s’exécutent via le moteur habituel (`php core/migrate.php`), notamment à la fin du workflow d’orchestration lorsque les tâches DB sont activées. La migration `1.0.2` transfère l’ancienne clé de `config.php` vers `settings` puis la retire. À la première connexion après mise à jour, l’administrateur doit configurer et vérifier une adresse de récupération.

Si l’installation a été configurée manuellement ou si le lien initial a expiré/perdu, l’opérateur peut en générer un nouveau depuis le terminal avec `php core/create_admin_activation_link.php`. Cette commande refuse de créer un lien pour une instance déjà activée ; une clé oubliée se réinitialise par email.

### Emails de récupération (PHPMailer / SMTP)

Installe PHPMailer dans le répertoire personnel du compte serveur, hors du document root (sur DreamHost Shared, suis le [guide PHPMailer DreamHost](https://help.dreamhost.com/hc/en-us/articles/360031174411-PHPMailer-Installing-on-a-shared-server)). La variable `CVFE_PHPMAILER_PATH` doit pointer vers son dossier `src`.

Le site lit en priorité ces variables depuis l’environnement du processus PHP :

| Variable | Exemple DreamHost | Obligatoire |
|---|---|---|
| `CVFE_PHPMAILER_PATH` | `/home/USER/PHPMailer/src` | Oui |
| `CVFE_SMTP_HOST` | `smtp.dreamhost.com` | Oui |
| `CVFE_SMTP_PORT` | `587` (TLS) ou `465` (SMTPS) | Non, défaut `587` |
| `CVFE_SMTP_USERNAME` | `admin@exemple.ch` | Oui |
| `CVFE_SMTP_PASSWORD` | Mot de passe de cette boîte email | Oui |
| `CVFE_SMTP_FROM_EMAIL` | `admin@exemple.ch` | Oui |
| `CVFE_SMTP_FROM_NAME` | `CV Funnel Engine` | Non |

Les identifiants SMTP sont communs aux instances hébergées sous ce compte ; ils ne vont ni dans Git, ni dans `settings`, ni dans les URLs. Si l’hébergement ne transmet pas ces variables à PHP, le site accepte à la place un fichier PHP serveur `~/.cvfe-smtp.php`, hors document root, lisible par le compte PHP et sans droit d’écriture pour le groupe ou les autres. Ce fichier doit retourner un tableau avec les clés `mailer_path`, `host`, `port`, `username`, `password`, `from_email` et, facultativement, `from_name`. Exemple :

```php
<?php
return [
    'mailer_path' => '/home/USER/PHPMailer/src',
    'host' => 'smtp.dreamhost.com',
    'port' => 587,
    'username' => 'admin@exemple.ch',
    'password' => 'SECRET_SMTP',
    'from_email' => 'admin@exemple.ch',
    'from_name' => 'CV Funnel Engine',
];
```

Le fallback peut pointer vers un autre fichier privé en définissant `CVFE_SMTP_CONFIG_FILE`. Sur DreamHost, vérifie les variables depuis une requête PHP du site : celles exportées uniquement dans un terminal SSH ne sont pas nécessairement transmises au processus web. Les emails sont des messages transactionnels ponctuels ; respecte les [quotas SMTP DreamHost](https://help.dreamhost.com/hc/en-us/articles/215730437-SMTP-quota-limits).

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

- **Clé d'accès admin** : conservée uniquement sous forme de hash dans `settings` ; la clé en clair n'est pas enregistrée dans `config.php`
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
