# ✅ Checklist de vérification technique

## Phase 1: Pré-déploiement (LOCAL)

### 1.1 Vérification des fichiers

- [ ] `/core/category_helpers.php` existe et contient 6 fonctions
- [ ] `/core/templates/admin_module_dashboard.php` inclut `category_helpers.php`
- [ ] `/core/templates/cv_interactive.php` inclut `category_helpers.php`
- [ ] `/core/sql/migrations/2026_04_29_category_dictionary.sql` existe
- [ ] `/CATEGORIES_DYNAMIQUES_PROPOSITION.md` documenter bien
- [ ] `/MIGRATION_PRODUCTION_READY.sql` prêt à copier-coller

### 1.2 Vérification du code PHP

```bash
# Vérifier les includes
grep -n "require_once.*category_helpers.php" core/templates/*.php
# Doit retourner 2 lignes (admin_module_dashboard + cv_interactive)
```

- [ ] Les deux includes sont présents
- [ ] Pas d'erreur de syntaxe: `php -l core/category_helpers.php`
- [ ] Pas d'erreur de syntaxe: `php -l core/templates/admin_module_dashboard.php`
- [ ] Pas d'erreur de syntaxe: `php -l core/templates/cv_interactive.php`

### 1.3 Vérification des remplacements

```bash
# Admin dashboard
grep -n "render_category_select" core/templates/admin_module_dashboard.php
# Doit retourner 4 lignes (3 selects + 1 appel supplémentaire)

grep -n "defaultCategoryExp\|defaultCategorySkill" core/templates/admin_module_dashboard.php
# Doit retourner 2 lignes (appData + 2 utilisations)
```

- [ ] `render_category_select` apparaît 4 fois
- [ ] `defaultCategoryExp` et `defaultCategorySkill` présents
- [ ] Les anciens selects hardcodés ont disparu

### 1.4 Vérification du SQL

```bash
# Vérifier que le SQL est valide
mysql --version  # s'assurer que vous avez mysql en CLI

# Sinon, verifier manuellement:
# - CREATE TABLE IF NOT EXISTS category_dictionary
# - 3 INSERT avec (ops, management, tech)
# - Indexes sur code, display_order, is_active
```

- [ ] Migration SQL valide
- [ ] Pas d'erreur de syntaxe SQL

---

## Phase 2: Test local en base de données

### 2.1 Préparation

- [ ] Créer une sauvegarde de la BD locale: `mysqldump -u root -p cv_funnel > backup_$(date +%s).sql`
- [ ] Appliquer la migration: `mysql -u root -p cv_funnel < core/sql/migrations/2026_04_29_category_dictionary.sql`

### 2.2 Vérification de la migration

```sql
-- Exécuter ces requêtes:
SELECT COUNT(*) FROM category_dictionary WHERE is_active = TRUE;
-- Résultat attendu: 3

SELECT code, label_short, label_long FROM category_dictionary ORDER BY display_order;
-- Résultat attendu:
-- ops | Opérations | Excellence Opérationnelle
-- management | Management | Management & Gouvernance
-- tech | Technique | Techniques & Technologie
```

- [ ] 3 catégories créées
- [ ] Les labels sont corrects
- [ ] `display_order` est correct (0, 1, 2)
- [ ] `is_active` = 1 pour tous

### 2.3 Test des fonctions PHP

Créer un fichier `test_categories.php`:

```php
<?php
require_once 'core/tools.php';  // Ou votre chargement de DB
require_once 'core/category_helpers.php';

echo "=== TEST CATÉGORIES ===\n\n";

// Test 1
echo "1. get_categories()\n";
$cats = get_categories();
echo "   Résultat: " . count($cats) . " catégories\n";
echo "   ✅ PASS\n\n";

// Test 2
echo "2. get_category_label('ops', 'long')\n";
$label = get_category_label('ops', 'long');
echo "   Résultat: $label\n";
echo (strpos($label, 'Excellence') !== false ? "   ✅ PASS\n\n" : "   ❌ FAIL\n\n");

// Test 3
echo "3. get_categories_for_select()\n";
$select = get_categories_for_select();
echo "   Résultat: " . json_encode($select) . "\n";
echo (count($select) === 3 ? "   ✅ PASS\n\n" : "   ❌ FAIL\n\n");

// Test 4
echo "4. render_category_select('test', 'ops')\n";
$html = render_category_select('test', 'ops');
echo (strpos($html, '<select') !== false ? "   ✅ PASS\n\n" : "   ❌ FAIL\n\n");

echo "=== FIN TEST ===\n";
?>
```

