<?php
/**
 * Import/Export Class
 * 
 * @package WP_Restaurant_Menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

class WPR_Import_Export {
    
    /**
     * Export all menu items to JSON
     */
    public static function export() {
        $args = array(
            'post_type' => 'wpr_menu_item',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        );
        
        $query = new WP_Query($args);
        $items = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get categories
                $categories = wp_get_post_terms($post_id, 'wpr_category', array('fields' => 'slugs'));
                
                // Get menu lists
                $menu_lists = wp_get_post_terms($post_id, 'wpr_menu_list', array('fields' => 'slugs'));
                
                // Get meta data
                $dish_number = get_post_meta($post_id, '_wpr_dish_number', true);
                $price = get_post_meta($post_id, '_wpr_price', true);
                $vegan = get_post_meta($post_id, '_wpr_vegan', true);
                $vegetarian = get_post_meta($post_id, '_wpr_vegetarian', true);
                $allergens = get_post_meta($post_id, '_wpr_allergens', true);
                
                // Get featured image
                $image_id = get_post_thumbnail_id($post_id);
                $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
                
                $items[] = array(
                    'title' => get_the_title(),
                    'content' => get_the_content(),
                    'dish_number' => $dish_number,
                    'price' => $price,
                    'vegan' => (bool) $vegan,
                    'vegetarian' => (bool) $vegetarian,
                    'allergens' => is_array($allergens) ? $allergens : array(),
                    'categories' => is_array($categories) ? $categories : array(),
                    'menu_lists' => is_array($menu_lists) ? $menu_lists : array(),
                    'image_url' => $image_url,
                    'menu_order' => get_post_field('menu_order', $post_id),
                );
            }
            wp_reset_postdata();
        }
        
        $export_data = array(
            'version' => WP_RESTAURANT_MENU_VERSION,
            'exported_at' => current_time('c'),
            'site_url' => home_url(),
            'items' => $items,
        );
        
        return $export_data;
    }
    
    /**
     * Import menu items from JSON
     */
    public static function import($json_data, $overwrite = false) {
        $data = json_decode($json_data, true);
        
        if (!$data || !isset($data['items'])) {
            return array(
                'success' => false,
                'message' => __('Ungültiges JSON-Format', 'wp-restaurant-menu')
            );
        }
        
        $imported = 0;
        $skipped = 0;
        $errors = array();
        
        foreach ($data['items'] as $item) {
            try {
                // Check if item exists by title
                $existing = get_page_by_title($item['title'], OBJECT, 'wpr_menu_item');
                
                if ($existing && !$overwrite) {
                    $skipped++;
                    continue;
                }
                
                // Create or update post
                $post_data = array(
                    'post_title' => $item['title'],
                    'post_content' => $item['content'] ?? '',
                    'post_type' => 'wpr_menu_item',
                    'post_status' => 'publish',
                    'menu_order' => $item['menu_order'] ?? 0,
                );
                
                if ($existing && $overwrite) {
                    $post_data['ID'] = $existing->ID;
                    $post_id = wp_update_post($post_data);
                } else {
                    $post_id = wp_insert_post($post_data);
                }
                
                if (is_wp_error($post_id)) {
                    $errors[] = sprintf(__('Fehler bei %s: %s', 'wp-restaurant-menu'), $item['title'], $post_id->get_error_message());
                    continue;
                }
                
                // Save meta data
                if (isset($item['dish_number'])) {
                    update_post_meta($post_id, '_wpr_dish_number', sanitize_text_field($item['dish_number']));
                }
                
                if (isset($item['price'])) {
                    update_post_meta($post_id, '_wpr_price', sanitize_text_field($item['price']));
                }
                
                update_post_meta($post_id, '_wpr_vegan', isset($item['vegan']) ? (int) $item['vegan'] : 0);
                update_post_meta($post_id, '_wpr_vegetarian', isset($item['vegetarian']) ? (int) $item['vegetarian'] : 0);
                
                if (isset($item['allergens']) && is_array($item['allergens'])) {
                    $allergens = array_map('sanitize_text_field', $item['allergens']);
                    update_post_meta($post_id, '_wpr_allergens', $allergens);
                }
                
                // Assign categories
                if (isset($item['categories']) && is_array($item['categories'])) {
                    $category_ids = array();
                    foreach ($item['categories'] as $cat_slug) {
                        $term = get_term_by('slug', $cat_slug, 'wpr_category');
                        if (!$term) {
                            // Create category if doesn't exist
                            $term = wp_insert_term($cat_slug, 'wpr_category');
                            if (!is_wp_error($term)) {
                                $category_ids[] = $term['term_id'];
                            }
                        } else {
                            $category_ids[] = $term->term_id;
                        }
                    }
                    wp_set_post_terms($post_id, $category_ids, 'wpr_category');
                }
                
                // Assign menu lists
                if (isset($item['menu_lists']) && is_array($item['menu_lists'])) {
                    $menu_list_ids = array();
                    foreach ($item['menu_lists'] as $list_slug) {
                        $term = get_term_by('slug', $list_slug, 'wpr_menu_list');
                        if (!$term) {
                            // Create menu list if doesn't exist
                            $term = wp_insert_term($list_slug, 'wpr_menu_list');
                            if (!is_wp_error($term)) {
                                $menu_list_ids[] = $term['term_id'];
                            }
                        } else {
                            $menu_list_ids[] = $term->term_id;
                        }
                    }
                    wp_set_post_terms($post_id, $menu_list_ids, 'wpr_menu_list');
                }
                
                // Handle image (optional - download from URL)
                if (!empty($item['image_url']) && filter_var($item['image_url'], FILTER_VALIDATE_URL)) {
                    self::import_image($post_id, $item['image_url']);
                }
                
                $imported++;
                
            } catch (Exception $e) {
                $errors[] = sprintf(__('Fehler bei %s: %s', 'wp-restaurant-menu'), $item['title'], $e->getMessage());
            }
        }
        
        $message = sprintf(
            __('%d Gerichte importiert, %d übersprungen', 'wp-restaurant-menu'),
            $imported,
            $skipped
        );
        
        if (!empty($errors)) {
            $message .= '<br>' . implode('<br>', $errors);
        }
        
        return array(
            'success' => true,
            'message' => $message,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        );
    }
    
    /**
     * Import image from URL
     */
    private static function import_image($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download image
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        // Get file info
        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }
        
        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);
        
        return true;
    }
    
    /**
     * Download export file
     */
    public static function download() {
        $export_data = self::export();
        $json = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="wp-restaurant-menu-export-' . date('Y-m-d') . '.json"');
        header('Content-Length: ' . strlen($json));
        
        echo $json;
        exit;
    }
}