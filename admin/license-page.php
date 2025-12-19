<?php
/**
 * License Page Template
 * 
 * @package WP_Restaurant_Menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Handle license activation
if (isset($_POST['wpr_activate_license']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
    $license_key = sanitize_text_field($_POST['wpr_license_key']);
    $result = WPR_License::activate($license_key);
    
    if ($result['success']) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
    }
}

// Handle license deactivation
if (isset($_POST['wpr_deactivate_license']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
    $result = WPR_License::deactivate();
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
}

// Handle server test
if (isset($_POST['wpr_test_server']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
    $result = WPR_License::test_server();
    $class = $result['success'] ? 'notice-success' : 'notice-error';
    echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
}

// Handle pricing refresh
if (isset($_POST['wpr_refresh_pricing']) && check_admin_referer('wpr_license_action', 'wpr_license_nonce')) {
    delete_transient('wpr_pricing_data');
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Preise aktualisiert!', 'wp-restaurant-menu') . '</p></div>';
}

$license_data = WPR_License::get_license_data();
$current_license_key = get_option('wpr_license_key', '');
$item_count = WPR_License::get_item_count();
$items_remaining = WPR_License::get_items_remaining();
$pricing = WPR_License::get_pricing();
?>

<div class="wrap wpr-license-page">
    <h1><?php _e('Lizenz-Verwaltung', 'wp-restaurant-menu'); ?></h1>
    
    <!-- Current License Status -->
    <div class="wpr-license-status-card">
        <h2><?php _e('Aktueller Status', 'wp-restaurant-menu'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php _e('Lizenz-Typ', 'wp-restaurant-menu'); ?></th>
                    <td>
                        <strong><?php echo esc_html(WPR_License::get_type_display_name()); ?></strong>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Gerichte', 'wp-restaurant-menu'); ?></th>
                    <td>
                        <?php echo esc_html($item_count); ?> / <?php echo esc_html($license_data['max_items'] >= 999999 ? '∞' : $license_data['max_items']); ?>
                        <span style="color: #135e96;">(<?php echo esc_html($items_remaining >= 999999 ? '∞' : $items_remaining); ?> verfügbar)</span>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Features', 'wp-restaurant-menu'); ?></th>
                    <td>
                        <?php if (empty($license_data['features'])): ?>
                            <span style="color: #666;"><?php _e('Basis-Features', 'wp-restaurant-menu'); ?></span>
                        <?php else: ?>
                            <?php foreach ($license_data['features'] as $feature): ?>
                                <?php
                                $feature_names = array(
                                    'dark_mode' => '🌙 Dark Mode',
                                    'cart' => '🛒 Warenkorb',
                                    'unlimited' => '♾️ Unlimited Items',
                                );
                                echo '<span class="wpr-feature-badge">' . esc_html($feature_names[$feature] ?? $feature) . '</span> ';
                                ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Server-URL', 'wp-restaurant-menu'); ?></th>
                    <td><code><?php echo esc_html(WPR_License::get_server_url()); ?></code></td>
                </tr>
                <tr>
                    <th><?php _e('Domain', 'wp-restaurant-menu'); ?></th>
                    <td><code><?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?></code></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Available Licenses -->
    <div class="wpr-pricing-section">
        <h2><?php _e('Verfügbare Lizenzen', 'wp-restaurant-menu'); ?></h2>
        
        <div class="wpr-pricing-grid">
            <?php foreach ($pricing as $type => $package): ?>
                <div class="wpr-pricing-card <?php echo $license_data['type'] === $type ? 'active' : ''; ?>">
                    <?php if ($license_data['type'] === $type): ?>
                        <div class="wpr-active-badge">AKTIV</div>
                    <?php endif; ?>
                    
                    <h3><?php echo esc_html($package['label']); ?></h3>
                    
                    <div class="wpr-price">
                        <?php if ($package['price'] > 0): ?>
                            <?php echo esc_html($package['price']); ?> <?php echo esc_html($package['currency']); ?>
                        <?php else: ?>
                            <?php _e('Kostenlos', 'wp-restaurant-menu'); ?>
                        <?php endif; ?>
                    </div>
                    
                    <p class="wpr-description"><?php echo esc_html($package['description']); ?></p>
                    
                    <ul class="wpr-features">
                        <li>✔️ <?php echo esc_html($package['max_items']); ?> <?php _e('Gerichte', 'wp-restaurant-menu'); ?></li>
                        <?php if (!empty($package['features'])): ?>
                            <?php foreach ($package['features'] as $feature): ?>
                                <li>✔️ <?php
                                $feature_names = array(
                                    'dark_mode' => 'Dark Mode',
                                    'cart' => 'Warenkorb-System',
                                    'unlimited' => 'Unbegrenzte Gerichte',
                                );
                                echo esc_html($feature_names[$feature] ?? $feature);
                                ?></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- License Activation -->
    <div class="wpr-license-activation">
        <h2><?php _e('Lizenz aktivieren', 'wp-restaurant-menu'); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="wpr_license_key"><?php _e('Lizenzschlüssel', 'wp-restaurant-menu'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="wpr_license_key" id="wpr_license_key" 
                                value="<?php echo esc_attr($current_license_key); ?>" 
                                class="regular-text" 
                                placeholder="WPR-XXXXX-XXXXX-XXXXX">
                            <p class="description"><?php _e('Geben Sie Ihren Lizenzschlüssel ein (Format: WPR-XXXXX-XXXXX-XXXXX)', 'wp-restaurant-menu'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <button type="submit" name="wpr_activate_license" class="button button-primary">
                    <?php _e('Lizenz aktivieren', 'wp-restaurant-menu'); ?>
                </button>
                
                <?php if ($current_license_key): ?>
                    <button type="submit" name="wpr_deactivate_license" class="button button-secondary">
                        <?php _e('Lizenz deaktivieren', 'wp-restaurant-menu'); ?>
                    </button>
                <?php endif; ?>
            </p>
        </form>
    </div>
    
    <!-- Actions -->
    <div class="wpr-license-actions">
        <h2><?php _e('Aktionen', 'wp-restaurant-menu'); ?></h2>
        
        <form method="post" action="" style="display: inline-block; margin-right: 10px;">
            <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
            <button type="submit" name="wpr_test_server" class="button">
                🔍 <?php _e('Server testen', 'wp-restaurant-menu'); ?>
            </button>
        </form>
        
        <form method="post" action="" style="display: inline-block;">
            <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
            <button type="submit" name="wpr_refresh_pricing" class="button">
                🔄 <?php _e('Preise aktualisieren', 'wp-restaurant-menu'); ?>
            </button>
        </form>
    </div>
</div>

<style>
.wpr-license-page {
    max-width: 1200px;
}

.wpr-license-status-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
}

.wpr-feature-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #2271b1;
    color: white;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 5px;
}

.wpr-pricing-section {
    margin-bottom: 30px;
}

.wpr-pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.wpr-pricing-card {
    background: #fff;
    border: 2px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    position: relative;
    transition: all 0.3s ease;
}

.wpr-pricing-card.active {
    border-color: #2271b1;
    box-shadow: 0 4px 12px rgba(34, 113, 177, 0.2);
}

.wpr-pricing-card h3 {
    margin-top: 0;
    font-size: 20px;
    color: #1d2327;
}

.wpr-active-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #00a32a;
    color: white;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.wpr-price {
    font-size: 32px;
    font-weight: 700;
    color: #2271b1;
    margin: 15px 0;
}

.wpr-description {
    color: #666;
    font-size: 13px;
    margin: 15px 0;
    min-height: 40px;
}

.wpr-features {
    list-style: none;
    padding: 0;
    margin: 0;
}

.wpr-features li {
    padding: 5px 0;
    font-size: 13px;
    color: #1d2327;
}

.wpr-license-activation,
.wpr-license-actions {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}
</style>