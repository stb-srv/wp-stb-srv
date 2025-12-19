<?php
/**
 * Plugin Name: WP Restaurant Menu
 * Plugin URI: https://github.com/stb-srv/wp-restaurant-menu
 * Description: Modernes WordPress-Plugin zur Verwaltung von Restaurant-Speisekarten mit Lizenz-Server
 * Version: 1.7.2
 * Author: STB-SRV
 * Author URI: https://stb-srv.de
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-restaurant-menu
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Plugin Constants
define('WP_RESTAURANT_MENU_VERSION', '1.7.2');
define('WP_RESTAURANT_MENU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_RESTAURANT_MENU_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_RESTAURANT_MENU_PLUGIN_FILE', __FILE__);

// Load Dependencies
require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wpr-license.php';
require_once WP_RESTAURANT_MENU_PLUGIN_DIR . 'includes/class-wpr-import-export.php';

/**
 * Main Plugin Class
 */
class WP_Restaurant_Menu {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // WordPress Hooks
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_wpr_menu_item', array($this, 'save_meta_boxes'), 10, 2);
        
        // Shortcode
        add_shortcode('restaurant_menu', array($this, 'shortcode_handler'));
    }
    
    /**
     * Plugin Activation
     */
    public function activate() {
        $this->register_post_type();
        $this->register_taxonomies();
        flush_rewrite_rules();
        
        // Set default options
        if (!get_option('wpr_settings')) {
            update_option('wpr_settings', array(
                'currency_symbol' => '€',
                'currency_position' => 'after',
                'show_images' => 'yes',
                'image_position' => 'left',
                'show_search' => 'yes',
                'group_by_category' => 'yes',
                'columns' => 2,
                'dark_mode_enabled' => 'no',
                'dark_mode_scope' => 'menu',
                'dark_mode_method' => 'manual',
                'dark_mode_position' => 'bottom-right'
            ));
        }
    }
    
    /**
     * Plugin Deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Register Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Menüpunkte', 'wp-restaurant-menu'),
            'singular_name' => __('Menüpunkt', 'wp-restaurant-menu'),
            'menu_name' => __('Restaurant Menü', 'wp-restaurant-menu'),
            'add_new' => __('Neues Gericht', 'wp-restaurant-menu'),
            'add_new_item' => __('Neues Gericht hinzufügen', 'wp-restaurant-menu'),
            'edit_item' => __('Gericht bearbeiten', 'wp-restaurant-menu'),
            'new_item' => __('Neues Gericht', 'wp-restaurant-menu'),
            'view_item' => __('Gericht ansehen', 'wp-restaurant-menu'),
            'search_items' => __('Gerichte suchen', 'wp-restaurant-menu'),
            'not_found' => __('Keine Gerichte gefunden', 'wp-restaurant-menu'),
            'not_found_in_trash' => __('Keine Gerichte im Papierkorb', 'wp-restaurant-menu'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => false,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-food',
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true,
        );
        
        register_post_type('wpr_menu_item', $args);
    }
    
    /**
     * Register Taxonomies
     */
    public function register_taxonomies() {
        // Categories (hierarchical)
        register_taxonomy('wpr_category', 'wpr_menu_item', array(
            'labels' => array(
                'name' => __('Kategorien', 'wp-restaurant-menu'),
                'singular_name' => __('Kategorie', 'wp-restaurant-menu'),
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
        ));
        
        // Menu Lists (non-hierarchical)
        register_taxonomy('wpr_menu_list', 'wpr_menu_item', array(
            'labels' => array(
                'name' => __('Menükarten', 'wp-restaurant-menu'),
                'singular_name' => __('Menükarte', 'wp-restaurant-menu'),
            ),
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => false,
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Add Admin Menu
     */
    public function admin_menu() {
        // Settings Page
        add_submenu_page(
            'edit.php?post_type=wpr_menu_item',
            __('Einstellungen', 'wp-restaurant-menu'),
            __('⚙️ Einstellungen', 'wp-restaurant-menu'),
            'manage_options',
            'wpr-settings',
            array($this, 'settings_page')
        );
        
        // License Page
        add_submenu_page(
            'edit.php?post_type=wpr_menu_item',
            __('Lizenz', 'wp-restaurant-menu'),
            __('🔑 Lizenz', 'wp-restaurant-menu'),
            'manage_options',
            'wpr-license',
            array($this, 'license_page')
        );
        
        // Import/Export Page
        add_submenu_page(
            'edit.php?post_type=wpr_menu_item',
            __('Import / Export', 'wp-restaurant-menu'),
            __('📊 Import / Export', 'wp-restaurant-menu'),
            'manage_options',
            'wpr-import-export',
            array($this, 'import_export_page')
        );
    }
    
    /**
     * Load Admin Scripts
     */
    public function admin_scripts($hook) {
        if (strpos($hook, 'wpr') !== false || get_post_type() === 'wpr_menu_item') {
            wp_enqueue_style('wpr-admin', WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/admin-styles.css', array(), WP_RESTAURANT_MENU_VERSION);
            wp_enqueue_script('wpr-admin', WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/admin-scripts.js', array('jquery'), WP_RESTAURANT_MENU_VERSION, true);
        }
    }
    
    /**
     * Load Frontend Scripts
     */
    public function frontend_scripts() {
        wp_enqueue_style('wpr-menu', WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/menu-styles.css', array(), WP_RESTAURANT_MENU_VERSION);
        
        $settings = get_option('wpr_settings', array());
        
        // Dark Mode
        if (isset($settings['dark_mode_enabled']) && $settings['dark_mode_enabled'] === 'yes' && WPR_License::has_dark_mode()) {
            wp_enqueue_style('wpr-dark-mode', WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/dark-mode.css', array('wpr-menu'), WP_RESTAURANT_MENU_VERSION);
            wp_enqueue_script('wpr-dark-mode', WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/dark-mode.js', array(), WP_RESTAURANT_MENU_VERSION, true);
            wp_localize_script('wpr-dark-mode', 'wprDarkMode', array(
                'scope' => $settings['dark_mode_scope'] ?? 'menu',
                'method' => $settings['dark_mode_method'] ?? 'manual',
                'position' => $settings['dark_mode_position'] ?? 'bottom-right'
            ));
        }
        
        // Search
        if (isset($settings['show_search']) && $settings['show_search'] === 'yes') {
            wp_enqueue_script('wpr-search', WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/menu-search.js', array(), WP_RESTAURANT_MENU_VERSION, true);
        }
        
        // Accordion
        if (isset($settings['group_by_category']) && $settings['group_by_category'] === 'yes') {
            wp_enqueue_script('wpr-accordion', WP_RESTAURANT_MENU_PLUGIN_URL . 'assets/menu-accordion.js', array(), WP_RESTAURANT_MENU_VERSION, true);
        }
    }
    
    /**
     * Add Meta Boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wpr_menu_details',
            __('Gericht Details', 'wp-restaurant-menu'),
            array($this, 'render_menu_details_meta_box'),
            'wpr_menu_item',
            'normal',
            'high'
        );
        
        add_meta_box(
            'wpr_menu_allergens',
            __('Allergene & Ernährung', 'wp-restaurant-menu'),
            array($this, 'render_allergens_meta_box'),
            'wpr_menu_item',
            'side',
            'default'
        );
    }
    
    /**
     * Render Menu Details Meta Box
     */
    public function render_menu_details_meta_box($post) {
        wp_nonce_field('wpr_menu_details_nonce', 'wpr_menu_details_nonce');
        
        $dish_number = get_post_meta($post->ID, '_wpr_dish_number', true);
        $price = get_post_meta($post->ID, '_wpr_price', true);
        
        include WP_RESTAURANT_MENU_PLUGIN_DIR . 'admin/meta-box-details.php';
    }
    
    /**
     * Render Allergens Meta Box
     */
    public function render_allergens_meta_box($post) {
        wp_nonce_field('wpr_allergens_nonce', 'wpr_allergens_nonce');
        
        $vegan = get_post_meta($post->ID, '_wpr_vegan', true);
        $vegetarian = get_post_meta($post->ID, '_wpr_vegetarian', true);
        $allergens = get_post_meta($post->ID, '_wpr_allergens', true);
        
        include WP_RESTAURANT_MENU_PLUGIN_DIR . 'admin/meta-box-allergens.php';
    }
    
    /**
     * Save Meta Boxes
     */
    public function save_meta_boxes($post_id, $post) {
        // Check nonces
        if (!isset($_POST['wpr_menu_details_nonce']) || !wp_verify_nonce($_POST['wpr_menu_details_nonce'], 'wpr_menu_details_nonce')) {
            return;
        }
        
        if (!isset($_POST['wpr_allergens_nonce']) || !wp_verify_nonce($_POST['wpr_allergens_nonce'], 'wpr_allergens_nonce')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save dish number
        if (isset($_POST['wpr_dish_number'])) {
            update_post_meta($post_id, '_wpr_dish_number', sanitize_text_field($_POST['wpr_dish_number']));
        }
        
        // Save price
        if (isset($_POST['wpr_price'])) {
            update_post_meta($post_id, '_wpr_price', sanitize_text_field($_POST['wpr_price']));
        }
        
        // Save vegan/vegetarian
        update_post_meta($post_id, '_wpr_vegan', isset($_POST['wpr_vegan']) ? 1 : 0);
        update_post_meta($post_id, '_wpr_vegetarian', isset($_POST['wpr_vegetarian']) ? 1 : 0);
        
        // Save allergens
        if (isset($_POST['wpr_allergens']) && is_array($_POST['wpr_allergens'])) {
            $allergens = array_map('sanitize_text_field', $_POST['wpr_allergens']);
            update_post_meta($post_id, '_wpr_allergens', $allergens);
        } else {
            delete_post_meta($post_id, '_wpr_allergens');
        }
    }
    
    /**
     * Settings Page
     */
    public function settings_page() {
        include WP_RESTAURANT_MENU_PLUGIN_DIR . 'admin/settings-page.php';
    }
    
    /**
     * License Page
     */
    public function license_page() {
        include WP_RESTAURANT_MENU_PLUGIN_DIR . 'admin/license-page.php';
    }
    
    /**
     * Import/Export Page
     */
    public function import_export_page() {
        include WP_RESTAURANT_MENU_PLUGIN_DIR . 'admin/import-export-page.php';
    }
    
    /**
     * Shortcode Handler
     */
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'menu' => '',
            'columns' => get_option('wpr_settings')['columns'] ?? 2,
            'show_search' => get_option('wpr_settings')['show_search'] ?? 'yes',
            'show_images' => get_option('wpr_settings')['show_images'] ?? 'yes',
            'image_position' => get_option('wpr_settings')['image_position'] ?? 'left',
            'group_by_category' => get_option('wpr_settings')['group_by_category'] ?? 'yes',
        ), $atts, 'restaurant_menu');
        
        include WP_RESTAURANT_MENU_PLUGIN_DIR . 'public/shortcode-menu.php';
        
        return wpr_render_menu($atts);
    }
}

// Initialize Plugin
WP_Restaurant_Menu::get_instance();