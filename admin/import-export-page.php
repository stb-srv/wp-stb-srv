<?php
/**
 * Import/Export Page Template
 * 
 * @package WP_Restaurant_Menu
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Handle Export
if (isset($_POST['wpr_export']) && check_admin_referer('wpr_import_export_action', 'wpr_import_export_nonce')) {
    WPR_Import_Export::download();
}

// Handle Import
if (isset($_POST['wpr_import']) && check_admin_referer('wpr_import_export_action', 'wpr_import_export_nonce')) {
    if (isset($_FILES['wpr_import_file']) && $_FILES['wpr_import_file']['error'] === UPLOAD_ERR_OK) {
        $file_content = file_get_contents($_FILES['wpr_import_file']['tmp_name']);
        $overwrite = isset($_POST['wpr_overwrite']);
        
        $result = WPR_Import_Export::import($file_content, $overwrite);
        
        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . $result['message'] . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Bitte wählen Sie eine JSON-Datei aus.', 'wp-restaurant-menu') . '</p></div>';
    }
}

$item_count = wp_count_posts('wpr_menu_item')->publish;
?>

<div class="wrap wpr-import-export-page">
    <h1><?php _e('Import / Export', 'wp-restaurant-menu'); ?></h1>
    
    <!-- Export Section -->
    <div class="wpr-export-section">
        <h2><?php _e('Menü exportieren', 'wp-restaurant-menu'); ?></h2>
        
        <p><?php _e('Exportieren Sie alle Ihre Menüpunkte als JSON-Datei. Diese Datei enthält alle Gerichte mit Metadaten, Kategorien und Taxonomien.', 'wp-restaurant-menu'); ?></p>
        
        <div class="wpr-export-info">
            <p><strong><?php _e('Aktuelle Statistiken:', 'wp-restaurant-menu'); ?></strong></p>
            <ul>
                <li><?php printf(__('%d Gerichte', 'wp-restaurant-menu'), $item_count); ?></li>
                <li><?php printf(__('%d Kategorien', 'wp-restaurant-menu'), wp_count_terms('wpr_category')); ?></li>
                <li><?php printf(__('%d Menükarten', 'wp-restaurant-menu'), wp_count_terms('wpr_menu_list')); ?></li>
            </ul>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('wpr_import_export_action', 'wpr_import_export_nonce'); ?>
            <button type="submit" name="wpr_export" class="button button-primary button-large">
                ⬇️ <?php _e('Menü jetzt exportieren', 'wp-restaurant-menu'); ?>
            </button>
        </form>
    </div>
    
    <hr style="margin: 40px 0;">
    
    <!-- Import Section -->
    <div class="wpr-import-section">
        <h2><?php _e('Menü importieren', 'wp-restaurant-menu'); ?></h2>
        
        <div class="wpr-import-warning">
            <p><strong>⚠️ <?php _e('Wichtig:', 'wp-restaurant-menu'); ?></strong></p>
            <ul>
                <li><?php _e('Importieren Sie nur JSON-Dateien, die mit diesem Plugin exportiert wurden.', 'wp-restaurant-menu'); ?></li>
                <li><?php _e('Bestehende Gerichte mit gleichem Titel werden nur bei aktivierter "Überschreiben"-Option aktualisiert.', 'wp-restaurant-menu'); ?></li>
                <li><?php _e('Kategorien und Menükarten werden automatisch erstellt, falls sie nicht existieren.', 'wp-restaurant-menu'); ?></li>
                <li><?php _e('Erstellen Sie vor dem Import ein Backup Ihrer bestehenden Daten!', 'wp-restaurant-menu'); ?></li>
            </ul>
        </div>
        
        <form method="post" action="" enctype="multipart/form-data">
            <?php wp_nonce_field('wpr_import_export_action', 'wpr_import_export_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="wpr_import_file"><?php _e('JSON-Datei', 'wp-restaurant-menu'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="wpr_import_file" id="wpr_import_file" accept=".json" required>
                            <p class="description"><?php _e('Wählen Sie die zu importierende JSON-Datei aus.', 'wp-restaurant-menu'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wpr_overwrite"><?php _e('Optionen', 'wp-restaurant-menu'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpr_overwrite" id="wpr_overwrite" value="1">
                                <?php _e('Bestehende Gerichte überschreiben (bei gleichem Titel)', 'wp-restaurant-menu'); ?>
                            </label>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <p class="submit">
                <button type="submit" name="wpr_import" class="button button-primary button-large">
                    ⬆️ <?php _e('Menü jetzt importieren', 'wp-restaurant-menu'); ?>
                </button>
            </p>
        </form>
    </div>
    
    <!-- Format Documentation -->
    <div class="wpr-format-doc">
        <h3><?php _e('JSON-Format', 'wp-restaurant-menu'); ?></h3>
        <pre><code>{
  "version": "1.7.2",
  "exported_at": "2024-12-19T10:00:00Z",
  "items": [
    {
      "title": "Pizza Margherita",
      "content": "Tomaten, Mozzarella, Basilikum",
      "dish_number": "12",
      "price": "8.50",
      "vegan": false,
      "vegetarian": true,
      "allergens": ["a", "g"],
      "categories": ["hauptgerichte"],
      "menu_lists": ["mittag"],
      "image_url": "https://example.com/image.jpg"
    }
  ]
}</code></pre>
    </div>
</div>

<style>
.wpr-import-export-page {
    max-width: 900px;
}

.wpr-export-section,
.wpr-import-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.wpr-export-info {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 15px;
    margin: 20px 0;
}

.wpr-export-info ul,
.wpr-import-warning ul {
    margin: 10px 0 0 20px;
}

.wpr-import-warning {
    background: #fcf9e8;
    border-left: 4px solid #dba617;
    padding: 15px;
    margin: 20px 0;
}

.wpr-format-doc {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.wpr-format-doc pre {
    background: #f6f7f7;
    padding: 15px;
    border-radius: 4px;
    overflow-x: auto;
}

.wpr-format-doc code {
    font-family: 'Monaco', 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.6;
}

.button-large {
    height: auto !important;
    padding: 12px 24px !important;
    font-size: 16px !important;
}
</style>