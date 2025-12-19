<?php
/**
 * Allergens Meta Box Template
 * 
 * @package WP_Restaurant_Menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

$allergens_list = array(
    'a' => 'A - Glutenhaltiges Getreide 🌾',
    'b' => 'B - Krebstiere 🦀',
    'c' => 'C - Eier 🥚',
    'd' => 'D - Fisch 🐟',
    'e' => 'E - Erdnüsse 🥜',
    'f' => 'F - Soja 🌱',
    'g' => 'G - Milch/Laktose 🥛',
    'h' => 'H - Schalenfrüchte 🌰',
    'l' => 'L - Sellerie 🥬',
    'm' => 'M - Senf 🍯',
    'n' => 'N - Sesamsamen 🌾',
    'o' => 'O - Schwefeldioxid 🧪',
    'p' => 'P - Lupinen 🏺',
    'r' => 'R - Weichtiere 🦐',
);

$selected_allergens = is_array($allergens) ? $allergens : array();
?>

<div class="wpr-allergens-meta-box">
    <!-- Dietary Info -->
    <div class="wpr-dietary-section">
        <h4><?php _e('Ernährung', 'wp-restaurant-menu'); ?></h4>
        
        <p>
            <label>
                <input type="checkbox" 
                    name="wpr_vegan" 
                    value="1" 
                    <?php checked($vegan, 1); ?>>
                <span class="wpr-diet-icon">🌿</span> <?php _e('Vegan', 'wp-restaurant-menu'); ?>
            </label>
        </p>
        
        <p>
            <label>
                <input type="checkbox" 
                    name="wpr_vegetarian" 
                    value="1" 
                    <?php checked($vegetarian, 1); ?>>
                <span class="wpr-diet-icon">🥗</span> <?php _e('Vegetarisch', 'wp-restaurant-menu'); ?>
            </label>
        </p>
    </div>
    
    <hr>
    
    <!-- Allergens -->
    <div class="wpr-allergens-section">
        <h4><?php _e('Allergene (EU 14)', 'wp-restaurant-menu'); ?></h4>
        
        <div class="wpr-allergen-list">
            <?php foreach ($allergens_list as $code => $name): ?>
                <label class="wpr-allergen-item">
                    <input type="checkbox" 
                        name="wpr_allergens[]" 
                        value="<?php echo esc_attr($code); ?>"
                        <?php checked(in_array($code, $selected_allergens)); ?>>
                    <?php echo esc_html($name); ?>
                </label>
            <?php endforeach; ?>
        </div>
        
        <p class="description">
            <?php _e('Wählen Sie alle zutreffenden Allergene aus. Diese werden im Frontend angezeigt.', 'wp-restaurant-menu'); ?>
        </p>
    </div>
</div>

<style>
.wpr-allergens-meta-box {
    padding: 10px 0;
}

.wpr-dietary-section,
.wpr-allergens-section {
    margin-bottom: 15px;
}

.wpr-dietary-section h4,
.wpr-allergens-section h4 {
    margin: 0 0 10px 0;
    font-size: 13px;
    font-weight: 600;
}

.wpr-diet-icon {
    font-size: 16px;
    vertical-align: middle;
}

.wpr-allergen-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    margin: 10px 0;
}

.wpr-allergen-item {
    display: flex;
    align-items: center;
    padding: 5px;
    cursor: pointer;
}

.wpr-allergen-item input[type="checkbox"] {
    margin: 0 8px 0 0;
}

.wpr-allergen-item:hover {
    background: #f6f7f7;
    border-radius: 3px;
}

.wpr-allergens-meta-box hr {
    margin: 15px 0;
    border: 0;
    border-top: 1px solid #dcdcde;
}

.wpr-allergens-meta-box .description {
    color: #666;
    font-size: 12px;
    margin-top: 10px;
}
</style>