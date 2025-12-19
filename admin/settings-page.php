<?php
/**
 * Restaurant Menu - Settings Page
 * 
 * @package WP_Restaurant_Menu
 * @version 1.7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render settings page
 */
function wpr_render_settings_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'wp-restaurant-menu'));
    }

    // Save settings
    if (isset($_POST['wpr_settings_submit'])) {
        check_admin_referer('wpr_settings_action', 'wpr_settings_nonce');
        
        update_option('wpr_currency_symbol', sanitize_text_field($_POST['wpr_currency_symbol']));
        update_option('wpr_currency_position', sanitize_text_field($_POST['wpr_currency_position']));
        update_option('wpr_show_images', isset($_POST['wpr_show_images']) ? '1' : '0');
        update_option('wpr_image_position', sanitize_text_field($_POST['wpr_image_position']));
        update_option('wpr_show_search', isset($_POST['wpr_show_search']) ? '1' : '0');
        update_option('wpr_group_by_category', isset($_POST['wpr_group_by_category']) ? '1' : '0');
        update_option('wpr_columns', absint($_POST['wpr_columns']));
        
        // Dark Mode settings (PRO+ feature)
        if (WPR_License::has_dark_mode()) {
            update_option('wpr_dark_mode_enabled', isset($_POST['wpr_dark_mode_enabled']) ? '1' : '0');
            update_option('wpr_dark_mode_scope', sanitize_text_field($_POST['wpr_dark_mode_scope']));
            update_option('wpr_dark_mode_method', sanitize_text_field($_POST['wpr_dark_mode_method']));
            update_option('wpr_dark_mode_toggle_position', sanitize_text_field($_POST['wpr_dark_mode_toggle_position']));
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'wp-restaurant-menu') . '</p></div>';
    }

    // Get current settings
    $currency_symbol = get_option('wpr_currency_symbol', '€');
    $currency_position = get_option('wpr_currency_position', 'after');
    $show_images = get_option('wpr_show_images', '1');
    $image_position = get_option('wpr_image_position', 'left');
    $show_search = get_option('wpr_show_search', '1');
    $group_by_category = get_option('wpr_group_by_category', '1');
    $columns = get_option('wpr_columns', '2');
    
    $dark_mode_enabled = get_option('wpr_dark_mode_enabled', '0');
    $dark_mode_scope = get_option('wpr_dark_mode_scope', 'menu');
    $dark_mode_method = get_option('wpr_dark_mode_method', 'manual');
    $dark_mode_toggle_position = get_option('wpr_dark_mode_toggle_position', 'bottom-right');
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Restaurant Menu - Settings', 'wp-restaurant-menu'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('wpr_settings_action', 'wpr_settings_nonce'); ?>
            
            <!-- Currency Settings -->
            <h2><?php echo esc_html__('Currency Settings', 'wp-restaurant-menu'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="wpr_currency_symbol"><?php echo esc_html__('Currency Symbol', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_currency_symbol" id="wpr_currency_symbol">
                            <option value="€" <?php selected($currency_symbol, '€'); ?>>€ (Euro)</option>
                            <option value="EUR" <?php selected($currency_symbol, 'EUR'); ?>>EUR</option>
                            <option value="$" <?php selected($currency_symbol, '$'); ?>>$ (Dollar)</option>
                            <option value="£" <?php selected($currency_symbol, '£'); ?>>£ (Pound)</option>
                            <option value="CHF" <?php selected($currency_symbol, 'CHF'); ?>>CHF (Swiss Franc)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpr_currency_position"><?php echo esc_html__('Currency Position', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_currency_position" id="wpr_currency_position">
                            <option value="before" <?php selected($currency_position, 'before'); ?>><?php echo esc_html__('Before Price', 'wp-restaurant-menu'); ?></option>
                            <option value="after" <?php selected($currency_position, 'after'); ?>><?php echo esc_html__('After Price', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <!-- Image Settings -->
            <h2><?php echo esc_html__('Image Settings', 'wp-restaurant-menu'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Show Images', 'wp-restaurant-menu'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpr_show_images" value="1" <?php checked($show_images, '1'); ?>>
                            <?php echo esc_html__('Display dish images', 'wp-restaurant-menu'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpr_image_position"><?php echo esc_html__('Image Position', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_image_position" id="wpr_image_position">
                            <option value="top" <?php selected($image_position, 'top'); ?>><?php echo esc_html__('Top', 'wp-restaurant-menu'); ?></option>
                            <option value="left" <?php selected($image_position, 'left'); ?>><?php echo esc_html__('Left', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <!-- Layout Settings -->
            <h2><?php echo esc_html__('Layout Settings', 'wp-restaurant-menu'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Search Function', 'wp-restaurant-menu'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpr_show_search" value="1" <?php checked($show_search, '1'); ?>>
                            <?php echo esc_html__('Enable search field', 'wp-restaurant-menu'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Group by Categories', 'wp-restaurant-menu'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpr_group_by_category" value="1" <?php checked($group_by_category, '1'); ?>>
                            <?php echo esc_html__('Show accordion grouping', 'wp-restaurant-menu'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpr_columns"><?php echo esc_html__('Column Layout', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_columns" id="wpr_columns">
                            <option value="1" <?php selected($columns, '1'); ?>>1 <?php echo esc_html__('Column', 'wp-restaurant-menu'); ?></option>
                            <option value="2" <?php selected($columns, '2'); ?>>2 <?php echo esc_html__('Columns', 'wp-restaurant-menu'); ?></option>
                            <option value="3" <?php selected($columns, '3'); ?>>3 <?php echo esc_html__('Columns', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <!-- Dark Mode Settings (PRO+ Feature) -->
            <?php if (WPR_License::has_dark_mode()) : ?>
            <h2>🌙 <?php echo esc_html__('Dark Mode Settings', 'wp-restaurant-menu'); ?> <span class="wpr-badge wpr-badge-pro">PRO+</span></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php echo esc_html__('Enable Dark Mode', 'wp-restaurant-menu'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpr_dark_mode_enabled" value="1" <?php checked($dark_mode_enabled, '1'); ?>>
                            <?php echo esc_html__('Activate dark mode', 'wp-restaurant-menu'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpr_dark_mode_scope"><?php echo esc_html__('Scope', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_dark_mode_scope" id="wpr_dark_mode_scope">
                            <option value="global" <?php selected($dark_mode_scope, 'global'); ?>><?php echo esc_html__('Global (entire website)', 'wp-restaurant-menu'); ?></option>
                            <option value="menu" <?php selected($dark_mode_scope, 'menu'); ?>><?php echo esc_html__('Menu only', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpr_dark_mode_method"><?php echo esc_html__('Switch Method', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_dark_mode_method" id="wpr_dark_mode_method">
                            <option value="manual" <?php selected($dark_mode_method, 'manual'); ?>><?php echo esc_html__('Manual (toggle button)', 'wp-restaurant-menu'); ?></option>
                            <option value="auto" <?php selected($dark_mode_method, 'auto'); ?>><?php echo esc_html__('Automatic (system preference)', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpr_dark_mode_toggle_position"><?php echo esc_html__('Toggle Position', 'wp-restaurant-menu'); ?></label>
                    </th>
                    <td>
                        <select name="wpr_dark_mode_toggle_position" id="wpr_dark_mode_toggle_position">
                            <option value="bottom-right" <?php selected($dark_mode_toggle_position, 'bottom-right'); ?>><?php echo esc_html__('Bottom Right', 'wp-restaurant-menu'); ?></option>
                            <option value="bottom-left" <?php selected($dark_mode_toggle_position, 'bottom-left'); ?>><?php echo esc_html__('Bottom Left', 'wp-restaurant-menu'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php else : ?>
            <h2>🌙 <?php echo esc_html__('Dark Mode Settings', 'wp-restaurant-menu'); ?> <span class="wpr-badge wpr-badge-locked">🔒 PRO+ Required</span></h2>
            <p><?php echo esc_html__('Upgrade to PRO+ or ULTIMATE license to unlock Dark Mode features.', 'wp-restaurant-menu'); ?></p>
            <?php endif; ?>
            
            <?php submit_button(__('Save Settings', 'wp-restaurant-menu'), 'primary', 'wpr_settings_submit'); ?>
        </form>
    </div>
    
    <style>
        .wpr-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 3px;
            margin-left: 8px;
        }
        .wpr-badge-pro {
            background: #4CAF50;
            color: white;
        }
        .wpr-badge-locked {
            background: #9E9E9E;
            color: white;
        }
    </style>
    <?php
}
