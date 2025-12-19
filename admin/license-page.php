<?php
/**
 * Restaurant Menu - License Page
 * 
 * @package WP_Restaurant_Menu
 * @version 1.7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render license page
 */
function wpr_render_license_page() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'wp-restaurant-menu'));
    }

    // Handle license activation
    if (isset($_POST['wpr_activate_license'])) {
        check_admin_referer('wpr_license_action', 'wpr_license_nonce');
        
        $license_key = sanitize_text_field($_POST['license_key']);
        $result = WPR_License::activate($license_key);
        
        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
    
    // Handle license deactivation
    if (isset($_POST['wpr_deactivate_license'])) {
        check_admin_referer('wpr_license_action', 'wpr_license_nonce');
        
        WPR_License::deactivate();
        echo '<div class="notice notice-success is-dismissible"><p>' . __('License deactivated successfully!', 'wp-restaurant-menu') . '</p></div>';
    }
    
    // Handle test server
    if (isset($_POST['wpr_test_server'])) {
        check_admin_referer('wpr_license_action', 'wpr_license_nonce');
        
        $test = WPR_License::test_server();
        if ($test['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html($test['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ ' . esc_html($test['message']) . '</p></div>';
        }
    }
    
    // Handle refresh pricing
    if (isset($_POST['wpr_refresh_pricing'])) {
        check_admin_referer('wpr_license_action', 'wpr_license_nonce');
        
        $refresh = WPR_License::refresh_pricing();
        if ($refresh['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html($refresh['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ ' . esc_html($refresh['message']) . '</p></div>';
        }
    }

    // Get current license info
    $license_info = WPR_License::get_info();
    $pricing = WPR_License::get_pricing();
    $item_count = wp_count_posts('wpr_menu_item');
    $current_items = isset($item_count->publish) ? $item_count->publish : 0;
    
    ?>
    <div class="wrap">
        <h1>🔑 <?php echo esc_html__('Restaurant Menu - License', 'wp-restaurant-menu'); ?></h1>
        
        <!-- Current Status -->
        <div class="wpr-license-status">
            <h2><?php echo esc_html__('Current Status', 'wp-restaurant-menu'); ?></h2>
            <table class="widefat">
                <tr>
                    <td><strong><?php echo esc_html__('License Type:', 'wp-restaurant-menu'); ?></strong></td>
                    <td>
                        <?php 
                        $type_labels = array(
                            'free' => 'FREE',
                            'free_plus' => 'FREE+',
                            'pro' => 'PRO',
                            'pro_plus' => 'PRO+',
                            'ultimate' => 'ULTIMATE'
                        );
                        echo esc_html($type_labels[$license_info['type']] ?? 'Unknown');
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Dishes:', 'wp-restaurant-menu'); ?></strong></td>
                    <td>
                        <?php echo esc_html($current_items . ' / ' . ($license_info['max_items'] == 999 ? '∞' : $license_info['max_items'])); ?>
                        <?php if ($current_items >= $license_info['max_items'] && $license_info['max_items'] != 999) : ?>
                            <span style="color: red;">⚠️ <?php echo esc_html__('Limit reached!', 'wp-restaurant-menu'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Features:', 'wp-restaurant-menu'); ?></strong></td>
                    <td>
                        <?php 
                        $features = $license_info['features'];
                        if (empty($features)) {
                            echo esc_html__('Basic features only', 'wp-restaurant-menu');
                        } else {
                            $feature_names = array(
                                'dark_mode' => '🌙 Dark Mode',
                                'cart' => '🛒 Shopping Cart',
                                'unlimited' => '♾️ Unlimited Items'
                            );
                            $feature_list = array();
                            foreach ($features as $feature) {
                                $feature_list[] = $feature_names[$feature] ?? $feature;
                            }
                            echo esc_html(implode(', ', $feature_list));
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Server URL:', 'wp-restaurant-menu'); ?></strong></td>
                    <td><?php echo esc_url(WPR_License::get_server_url()); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Domain:', 'wp-restaurant-menu'); ?></strong></td>
                    <td><?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?></td>
                </tr>
            </table>
        </div>
        
        <!-- Available Licenses -->
        <div class="wpr-available-licenses">
            <h2><?php echo esc_html__('Available Licenses', 'wp-restaurant-menu'); ?></h2>
            <div class="wpr-license-grid">
                <?php foreach ($pricing as $type => $info) : ?>
                <div class="wpr-license-card <?php echo $license_info['type'] === $type ? 'active' : ''; ?>">
                    <?php if ($license_info['type'] === $type) : ?>
                    <div class="wpr-active-badge"><?php echo esc_html__('ACTIVE', 'wp-restaurant-menu'); ?></div>
                    <?php endif; ?>
                    
                    <h3><?php echo esc_html($info['label']); ?></h3>
                    <div class="wpr-price">
                        <?php 
                        if ($info['price'] == 0) {
                            echo esc_html__('Free', 'wp-restaurant-menu');
                        } else {
                            echo esc_html($info['price'] . ' ' . $info['currency']);
                        }
                        ?>
                    </div>
                    <p class="wpr-description"><?php echo esc_html($info['description']); ?></p>
                    <ul class="wpr-features">
                        <li>✅ <?php echo esc_html(sprintf(__('Up to %d dishes', 'wp-restaurant-menu'), $info['max_items'])); ?></li>
                        <?php if (!empty($info['features'])) : ?>
                            <?php foreach ($info['features'] as $feature) : ?>
                                <?php 
                                $feature_labels = array(
                                    'dark_mode' => '🌙 Dark Mode',
                                    'cart' => '🛒 Shopping Cart',
                                    'unlimited' => '♾️ Unlimited Items'
                                );
                                ?>
                                <li>✅ <?php echo esc_html($feature_labels[$feature] ?? $feature); ?></li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <li>📝 <?php echo esc_html__('Basic features', 'wp-restaurant-menu'); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Activate License -->
        <div class="wpr-activate-section">
            <h2><?php echo esc_html__('Activate License', 'wp-restaurant-menu'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="license_key"><?php echo esc_html__('License Key', 'wp-restaurant-menu'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="license_key" id="license_key" class="regular-text" 
                                   placeholder="WPR-XXXXX-XXXXX-XXXXX" 
                                   value="<?php echo esc_attr(get_option('wpr_license_key', '')); ?>">
                            <p class="description"><?php echo esc_html__('Enter your license key in the format: WPR-XXXXX-XXXXX-XXXXX', 'wp-restaurant-menu'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php if (WPR_License::is_active()) : ?>
                    <?php submit_button(__('Deactivate License', 'wp-restaurant-menu'), 'delete', 'wpr_deactivate_license'); ?>
                <?php else : ?>
                    <?php submit_button(__('Activate License', 'wp-restaurant-menu'), 'primary', 'wpr_activate_license'); ?>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Actions -->
        <div class="wpr-actions-section">
            <h2><?php echo esc_html__('Actions', 'wp-restaurant-menu'); ?></h2>
            <form method="post" action="" style="display: inline-block; margin-right: 10px;">
                <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
                <?php submit_button(__('🔍 Test Server', 'wp-restaurant-menu'), 'secondary', 'wpr_test_server', false); ?>
            </form>
            <form method="post" action="" style="display: inline-block;">
                <?php wp_nonce_field('wpr_license_action', 'wpr_license_nonce'); ?>
                <?php submit_button(__('🔄 Refresh Pricing', 'wp-restaurant-menu'), 'secondary', 'wpr_refresh_pricing', false); ?>
            </form>
        </div>
    </div>
    
    <style>
        .wpr-license-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .wpr-license-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            position: relative;
        }
        .wpr-license-card.active {
            border-color: #4CAF50;
            background: #f1f8f4;
        }
        .wpr-active-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .wpr-license-card h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
        }
        .wpr-price {
            font-size: 24px;
            font-weight: bold;
            color: #2271b1;
            margin-bottom: 10px;
        }
        .wpr-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .wpr-features {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .wpr-features li {
            padding: 5px 0;
            font-size: 14px;
        }
        .wpr-license-status,
        .wpr-available-licenses,
        .wpr-activate-section,
        .wpr-actions-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
    </style>
    <?php
}
