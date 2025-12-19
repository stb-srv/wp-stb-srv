<?php
/**
 * Settings Page Template
 * 
 * @package WP_Restaurant_Menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Handle form submission
if (isset($_POST['wpr_settings_submit']) && check_admin_referer('wpr_settings_action', 'wpr_settings_nonce')) {
    $settings = array(
        'currency_symbol' => sanitize_text_field($_POST['wpr_currency_symbol']),
        'currency_position' => sanitize_text_field($_POST['wpr_currency_position']),
        'show_images' => isset($_POST['wpr_show_images']) ? 'yes' : 'no',
        'image_position' => sanitize_text_field($_POST['wpr_image_position']),
        'show_search' => isset($_POST['wpr_show_search']) ? 'yes' : 'no',
        'group_by_category' => isset($_POST['wpr_group_by_category']) ? 'yes' : 'no',
        'columns' => intval($_POST['wpr_columns']),
        'dark_mode_enabled' => isset($_POST['wpr_dark_mode_enabled']) ? 'yes' : 'no',
        'dark_mode_scope' => sanitize_text_field($_POST['wpr_dark_mode_scope']),
        'dark_mode_method' => sanitize_text_field($_POST['wpr_dark_mode_method']),
        'dark_mode_position' => sanitize_text_field($_POST['wpr_dark_mode_position']),
    );
    
    update_option('wpr_settings', $settings);
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Einstellungen gespeichert!', 'wp-restaurant-menu') . '</p></div>';
}

$settings = get_option('wpr_settings', array());
$has_dark_mode = WPR_License::has_dark_mode();
?>

<div class="wrap">
    <h1><?php _e('Restaurant Menü Einstellungen', 'wp-restaurant-menu'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wpr_settings_action', 'wpr_settings_nonce'); ?>
        
        <table class="form-table">
            <tbody>
                <!-- Currency Settings -->
                <tr>
                    <th colspan="2">
                        <h2><?php _e('Währungseinstellungen', 'wp-restaurant-menu'); ?></h2>
                    </th>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_currency_symbol"><?php _e('Währungssymbol', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_currency_symbol" id="wpr_currency_symbol">
                            <option value="€" <?php selected($settings['currency_symbol'] ?? '€', '€'); ?>>€</option>
                            <option value="EUR" <?php selected($settings['currency_symbol'] ?? '€', 'EUR'); ?>>EUR</option>
                            <option value="EURO" <?php selected($settings['currency_symbol'] ?? '€', 'EURO'); ?>>EURO</option>
                            <option value="$" <?php selected($settings['currency_symbol'] ?? '€', '$'); ?>>$</option>
                            <option value="£" <?php selected($settings['currency_symbol'] ?? '€', '£'); ?>>£</option>
                            <option value="CHF" <?php selected($settings['currency_symbol'] ?? '€', 'CHF'); ?>>CHF</option>
                        </select>
                        <p class="description"><?php _e('Wählen Sie das Währungssymbol für Ihre Preise.', 'wp-restaurant-menu'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_currency_position"><?php _e('Währungsposition', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_currency_position" id="wpr_currency_position">
                            <option value="before" <?php selected($settings['currency_position'] ?? 'after', 'before'); ?>><?php _e('Vor dem Preis', 'wp-restaurant-menu'); ?></option>
                            <option value="after" <?php selected($settings['currency_position'] ?? 'after', 'after'); ?>><?php _e('Nach dem Preis', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <!-- Image Settings -->
                <tr>
                    <th colspan="2">
                        <h2><?php _e('Bild-Einstellungen', 'wp-restaurant-menu'); ?></h2>
                    </th>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_show_images"><?php _e('Bilder anzeigen', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="wpr_show_images" id="wpr_show_images" value="1" <?php checked($settings['show_images'] ?? 'yes', 'yes'); ?>>
                        <label for="wpr_show_images"><?php _e('Featured Images in der Menüanzeige anzeigen', 'wp-restaurant-menu'); ?></label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_image_position"><?php _e('Bild-Position', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_image_position" id="wpr_image_position">
                            <option value="top" <?php selected($settings['image_position'] ?? 'left', 'top'); ?>><?php _e('Oben', 'wp-restaurant-menu'); ?></option>
                            <option value="left" <?php selected($settings['image_position'] ?? 'left', 'left'); ?>><?php _e('Links', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <!-- Layout Settings -->
                <tr>
                    <th colspan="2">
                        <h2><?php _e('Layout-Einstellungen', 'wp-restaurant-menu'); ?></h2>
                    </th>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_show_search"><?php _e('Suchfunktion', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="wpr_show_search" id="wpr_show_search" value="1" <?php checked($settings['show_search'] ?? 'yes', 'yes'); ?>>
                        <label for="wpr_show_search"><?php _e('Suchfeld anzeigen', 'wp-restaurant-menu'); ?></label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_group_by_category"><?php _e('Nach Kategorien gruppieren', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="wpr_group_by_category" id="wpr_group_by_category" value="1" <?php checked($settings['group_by_category'] ?? 'yes', 'yes'); ?>>
                        <label for="wpr_group_by_category"><?php _e('Gerichte in Accordion nach Kategorien anzeigen', 'wp-restaurant-menu'); ?></label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_columns"><?php _e('Spalten-Layout', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_columns" id="wpr_columns">
                            <option value="1" <?php selected($settings['columns'] ?? 2, 1); ?>>1 <?php _e('Spalte', 'wp-restaurant-menu'); ?></option>
                            <option value="2" <?php selected($settings['columns'] ?? 2, 2); ?>>2 <?php _e('Spalten', 'wp-restaurant-menu'); ?></option>
                            <option value="3" <?php selected($settings['columns'] ?? 2, 3); ?>>3 <?php _e('Spalten', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <!-- Dark Mode Settings -->
                <tr>
                    <th colspan="2">
                        <h2>
                            <?php _e('Dark Mode', 'wp-restaurant-menu'); ?>
                            <?php if (!$has_dark_mode): ?>
                                <span class="wpr-premium-badge">🔒 PRO+ Feature</span>
                            <?php endif; ?>
                        </h2>
                    </th>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_dark_mode_enabled"><?php _e('Dark Mode aktivieren', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="wpr_dark_mode_enabled" id="wpr_dark_mode_enabled" value="1" 
                            <?php checked($settings['dark_mode_enabled'] ?? 'no', 'yes'); ?>
                            <?php disabled(!$has_dark_mode); ?>>
                        <label for="wpr_dark_mode_enabled"><?php _e('Dark Mode für Menü aktivieren', 'wp-restaurant-menu'); ?></label>
                        <?php if (!$has_dark_mode): ?>
                            <p class="description" style="color: #d63638;"><?php _e('Dieses Feature ist nur in PRO+ und ULTIMATE verfügbar.', 'wp-restaurant-menu'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <?php if ($has_dark_mode): ?>
                <tr>
                    <th scope="row">
                        <label for="wpr_dark_mode_scope"><?php _e('Dark Mode Bereich', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_dark_mode_scope" id="wpr_dark_mode_scope">
                            <option value="global" <?php selected($settings['dark_mode_scope'] ?? 'menu', 'global'); ?>><?php _e('Global (ganze Website)', 'wp-restaurant-menu'); ?></option>
                            <option value="menu" <?php selected($settings['dark_mode_scope'] ?? 'menu', 'menu'); ?>><?php _e('Lokal (nur Menü-Bereich)', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_dark_mode_method"><?php _e('Umschalt-Methode', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_dark_mode_method" id="wpr_dark_mode_method">
                            <option value="manual" <?php selected($settings['dark_mode_method'] ?? 'manual', 'manual'); ?>><?php _e('Manuell (Toggle Button)', 'wp-restaurant-menu'); ?></option>
                            <option value="auto" <?php selected($settings['dark_mode_method'] ?? 'manual', 'auto'); ?>><?php _e('Automatisch (System-Einstellung)', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wpr_dark_mode_position"><?php _e('Toggle Position', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_dark_mode_position" id="wpr_dark_mode_position">
                            <option value="bottom-right" <?php selected($settings['dark_mode_position'] ?? 'bottom-right', 'bottom-right'); ?>><?php _e('Unten rechts', 'wp-restaurant-menu'); ?></option>
                            <option value="bottom-left" <?php selected($settings['dark_mode_position'] ?? 'bottom-right', 'bottom-left'); ?>><?php _e('Unten links', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
                <?php endif; ?>
                
            </tbody>
        </table>
        
        <?php submit_button(__('Einstellungen speichern', 'wp-restaurant-menu'), 'primary', 'wpr_settings_submit'); ?>
    </form>
</div>

<style>
.wpr-premium-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #d63638;
    color: white;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 10px;
}
</style>