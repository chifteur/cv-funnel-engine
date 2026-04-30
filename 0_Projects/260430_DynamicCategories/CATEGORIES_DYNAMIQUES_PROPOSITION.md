# 🎯 PROPOSITION: Rendre les catégories dynamiques

**Date**: 29 avril 2026  
**Statut**: ✅ IMPLÉMENTÉ

---

## 📋 Résumé exécutif

Les catégories **Opérations**, **Management** et **Technique** sont actuellement codées en dur dans 15+ emplacements du code PHP. Cette proposition crée une **table de dictionnaire centralisée** pour les gérer dynamiquement.

### Bénéfices

✅ **Maintenance simplifiée** - Modifier une catégorie se fait en DB, pas en code  
✅ **Extensibilité** - Ajouter de nouvelles catégories sans code  
✅ **Cohérence** - Un seul point de vérité pour les labels  
✅ **Performance** - Cache des catégories en session  
✅ **Production-safe** - Migration SQL fournie  

---

## 🗄️ Structure de la base de données

### Table créée: `category_dictionary`

```sql
CREATE TABLE category_dictionary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,          -- Clé technique: ops, management, tech
    label_short VARCHAR(100) NOT NULL,          -- Pour les selects: "Opérations"
    label_long VARCHAR(150) NOT NULL,           -- Pour l'affichage: "Excellence Opérationnelle"
    display_order INT NOT NULL DEFAULT 0,       -- Ordre d'affichage
    color_hex VARCHAR(7) DEFAULT '#3b82f6',     -- Couleur associée
    icon_name VARCHAR(50) DEFAULT 'briefcase',  -- Icône FontAwesome
    is_active BOOLEAN DEFAULT TRUE,             -- Archivage soft-delete
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (code),
    INDEX idx_display_order (display_order),
    INDEX idx_active (is_active)
);
```

### Données initiales

```sql
INSERT INTO category_dictionary (code, label_short, label_long, display_order, icon_name) VALUES
('ops', 'Opérations', 'Excellence Opérationnelle', 1, 'cog'),
('management', 'Management', 'Management & Gouvernance', 0, 'chess-king'),
('tech', 'Technique', 'Techniques & Technologie', 2, 'microchip');
```

---

## 🔧 Fichiers modifiés

### 1. **Fichier créé**: `core/category_helpers.php`

Contient 6 fonctions utilitaires:

| Fonction | Usage |
|----------|-------|
| `get_categories()` | Récupère toutes les catégories actives |
| `get_category_by_code($code)` | Récupère une catégorie par son code |
| `get_category_label($code, $type)` | Retourne le label d'une catégorie |
| `get_categories_for_select()` | Retourne array pour selects |
| `render_category_select($name, $current_value, $class, $attributes)` | Rend un select HTML complet |
| `init_categories_cache()` | Cache les catégories en session |

#### Exemple d'utilisation

```php
// Charger les catégories
init_categories_cache();
$categories = get_categories();

// Afficher le label complet
echo get_category_label('ops', 'long'); // "Excellence Opérationnelle"

// Générer un select automatique
echo render_category_select('category', 'ops', '', ['x-model' => 'editItem.category']);

// Récupérer toutes les catégories avec détails
$indexed = get_categories_indexed();
```

### 2. **Modifié**: `core/templates/admin_module_dashboard.php`

**Changements:**

✅ Ligne 5: Ajout de `require_once __DIR__ . '/../category_helpers.php';`  
✅ Ligne 375-380: Initialisation des catégories dynamiques  
✅ Ligne 415: Ajout de `categories`, `defaultCategoryExp`, `defaultCategorySkill` à appData()  
✅ Ligne 742: Remplacement de `category:'ops'` par `category: defaultCategoryExp`  
✅ Ligne 762: Remplacement de `category:'management'` par `category: defaultCategorySkill`  
✅ Lignes 1215-1291: Les 3 selects remplacés par `render_category_select()`  

### 3. **Modifié**: `core/templates/cv_interactive.php`

**Changements:**

✅ Ligne 6: Ajout de `require_once __DIR__ . '/../category_helpers.php';`  
✅ Ligne 190: Logique ternaire remplacée par `get_category_label($category, 'long')`  
✅ Lignes 322-333: Scores dynamiques basés sur les catégories de la BD  