Puis lancer: `php test_categories.php`

- [ ] Test 1: 3 catégories retournées
- [ ] Test 2: 'Excellence Opérationnelle' retourné
- [ ] Test 3: 3 options dans le select
- [ ] Test 4: HTML valide généré

---

## Phase 3: Tests fonctionnels

### 3.1 Dashboard Admin

1. Accéder à `http://localhost/public/index.php?page=admin&tab=cv`
2. Cliquer sur l'onglet "CV"
3. Cliquer sur "Modifier" d'une expérience existante ou "+ Ajouter Experience"

- [ ] Le select "Catégorie" s'affiche correctement
- [ ] Les 3 options (Opérations, Management, Technique) sont visibles
- [ ] La valeur par défaut est définie (première catégorie)
- [ ] Pas d'erreur JavaScript en console

### 3.2 Ajout d'une expérience

1. Cliquer sur "+ Ajouter Experience"
2. Vérifier que le formulaire s'ouvre
3. Le champ "Catégorie" doit avoir une valeur pré-remplie

- [ ] Le formulaire s'ouvre sans erreur
- [ ] `category: defaultCategoryExp` s'est exécuté
- [ ] Alpine.js console: pas d'erreur

### 3.3 Skills

1. Cliquer sur "Compétences" dans le sidebar
2. Cliquer sur "+ Ajouter Skill"

- [ ] Le select "Catégorie" s'affiche
- [ ] Les 3 options sont visibles
- [ ] La valeur par défaut est définie

### 3.4 Candidatures (Applications)

1. Aller à l'onglet "Candidatures"
2. Ajouter ou modifier une candidature
3. Chercher le champ "Statut du dossier" (ou "default_lens")

- [ ] Le select "Catégorie" s'affiche dans la modale
- [ ] Les 3 options sont visibles
- [ ] Pas d'erreur

### 3.5 CV Interactif

1. Accéder à une page `/go/<slug>` (une candidature)
2. Scroller jusqu'à la section "Compétences & Méthodologies"

- [ ] Les titres s'affichent: "Management & Gouvernance", "Excellence Opérationnelle", "Techniques & Technologie"
- [ ] Les compétences sont bien groupées par catégorie
- [ ] Pas d'erreur JavaScript

### 3.6 Section "Indicateurs d'adéquation"

1. Scroller jusqu'au bas du CV (section grise "Pourquoi + ?")
2. Vérifier les indicateurs

- [ ] Les scores s'affichent correctement
- [ ] Le label "Expertise Opérations" (ou autre) s'affiche
- [ ] Les barres de progression sont visibles
- [ ] Pas d'erreur

---

## Phase 4: Test de pérennité

### 4.1 Ajouter une 4ème catégorie

En SQL:
```sql
INSERT INTO category_dictionary (code, label_short, label_long, display_order, icon_name)
VALUES ('design', 'Design', 'Design & UX', 3, 'palette');
```

Puis retourner au dashboard:

- [ ] Le nouveau select affiche "Design" comme option
- [ ] Les fonctions PHP retournent 4 catégories
- [ ] Pas d'erreur

### 4.2 Archiver une catégorie

En SQL:
```sql
UPDATE category_dictionary SET is_active = FALSE WHERE code = 'tech';
```

Vider le cache:
```php
unset($_SESSION['_categories_cache']);
```

Puis retourner au dashboard:

- [ ] Le select n'affiche plus "Technique"
- [ ] Les expériences/skills existantes avec `category = 'tech'` restent intactes
- [ ] Les nouveaux formulaires proposent 2 options seulement

- [ ] Soft-delete fonctionne

### 4.3 Réactiver

En SQL:
```sql
UPDATE category_dictionary SET is_active = TRUE WHERE code = 'tech';
```

Vider le cache et vérifier:

- [ ] "Technique" réapparaît dans les selects
- [ ] Pas de perte de données

---

## Phase 5: Logs et erreurs

### 5.1 Vérifier les logs PHP

```bash
# Regarder les erreurs PHP (adapter le chemin selon votre config)
tail -f /var/log/php-fpm/www-error.log

# Ou avec xdebug activé dans VS Code
```

- [ ] Pas d'erreur "Undefined variable"
- [ ] Pas d'erreur "Call to undefined function"
- [ ] Pas d'erreur SQL

### 5.2 Vérifier la console JavaScript

1. Ouvrir DevTools (F12)
2. Onglet "Console"
3. Naviguer sur le dashboard

- [ ] Pas d'erreur rouge
- [ ] Pas de warning lié aux catégories

### 5.3 Vérifier les requêtes SQL

