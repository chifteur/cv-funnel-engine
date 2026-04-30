<?php
/**
 * CATEGORY DICTIONARY HELPERS
 * Fonctions pour gérer les catégories de manière centralisée
 * 
 * Usage:
 *   $categories = get_categories();
 *   $category = get_category_by_code('ops');
 *   $label = get_category_label($code, 'long');
 */

/**
 * Récupère toutes les catégories actives
 * @param bool $order_by_display Si true, ordonne par display_order
 * @return array
 */
function get_categories($order_by_display = true) {
    $db = get_db_connection();
    $sql = "SELECT * FROM category_dictionary WHERE is_active = TRUE";
    
    if ($order_by_display) {
        $sql .= " ORDER BY display_order ASC";
    }
    
    $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Cache le résultat en session pour éviter les requêtes répétées
    $_SESSION['_categories_cache'] = $result;
    
    return $result ?: [];
}

/**
 * Récupère une catégorie par son code
 * @param string $code Code unique (ops, management, tech)
 * @return array|null
 */
function get_category_by_code($code) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT * FROM category_dictionary WHERE code = ? AND is_active = TRUE LIMIT 1");
    $stmt->execute([$code]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Récupère le label d'une catégorie
 * @param string $code Code de la catégorie
 * @param string $type 'short' ou 'long'
 * @return string
 */
function get_category_label($code, $type = 'short') {
    $category = get_category_by_code($code);
    
    if (!$category) {
        return htmlspecialchars($code); // Fallback
    }
    
    $key = ($type === 'long') ? 'label_long' : 'label_short';
    return htmlspecialchars($category[$key] ?? $code);
}

/**
 * Retourne toutes les catégories comme un tableau code => label_short
 * Utile pour les selects HTML
 * @return array
 */
function get_categories_for_select() {
    $categories = get_categories();
    $result = [];
    
    foreach ($categories as $cat) {
        $result[$cat['code']] = $cat['label_short'];
    }
    
    return $result;
}

/**
 * Retourne le tableau des catégories avec tous leurs détails
 * Utile pour les templates
 * @return array Format: ['code' => [...details...]]
 */
function get_categories_indexed() {
    $categories = get_categories();
    $result = [];
    
    foreach ($categories as $cat) {
        $result[$cat['code']] = $cat;
    }
    
    return $result;
}

/**
 * Affiche un select HTML avec toutes les catégories
 * @param string $name Attribut 'name' du select
 * @param string $current_value Valeur sélectionnée
 * @param string $css_class Classes CSS supplémentaires
 * @param array $attributes Attributs HTML additionnels (x-model, etc.)
 * @return string HTML
 */
function render_category_select($name, $current_value = '', $css_class = '', $attributes = []) {
    $categories = get_categories();
    
    $attrs_str = '';
    foreach ($attributes as $key => $value) {
        $attrs_str .= " {$key}=\"" . htmlspecialchars($value) . "\"";
    }
    
    $html = "<select name=\"{$name}\" class=\"border p-3 rounded-xl w-full bg-slate-50 {$css_class}\"{$attrs_str}>";
    
    foreach ($categories as $cat) {
        $selected = ($cat['code'] === $current_value) ? 'selected' : '';
        $html .= sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($cat['code']),
            $selected,
            htmlspecialchars($cat['label_short'])
        );
    }
    
    $html .= "</select>";
    
    return $html;
}

/**
 * Initialise le cache des catégories au chargement
 * À appeler une seule fois au démarrage de la session
 */
function init_categories_cache() {
    if (empty($_SESSION['_categories_cache'])) {
        get_categories();
    }
}

?>