### 4. **Créé**: `core/sql/migrations/2026_04_29_category_dictionary.sql`

Migration complète avec:
- Création de la table
- Insertion des données de migration
- Commentaires de version
- Vérification post-migration

---

## 🚀 Étapes d'implémentation

### Étape 1: Appliquer la migration

```bash
# En local (test)
mysql -u root -p cv_funnel < core/sql/migrations/2026_04_29_category_dictionary.sql

# En production
# Via phpmyadmin ou via SSH:
mysql -h <host> -u <user> -p <db> < core/sql/migrations/2026_04_29_category_dictionary.sql
```

### Étape 2: Vérifier la migration

```sql
SELECT * FROM category_dictionary WHERE is_active = TRUE;
-- Doit retourner 3 lignes: ops, management, tech
```

### Étape 3: Tester les modifications

1. Naviguer vers le dashboard admin
2. Aller à l'onglet "CV"
3. Cliquer sur "+ Ajouter Experience" → La catégorie doit être pré-remplie
4. Cliquer sur "+ Ajouter Skill" → La catégorie doit être pré-remplie
5. Vérifier les selects des catégories (ils doivent avoir les bonnes options)

### Étape 4: Vérifier le CV interactif

1. Naviguer vers une page `/go/<slug>`
2. Vérifier que les sections "Compétences & Méthodologies" affichent les labels complets
3. Vérifier la section "Pourquoi + Adéquation" avec les bonnes catégories

---

## 🔄 Évolutions futures possibles

### Ajouter une nouvelle catégorie

```sql
INSERT INTO category_dictionary (code, label_short, label_long, display_order, icon_name)
VALUES ('marketing', 'Marketing', 'Marketing & Growth', 3, 'bullhorn');
```

La nouvelle catégorie apparaîtra **automatiquement** dans tous les selects!

### Archiver une catégorie

```sql
UPDATE category_dictionary SET is_active = FALSE WHERE code = 'tech';
```

### Modifier l'ordre d'affichage

```sql
UPDATE category_dictionary SET display_order = 0 WHERE code = 'tech';
UPDATE category_dictionary SET display_order = 1 WHERE code = 'ops';
UPDATE category_dictionary SET display_order = 2 WHERE code = 'management';
```

---

## 📊 Impact sur les données existantes

✅ **Aucune modification des données existantes** - Les `cv_experiences`, `cv_skills`, etc. restent inchangées  
✅ **Backward compatible** - Les ancien codes (`ops`, `management`, `tech`) sont conservés  
✅ **Zéro downtime** - La migration peut s'appliquer en production sans arrêt  

---

## 🐛 Troubleshooting

### Problème: Les selects sont vides

**Cause**: Les catégories n'ont pas été chargées  
**Solution**: Vérifier que `require_once __DIR__ . '/../category_helpers.php';` est présent

### Problème: Cache non mis à jour

**Cause**: Le cache session persiste  
**Solution**: 
```php
// Forcer le rechargement
unset($_SESSION['_categories_cache']);
get_categories(); // Recharge
```

### Problème: Les anciennes catégories ne s'affichent plus

**Cause**: Les codes `ops`, `management`, `tech` ne sont plus dans la DB  
**Cause**: Requête SQL pour vérifier:
```sql
SELECT * FROM category_dictionary WHERE code IN ('ops', 'management', 'tech');
-- Doit retourner 3 lignes
```

---

## 📝 Checklist de déploiement

- [ ] Sauvegarder la base de données
- [ ] Appliquer la migration SQL
- [ ] Vérifier que les 3 catégories existent
- [ ] Tester le dashboard admin (ajouter exp, skill)
- [ ] Tester le CV interactif
- [ ] Vérifier les logs d'erreur
- [ ] Documenter dans le changelog
- [ ] Notifier l'équipe

---

## 📚 Références

- **Fichier helpers**: `/core/category_helpers.php`
- **Migration SQL**: `/core/sql/migrations/2026_04_29_category_dictionary.sql`
- **Dashboard admin**: `/core/templates/admin_module_dashboard.php`
- **CV interactif**: `/core/templates/cv_interactive.php`

---

**✅ Proposition complète et prête à déployer!**
