# 🚀 Guide rapide: Utiliser les catégories dynamiques

## Import requis

```php
require_once __DIR__ . '/../category_helpers.php';
init_categories_cache();
```

## 5 façons courantes d'utiliser les catégories

### 1️⃣ Afficher le label d'une catégorie

```php
<?= get_category_label('ops', 'long') ?>
<!-- Affiche: Excellence Opérationnelle -->

<?= get_category_label('ops', 'short') ?>
<!-- Affiche: Opérations -->
```

### 2️⃣ Générer un select automatique

```php
<!-- Basic -->
<?= render_category_select('category') ?>

<!-- Avec valeur pré-sélectionnée -->
<?= render_category_select('category', 'ops') ?>

<!-- Avec classe CSS et attributs Alpine.js -->
<?= render_category_select('category', '', 'custom-class', ['x-model' => 'editItem.category']) ?>
```

### 3️⃣ Boucler sur toutes les catégories

```php
<?php foreach (get_categories() as $cat): ?>
    <div>
        <h3><?= htmlspecialchars($cat['label_long']) ?></h3>
        <span><?= htmlspecialchars($cat['label_short']) ?></span>
    </div>
<?php endforeach; ?>
```

### 4️⃣ Vérifier si une catégorie existe

```php
$cat = get_category_by_code('ops');
if ($cat) {
    echo "Catégorie trouvée: " . $cat['label_long'];
} else {
    echo "Catégorie non trouvée";
}
```

### 5️⃣ Récupérer pour un select en PHP

```php
$options = get_categories_for_select();
// Retourne: ['ops' => 'Opérations', 'management' => 'Management', 'tech' => 'Technique']

foreach ($options as $code => $label) {
    echo "<option value='$code'>$label</option>";
}
```

## Dans Alpine.js

```html
<div x-data="appData()">
    <!-- Accéder aux catégories -->
    <span x-text="categories.length"></span>
    
    <!-- Boucler -->
    <template x-for="cat in categories" :key="cat.code">
        <option :value="cat.code" x-text="cat.label_short"></option>
    </template>
    
    <!-- Catégorie par défaut -->
    <input :value="defaultCategoryExp" />
</div>
```

## Dans les templates

### admin_module_dashboard.php
```php
<?= render_category_select('category', '', '', ['x-model' => 'editItem.category']) ?>
```

### cv_interactive.php
```php
<?= get_category_label($category, 'long') ?>
```

## Ajouter une nouvelle catégorie

### En SQL
```sql
INSERT INTO category_dictionary (code, label_short, label_long, display_order, icon_name)
VALUES ('design', 'Design', 'Design & UX', 3, 'palette');
```

### Elle apparaît **automatiquement** partout!

## Caching

Le cache session évite les requêtes répétées à la BD.

```php
// Force le rechargement
unset($_SESSION['_categories_cache']);
$categories = get_categories(); // Requête fresh à la BD
```

## Débogage

```php
// Afficher tout
echo '<pre>';
print_r(get_categories_indexed());
echo '</pre>';

// Vérifier une catégorie
$cat = get_category_by_code('ops');
var_dump($cat);
```

---

**💡 Astuce**: Toutes les fonctions retournent des tableaux associatifs PHP, donc utilisez `htmlspecialchars()` ou `json_encode()` selon le contexte!