Activer le log des requêtes dans `tools.php` ou votre logger:

```php
// Avant chaque query:
$db->query("SELECT * FROM category_dictionary...");
// Log: "Query executed: SELECT * FROM category_dictionary... (time: 2ms)"
```

- [ ] Requêtes optimisées (< 5ms)
- [ ] Cache session fonctionnant (2e appel plus rapide)

---

## Phase 6: Sécurité

### 6.1 Validation des entrées

- [ ] `htmlspecialchars()` utilisé sur tous les `$category` affichés
- [ ] `json_encode()` utilisé dans les attributs HTML
- [ ] Pas de SQL injection possible

### 6.2 Vérifier les requêtes paramétrées

```php
// ✅ BON - Requête paramétrée
$stmt = $db->prepare("SELECT * FROM category_dictionary WHERE code = ?");
$stmt->execute([$code]);

// ❌ MAUVAIS - Concaténation
$result = $db->query("SELECT * FROM category_dictionary WHERE code = '$code'");
```

- [ ] Toutes les requêtes utilisent des placeholders (?)
- [ ] Pas de concaténation directe de variables

---

## Phase 7: Performance

### 7.1 Temps de chargement

Avant et après, mesurer:

```bash
# Time the page load
time curl -s http://localhost/public/index.php?page=admin > /dev/null

# Avant: ~200ms
# Après: ~205ms (acceptable)
```

- [ ] Impact minimal (< 10ms ajouté)
- [ ] Cache session actif après 1er appel

### 7.2 Nombre de requêtes DB

```bash
# Avec logging activé, compter les queries

# Avant: 0 query pour les catégories
# Après: 1 query (cached ensuite)
```

- [ ] 1 seule requête par session
- [ ] Pas de requête en N+1

---

## Phase 8: Pré-production (STAGING)

Si vous avez un environnement de staging, répéter:

- [ ] Toutes les vérifications de la Phase 3 (tests fonctionnels)
- [ ] Tests avec données réelles
- [ ] Tests avec plusieurs utilisateurs simultanés
- [ ] Tests sur différents navigateurs

---

## Phase 9: Production FINAL

### 9.1 Avant le déploiement

```bash
# Sauvegarder la BD
mysqldump -h <HOST> -u <USER> -p <DB> > backup_prod_$(date +%Y%m%d_%H%M%S).sql

# Vérifier l'espace disque
df -h

# Vérifier les permissions
ls -la core/category_helpers.php
```

- [ ] Backup effectué et stocké
- [ ] Espace disque suffisant
- [ ] Permissions correctes (644 pour PHP, 755 pour répertoires)

### 9.2 Déploiement

```bash
# 1. Uploader les fichiers
rsync -avz core/category_helpers.php user@prod:/path/to/cv-funnel/core/

rsync -avz core/templates/admin_module_dashboard.php user@prod:/path/to/cv-funnel/core/templates/

rsync -avz core/templates/cv_interactive.php user@prod:/path/to/cv-funnel/core/templates/

# 2. Appliquer la migration
mysql -h PROD_HOST -u PROD_USER -p PROD_DB < MIGRATION_PRODUCTION_READY.sql

# 3. Invalider le cache
# (Selon votre implémentation - peut être un refresh de session)
```

- [ ] Fichiers uploadés
- [ ] Migration appliquée
- [ ] Cache invalidé

### 9.3 Post-déploiement

- [ ] Tester le dashboard admin (toutes les sections)
- [ ] Tester un CV interactif public
- [ ] Vérifier les logs d'erreur
- [ ] Demander à un utilisateur de tester
- [ ] Documenter la version déployée

---

## 🎯 Résumé rapide

| Phase | Durée | Criticité |
|-------|-------|-----------|
| Phase 1: Vérification fichiers | 5 min | 🔴 CRITIQUE |
| Phase 2: Tests DB | 10 min | 🔴 CRITIQUE |
| Phase 3: Tests fonctionnels | 15 min | 🔴 CRITIQUE |
| Phase 4: Pérennité | 10 min | 🟠 IMPORTANT |
| Phase 5: Logs | 5 min | 🟠 IMPORTANT |
| Phase 6: Sécurité | 5 min | 🔴 CRITIQUE |
| Phase 7: Performance | 10 min | 🟡 BON A SAVOIR |
| Phase 8: Staging | 20 min | 🟡 OPTIONNEL |
| Phase 9: Production | 15 min | 🔴 CRITIQUE |
| **TOTAL** | **~1.5h** | |

---

**✅ Une fois toutes les phases passées = Déploiement fiable en production!**
