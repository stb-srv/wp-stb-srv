<?php
/**
 * Plugin Uninstall Handler
 * 
 * @package WP_Restaurant_Menu
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die('Direct access not permitted.');
}

// Delete all menu items
$menu_items = get_posts(array(
    'post_type' => 'wpr_menu_item',
    'posts_per_page' => -1,
    'post_status' => 'any',
));

foreach ($menu_items as $item) {
    wp_delete_post($item->ID, true);
}

// Delete all categories
$categories = get_terms(array(
    'taxonomy' => 'wpr_category',
    'hide_empty' => false,
));

foreach ($categories as $category) {
    wp_delete_term($category->term_id, 'wpr_category');
}

// Delete all menu lists
$menu_lists = get_terms(array(
    'taxonomy' => 'wpr_menu_list',
    'hide_empty' => false,
));

foreach ($menu_lists as $menu_list) {
    wp_delete_term($menu_list->term_id, 'wpr_menu_list');
}

// Delete options
delete_option('wpr_settings');
delete_option('wpr_license_key');
delete_option('wpr_license_data');
delete_option('wpr_license_last_check');

// Drop custom tables if any (for future use)
global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wpr_orders");

// Clear any cached data
wp_cache_flush();