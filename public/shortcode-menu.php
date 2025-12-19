<?php
/**
 * Shortcode Handler for Menu Display
 * 
 * @package WP_Restaurant_Menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

function wpr_render_menu($atts) {
    // Query args
    $args = array(
        'post_type' => 'wpr_menu_item',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC',
    );
    
    // Filter by category
    if (!empty($atts['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'wpr_category',
                'field' => 'slug',
                'terms' => $atts['category'],
            ),
        );
    }
    
    // Filter by menu list
    if (!empty($atts['menu'])) {
        if (!isset($args['tax_query'])) {
            $args['tax_query'] = array();
        }
        $args['tax_query'][] = array(
            'taxonomy' => 'wpr_menu_list',
            'field' => 'slug',
            'terms' => $atts['menu'],
        );
    }
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<p class="wpr-no-items">' . __('Keine Menüpunkte gefunden.', 'wp-restaurant-menu') . '</p>';
    }
    
    // Get settings
    $settings = get_option('wpr_settings', array());
    $currency = $settings['currency_symbol'] ?? '€';
    $currency_position = $settings['currency_position'] ?? 'after';
    
    // Group by category
    $items_by_category = array();
    
    if ($atts['group_by_category'] === 'yes') {
        while ($query->have_posts()) {
            $query->the_post();
            $categories = wp_get_post_terms(get_the_ID(), 'wpr_category');
            
            if (empty($categories)) {
                $items_by_category['uncategorized'][] = get_the_ID();
            } else {
                foreach ($categories as $category) {
                    $items_by_category[$category->slug][] = get_the_ID();
                }
            }
        }
        wp_reset_postdata();
    } else {
        // All items in one group
        while ($query->have_posts()) {
            $query->the_post();
            $items_by_category['all'][] = get_the_ID();
        }
        wp_reset_postdata();
    }
    
    // Start output
    ob_start();
    ?>
    
    <div class="wpr-menu-container" data-columns="<?php echo esc_attr($atts['columns']); ?>">
        
        <?php if ($atts['show_search'] === 'yes'): ?>
        <div class="wpr-search-container">
            <input type="text" 
                class="wpr-menu-search" 
                placeholder="<?php _e('Menü durchsuchen...', 'wp-restaurant-menu'); ?>">
        </div>
        <?php endif; ?>
        
        <?php foreach ($items_by_category as $category_slug => $item_ids): ?>
            <?php
            // Get category info
            if ($category_slug === 'uncategorized') {
                $category_name = __('Sonstiges', 'wp-restaurant-menu');
            } elseif ($category_slug === 'all') {
                $category_name = '';
            } else {
                $category = get_term_by('slug', $category_slug, 'wpr_category');
                $category_name = $category ? $category->name : $category_slug;
            }
            ?>
            
            <div class="wpr-category-section" data-category="<?php echo esc_attr($category_slug); ?>">
                
                <?php if ($atts['group_by_category'] === 'yes' && !empty($category_name)): ?>
                <button class="wpr-category-header" aria-expanded="true">
                    <span class="wpr-category-title"><?php echo esc_html($category_name); ?></span>
                    <span class="wpr-category-count"><?php echo count($item_ids); ?></span>
                    <span class="wpr-category-arrow">▼</span>
                </button>
                <?php endif; ?>
                
                <div class="wpr-category-items">
                    <div class="wpr-menu-grid wpr-columns-<?php echo esc_attr($atts['columns']); ?>">
                        
                        <?php foreach ($item_ids as $item_id): ?>
                            <?php
                            $dish_number = get_post_meta($item_id, '_wpr_dish_number', true);
                            $price = get_post_meta($item_id, '_wpr_price', true);
                            $vegan = get_post_meta($item_id, '_wpr_vegan', true);
                            $vegetarian = get_post_meta($item_id, '_wpr_vegetarian', true);
                            $allergens = get_post_meta($item_id, '_wpr_allergens', true);
                            
                            $title = get_the_title($item_id);
                            $content = get_post_field('post_content', $item_id);
                            $has_image = has_post_thumbnail($item_id);
                            
                            // Price formatting
                            $formatted_price = '';
                            if (!empty($price)) {
                                if ($currency_position === 'before') {
                                    $formatted_price = $currency . ' ' . $price;
                                } else {
                                    $formatted_price = $price . ' ' . $currency;
                                }
                            }
                            ?>
                            
                            <article class="wpr-menu-item wpr-image-<?php echo esc_attr($atts['image_position']); ?>"
                                data-search-title="<?php echo esc_attr(strtolower($title)); ?>"
                                data-search-content="<?php echo esc_attr(strtolower(wp_strip_all_tags($content))); ?>">
                                
                                <?php if ($atts['show_images'] === 'yes' && $has_image): ?>
                                <div class="wpr-menu-item-image">
                                    <?php echo get_the_post_thumbnail($item_id, 'medium'); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="wpr-menu-item-content">
                                    <div class="wpr-menu-item-header">
                                        <h3 class="wpr-menu-item-title">
                                            <?php if (!empty($dish_number)): ?>
                                                <span class="wpr-dish-number"><?php echo esc_html($dish_number); ?>.</span>
                                            <?php endif; ?>
                                            <?php echo esc_html($title); ?>
                                        </h3>
                                        
                                        <?php if (!empty($formatted_price)): ?>
                                        <span class="wpr-menu-item-price"><?php echo esc_html($formatted_price); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($content)): ?>
                                    <div class="wpr-menu-item-description">
                                        <?php echo wp_kses_post(wpautop($content)); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="wpr-menu-item-meta">
                                        <?php if ($vegan): ?>
                                            <span class="wpr-badge wpr-badge-vegan" title="<?php _e('Vegan', 'wp-restaurant-menu'); ?>">🌿</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($vegetarian): ?>
                                            <span class="wpr-badge wpr-badge-vegetarian" title="<?php _e('Vegetarisch', 'wp-restaurant-menu'); ?>">🥗</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($allergens) && is_array($allergens)): ?>
                                        <span class="wpr-allergens">
                                            <?php foreach ($allergens as $allergen): ?>
                                                <span class="wpr-allergen-icon" 
                                                    title="<?php echo esc_attr(wpr_get_allergen_name($allergen)); ?>">
                                                    <?php echo esc_html(strtoupper($allergen)); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (WPR_License::has_cart()): ?>
                                    <button class="wpr-add-to-cart" data-item-id="<?php echo esc_attr($item_id); ?>">
                                        🛒 <?php _e('In den Warenkorb', 'wp-restaurant-menu'); ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </article>
                            
                        <?php endforeach; ?>
                        
                    </div>
                </div>
            </div>
            
        <?php endforeach; ?>
        
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * Get allergen name by code
 */
function wpr_get_allergen_name($code) {
    $allergens = array(
        'a' => 'A - Glutenhaltiges Getreide',
        'b' => 'B - Krebstiere',
        'c' => 'C - Eier',
        'd' => 'D - Fisch',
        'e' => 'E - Erdnüsse',
        'f' => 'F - Soja',
        'g' => 'G - Milch/Laktose',
        'h' => 'H - Schalenfrüchte',
        'l' => 'L - Sellerie',
        'm' => 'M - Senf',
        'n' => 'N - Sesamsamen',
        'o' => 'O - Schwefeldioxid',
        'p' => 'P - Lupinen',
        'r' => 'R - Weichtiere',
    );
    
    return $allergens[$code] ?? strtoupper($code);
}