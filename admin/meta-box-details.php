<?php
/**
 * Menu Details Meta Box Template
 * 
 * @package WP_Restaurant_Menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}
?>

<div class="wpr-meta-box">
    <p>
        <label for="wpr_dish_number"><strong><?php _e('Gericht-Nummer', 'wp-restaurant-menu'); ?></strong></label><br>
        <input type="text" 
            name="wpr_dish_number" 
            id="wpr_dish_number" 
            value="<?php echo esc_attr($dish_number); ?>" 
            class="regular-text"
            placeholder="z.B. 12">
        <span class="description"><?php _e('Optionale Nummer für die Gericht-Identifikation (z.B. für Bestellungen)', 'wp-restaurant-menu'); ?></span>
    </p>
    
    <p>
        <label for="wpr_price"><strong><?php _e('Preis', 'wp-restaurant-menu'); ?> *</strong></label><br>
        <input type="text" 
            name="wpr_price" 
            id="wpr_price" 
            value="<?php echo esc_attr($price); ?>" 
            class="regular-text"
            placeholder="8.50"
            required>
        <span class="description"><?php _e('Preis ohne Währungssymbol (z.B. 8.50)', 'wp-restaurant-menu'); ?></span>
    </p>
</div>

<style>
.wpr-meta-box p {
    margin-bottom: 15px;
}

.wpr-meta-box label {
    display: block;
    margin-bottom: 5px;
}

.wpr-meta-box .description {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}
</style>