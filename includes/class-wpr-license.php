<?php
/**
 * License Management Class
 * Version: 2.3.1
 * 
 * @package WP_Restaurant_Menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class WPR_License {
    
    private static $license_server = 'https://license-server.stb-srv.de';
    private static $cache_duration = 86400; // 24 hours
    
    /**
     * License Types Configuration
     */
    private static $license_types = array(
        'free' => array(
            'max_items' => 20,
            'features' => array(),
        ),
        'free_plus' => array(
            'max_items' => 60,
            'features' => array(),
        ),
        'pro' => array(
            'max_items' => 200,
            'features' => array(),
        ),
        'pro_plus' => array(
            'max_items' => 200,
            'features' => array('dark_mode', 'cart'),
        ),
        'ultimate' => array(
            'max_items' => 999999,
            'features' => array('dark_mode', 'cart', 'unlimited'),
        ),
    );
    
    /**
     * Get current license data
     */
    public static function get_license_data() {
        $license_data = get_option('wpr_license_data', array());
        
        // Default to FREE if no license
        if (empty($license_data)) {
            return array(
                'valid' => true,
                'type' => 'free',
                'max_items' => 20,
                'features' => array(),
                'expires' => 'lifetime',
            );
        }
        
        return $license_data;
    }
    
    /**
     * Validate license key format
     */
    public static function validate_key_format($key) {
        // Format: WPR-XXXXX-XXXXX-XXXXX or WPR-XXXXX-XXXXX-XXXXX-XXXXX
        $pattern = '/^WPR-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}(-[A-Z0-9]{5})?$/';
        return preg_match($pattern, strtoupper($key));
    }
    
    /**
     * Activate license
     */
    public static function activate($license_key) {
        // Validate format
        if (!self::validate_key_format($license_key)) {
            return array(
                'success' => false,
                'message' => __('Ungültiges Lizenzschlüssel-Format.', 'wp-restaurant-menu')
            );
        }
        
        // Check with server
        $domain = self::get_domain();
        $response = wp_remote_get(
            self::$license_server . '/api.php?action=check_license&key=' . urlencode($license_key) . '&domain=' . urlencode($domain),
            array('timeout' => 15)
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => __('Verbindung zum Lizenz-Server fehlgeschlagen.', 'wp-restaurant-menu')
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$body || !isset($body['valid']) || !$body['valid']) {
            return array(
                'success' => false,
                'message' => __('Lizenzschlüssel ungültig oder nicht für diese Domain.', 'wp-restaurant-menu')
            );
        }
        
        // Save license data
        update_option('wpr_license_key', strtoupper($license_key));
        update_option('wpr_license_data', array(
            'valid' => true,
            'type' => $body['type'],
            'max_items' => $body['max_items'],
            'features' => $body['features'] ?? array(),
            'expires' => $body['expires'] ?? 'lifetime',
        ));
        update_option('wpr_license_last_check', time());
        
        return array(
            'success' => true,
            'message' => __('Lizenz erfolgreich aktiviert!', 'wp-restaurant-menu'),
            'data' => $body
        );
    }
    
    /**
     * Deactivate license
     */
    public static function deactivate() {
        delete_option('wpr_license_key');
        delete_option('wpr_license_data');
        delete_option('wpr_license_last_check');
        
        return array(
            'success' => true,
            'message' => __('Lizenz deaktiviert.', 'wp-restaurant-menu')
        );
    }
    
    /**
     * Check if license needs refresh
     */
    public static function needs_refresh() {
        $last_check = get_option('wpr_license_last_check', 0);
        return (time() - $last_check) > self::$cache_duration;
    }
    
    /**
     * Refresh license data
     */
    public static function refresh() {
        $license_key = get_option('wpr_license_key');
        
        if (!$license_key) {
            return false;
        }
        
        return self::activate($license_key);
    }
    
    /**
     * Get current item count
     */
    public static function get_item_count() {
        return wp_count_posts('wpr_menu_item')->publish;
    }
    
    /**
     * Check if can add more items
     */
    public static function can_add_item() {
        $license_data = self::get_license_data();
        $current_count = self::get_item_count();
        
        return $current_count < $license_data['max_items'];
    }
    
    /**
     * Get items remaining
     */
    public static function get_items_remaining() {
        $license_data = self::get_license_data();
        $current_count = self::get_item_count();
        
        return max(0, $license_data['max_items'] - $current_count);
    }
    
    /**
     * Check if feature is available
     */
    public static function has_feature($feature) {
        $license_data = self::get_license_data();
        return in_array($feature, $license_data['features']);
    }
    
    /**
     * Quick feature checks
     */
    public static function has_dark_mode() {
        return self::has_feature('dark_mode');
    }
    
    public static function has_cart() {
        return self::has_feature('cart');
    }
    
    public static function has_unlimited_items() {
        return self::has_feature('unlimited');
    }
    
    /**
     * Get license type display name
     */
    public static function get_type_display_name($type = null) {
        if ($type === null) {
            $license_data = self::get_license_data();
            $type = $license_data['type'];
        }
        
        $names = array(
            'free' => 'FREE',
            'free_plus' => 'FREE+',
            'pro' => 'PRO',
            'pro_plus' => 'PRO+',
            'ultimate' => 'ULTIMATE',
        );
        
        return $names[$type] ?? strtoupper($type);
    }
    
    /**
     * Get pricing from server
     */
    public static function get_pricing() {
        $transient_key = 'wpr_pricing_data';
        $cached = get_transient($transient_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get(
            self::$license_server . '/api.php?action=get_pricing',
            array('timeout' => 10)
        );
        
        if (is_wp_error($response)) {
            return self::get_default_pricing();
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$body || !isset($body['pricing'])) {
            return self::get_default_pricing();
        }
        
        set_transient($transient_key, $body['pricing'], 3600); // Cache for 1 hour
        
        return $body['pricing'];
    }
    
    /**
     * Get default pricing (fallback)
     */
    private static function get_default_pricing() {
        return array(
            'free' => array(
                'price' => 0,
                'currency' => '€',
                'label' => 'FREE',
                'description' => 'Perfekt zum Testen und für kleine Restaurants',
                'max_items' => 20,
                'features' => array(),
            ),
            'free_plus' => array(
                'price' => 15,
                'currency' => '€',
                'label' => 'FREE+',
                'description' => 'Erweiterte Kapazität für mittelgroße Menüs',
                'max_items' => 60,
                'features' => array(),
            ),
            'pro' => array(
                'price' => 29,
                'currency' => '€',
                'label' => 'PRO',
                'description' => 'Professionelle Lösung für umfangreiche Speisekarten',
                'max_items' => 200,
                'features' => array(),
            ),
            'pro_plus' => array(
                'price' => 49,
                'currency' => '€',
                'label' => 'PRO+',
                'description' => 'PRO + Dark Mode + Warenkorb-System',
                'max_items' => 200,
                'features' => array('dark_mode', 'cart'),
            ),
            'ultimate' => array(
                'price' => 79,
                'currency' => '€',
                'label' => 'ULTIMATE',
                'description' => 'Alle Features + unbegrenzte Gerichte',
                'max_items' => 999999,
                'features' => array('dark_mode', 'cart', 'unlimited'),
            ),
        );
    }
    
    /**
     * Test server connection
     */
    public static function test_server() {
        $response = wp_remote_get(
            self::$license_server . '/api.php?action=status',
            array('timeout' => 10)
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!$body || $body['status'] !== 'online') {
            return array(
                'success' => false,
                'message' => __('Server nicht erreichbar', 'wp-restaurant-menu')
            );
        }
        
        return array(
            'success' => true,
            'message' => __('Server online', 'wp-restaurant-menu'),
            'version' => $body['version'] ?? 'unknown'
        );
    }
    
    /**
     * Get current domain
     */
    private static function get_domain() {
        return parse_url(home_url(), PHP_URL_HOST);
    }
    
    /**
     * Get license server URL
     */
    public static function get_server_url() {
        return self::$license_server;
    }
}