<?php
/*
Plugin Name: Fix External Link
Plugin URI: https://example.com
Description: A plugin to modify external links, set follow/nofollow attributes, control implementation on posts or pages, and enable redirection (301, 302).
Version: 2.2
Author: Your Name
Author URI: https://example.com
License: GPL2
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Activation hook to create database table
register_activation_hook(__FILE__, 'fix_external_link_create_table');
function fix_external_link_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'external_links';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        keyword varchar(255) NOT NULL,
        url text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Default options
    add_option('fix_external_link_attribute', 'nofollow');
    add_option('fix_external_link_apply_to', 'both');
    add_option('fix_external_link_redirect_type', 'none');
    add_option('fix_external_link_open_type', '_self');
}

// Add menus in WordPress admin
add_action('admin_menu', 'fix_external_link_menus');
function fix_external_link_menus() {
    add_menu_page(
        'Fix External Link', 
        'Fix External Link', 
        'manage_options', 
        'fix-external-link', 
        'fix_external_link_settings_page', 
        'dashicons-admin-links', 
        100
    );

    add_submenu_page(
        'fix-external-link', 
        'List Links', 
        'List Links', 
        'manage_options', 
        'fix-external-link-list', 
        'fix_external_link_list_page'
    );
}

// Settings page
function fix_external_link_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['fix_external_link_save_settings'])) {
        update_option('fix_external_link_attribute', sanitize_text_field($_POST['link_attribute']));
        update_option('fix_external_link_apply_to', sanitize_text_field($_POST['apply_to']));
        update_option('fix_external_link_redirect_type', sanitize_text_field($_POST['redirect_type']));
        update_option('fix_external_link_open_type', sanitize_text_field($_POST['open_type']));
        update_option('fix_external_link_force', sanitize_text_field($_POST['force_check']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $link_attribute = get_option('fix_external_link_attribute', 'nofollow');
    $apply_to = get_option('fix_external_link_apply_to', 'both');
    $redirect_type = get_option('fix_external_link_redirect_type', 'none');
    $open_type = get_option('fix_external_link_open_type', '_self');
    $force_check = get_option('fix_external_link_force', 'no');

    ?>
    <div class="wrap">
        <h1>Fix External Link Settings</h1>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="link_attribute">Link Attribute</label>
                    </th>
                    <td>
                        <select name="link_attribute" id="link_attribute">
                            <option value="nofollow" <?php selected($link_attribute, 'nofollow'); ?>>Nofollow</option>
                            <option value="follow" <?php selected($link_attribute, 'follow'); ?>>Follow</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="apply_to">Apply To</label>
                    </th>
                    <td>
                        <select name="apply_to" id="apply_to">
                            <option value="posts" <?php selected($apply_to, 'posts'); ?>>Posts</option>
                            <option value="pages" <?php selected($apply_to, 'pages'); ?>>Pages</option>
                            <option value="both" <?php selected($apply_to, 'both'); ?>>Both</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="redirect_type">Redirect Type</label>
                    </th>
                    <td>
                        <select name="redirect_type" id="redirect_type">
                            <option value="none" <?php selected($redirect_type, 'none'); ?>>None</option>
                            <option value="301" <?php selected($redirect_type, '301'); ?>>301 Permanent</option>
                            <option value="302" <?php selected($redirect_type, '302'); ?>>302 Temporary</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="open_type">Open Link In</label>
                    </th>
                    <td>
                        <select name="open_type" id="open_type">
                            <option value="_self" <?php selected($open_type, '_self'); ?>>Same Tab</option>
                            <option value="_blank" <?php selected($open_type, '_blank'); ?>>New Tab</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="force_check">Force External Link</label>
                    </th>
                    <td>
                        <select name="force_check" id="force_check">
                            <option value="no" <?php selected($force_check, 'no'); ?>>No</option>
                            <option value="yes" <?php selected($force_check, 'yes'); ?>>Yes</option>
                        </select>
                        <p class="description">Bila diaktifkan, semua tautan eksternal akan dimodifikasi tanpa mempedulikan keywords. Pengaturan ini mengesampingkan logika pencocokan keywords.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="fix_external_link_save_settings" class="button-primary" value="Save Settings">
            </p>
        </form>
    </div>
    <?php
}

// List Links page
function fix_external_link_list_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'external_links';

    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['fix_external_link_save'])) {
        $keyword = sanitize_text_field($_POST['keyword']);
        $url = esc_url_raw($_POST['url']);

        if (!empty($keyword) && !empty($url)) {
            $wpdb->insert(
                $table_name,
                ['keyword' => $keyword, 'url' => $url]
            );
            echo '<div class="updated"><p>Link saved.</p></div>';
        }
    }

    if (isset($_POST['fix_external_link_delete'])) {
        $id = intval($_POST['link_id']);
        $wpdb->delete($table_name, ['id' => $id]);
        echo '<div class="updated"><p>Link deleted.</p></div>';
    }

    if (isset($_POST['fix_external_link_import'])) {
        if (!empty($_FILES['import_file']['tmp_name'])) {
            $json_data = file_get_contents($_FILES['import_file']['tmp_name']);
            $links = json_decode($json_data, true);

            if ($links) {
                foreach ($links as $link) {
                    $wpdb->insert(
                        $table_name,
                        [
                            'keyword' => sanitize_text_field($link['keyword']),
                            'url' => esc_url_raw($link['url'])
                        ]
                    );
                }
                echo '<div class="updated"><p>Links imported successfully.</p></div>';
            } else {
                echo '<div class="error"><p>Invalid JSON file.</p></div>';
            }
        }
    }

    if (isset($_POST['fix_external_link_export'])) {
        $links = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="external-links.json"');
        echo json_encode($links);
        exit;
    }

    $links = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h1>Manage External Links</h1>
        <form method="POST" enctype="multipart/form-data">
            <h2>Add New Link</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="keyword">Keyword</label>
                    </th>
                    <td>
                        <input type="text" name="keyword" id="keyword" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="url">URL</label>
                    </th>
                    <td>
                        <input type="text" name="url" id="url" class="regular-text">
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="fix_external_link_save" class="button-primary" value="Save Link">
                <input type="file" name="import_file" accept="application/json">
                <input type="submit" name="fix_external_link_import" class="button-secondary" value="Import Links JSON">
                <input type="submit" name="fix_external_link_export" class="button-secondary" value="Export Links JSON">
            </p>
        </form>

        <h2>Existing Links</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Keyword</th>
                    <th>URL</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link) : ?>
                    <tr>
                        <td><?php echo esc_html($link->id); ?></td>
                        <td><?php echo esc_html($link->keyword); ?></td>
                        <td><?php echo esc_url($link->url); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="link_id" value="<?php echo intval($link->id); ?>">
                                <input type="submit" name="fix_external_link_delete" class="button button-secondary" value="Delete">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Modify external links in content
add_filter('the_content', 'fix_external_links_in_content');
function fix_external_links_in_content($content) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'external_links';

    if (!is_singular()) {
        return $content; // Do not modify if not on a single post or page
    }

    $links = $wpdb->get_results("SELECT * FROM $table_name");
    $link_attribute = get_option('fix_external_link_attribute', 'nofollow') === 'nofollow' ? ' rel="nofollow noopener"' : ' rel="noopener"';
    $apply_to = get_option('fix_external_link_apply_to', 'both');
    $redirect_type = get_option('fix_external_link_redirect_type', 'none');
    $open_type = get_option('fix_external_link_open_type', '_self');
    $force_check = get_option('fix_external_link_force', 'no');

    if (
        ($apply_to === 'posts' && is_singular('post')) ||
        ($apply_to === 'pages' && is_singular('page')) ||
        ($apply_to === 'both')
    ) {
        $content = preg_replace_callback(
            '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/i',
            function ($matches) use ($links, $link_attribute, $redirect_type, $open_type, $force_check) {
                // $anchor_text = strtolower(strip_tags($matches[2]));
                // foreach ($links as $link) {
                //     if (strpos($anchor_text, strtolower($link->keyword)) !== false) {
                //         $redirect = ($redirect_type !== 'none') ? ' redirect-' . $redirect_type : '';
                //         return '<a href="' . esc_url($link->url) . '"' . $link_attribute . ' target="' . esc_attr($open_type) . '" class="' . esc_attr($redirect) . '">' . $matches[2] . '</a>';
                //     }
                // }
                $redirect = ($redirect_type !== 'none') ? ' redirect-' . $redirect_type : '';

                if ($force_check === 'yes') {
                    // Force all external links to use the specified options
                    return '<a href="' . esc_url($matches[1]) . '"' . $link_attribute . ' target="' . esc_attr($open_type) . '" class="' . esc_attr($redirect) . '">' . $matches[2] . '</a>';
                } else {
                    $anchor_text = strtolower(strip_tags($matches[2]));
                    foreach ($links as $link) {
                        if (strpos($anchor_text, strtolower($link->keyword)) !== false) {
                            return '<a href="' . esc_url($link->url) . '"' . $link_attribute . ' target="' . esc_attr($open_type) . '" class="' . esc_attr($redirect) . '">' . $matches[2] . '</a>';
                        }
                    }
                }
                return $matches[0]; // No match, return original link
            },
            $content
        );
    }

    return $content;
}
