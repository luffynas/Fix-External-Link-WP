<?php
/*
Plugin Name: Fix External Link
Plugin URI: https://cekmedia.com
Description: A plugin to modify external links, set follow/nofollow attributes, control implementation on posts or pages, and enable redirection (301, 302).
Version: 1.4
Author: @luffynas
Author URI: https://cekmedia.com
License: GPL2
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Add menu in WordPress admin
add_action('admin_menu', 'fix_external_link_menu');
function fix_external_link_menu() {
    add_menu_page(
        'Fix External Link Settings', 
        'Fix External Link', 
        'manage_options', 
        'fix-external-link', 
        'fix_external_link_settings_page', 
        'dashicons-admin-links', 
        100
    );
}

// Settings page
function fix_external_link_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['fix_external_link_save'])) {
        update_option('fix_external_link_target_url', sanitize_text_field($_POST['target_url']));
        update_option('fix_external_link_attribute', sanitize_text_field($_POST['link_attribute']));
        update_option('fix_external_link_apply_to', sanitize_text_field($_POST['apply_to']));
        update_option('fix_external_link_redirect_type', sanitize_text_field($_POST['redirect_type']));
        update_option('fix_external_link_open_type', sanitize_text_field($_POST['open_type']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $target_url = get_option('fix_external_link_target_url', 'https://example.com');
    $link_attribute = get_option('fix_external_link_attribute', 'nofollow');
    $apply_to = get_option('fix_external_link_apply_to', 'both');
    $redirect_type = get_option('fix_external_link_redirect_type', 'none');
    $open_type = get_option('fix_external_link_open_type', '_self');

    ?>
    <div class="wrap">
        <h1>Fix External Link Settings</h1>
        <form method="POST">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="target_url">Target URL</label>
                    </th>
                    <td>
                        <input type="text" name="target_url" id="target_url" value="<?php echo esc_attr($target_url); ?>" class="regular-text">
                    </td>
                </tr>
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
            </table>
            <p class="submit">
                <input type="submit" name="fix_external_link_save" class="button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}

// Modify external links in content
add_filter('the_content', 'fix_external_links_in_content');
function fix_external_links_in_content($content) {
    if (!is_singular()) {
        return $content; // Do not modify if not on a single post or page
    }

    $target_url = get_option('fix_external_link_target_url', 'https://example.com');
    $link_attribute = get_option('fix_external_link_attribute', 'nofollow') === 'nofollow' ? ' rel="nofollow"' : '';
    $apply_to = get_option('fix_external_link_apply_to', 'both');
    $open_type = get_option('fix_external_link_open_type', '_self');

    if (
        ($apply_to === 'posts' && is_singular('post')) ||
        ($apply_to === 'pages' && is_singular('page')) ||
        ($apply_to === 'both')
    ) {
        $content = preg_replace_callback(
            '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>/i',
            function ($matches) use ($target_url, $link_attribute, $open_type) {
                if (strpos($matches[1], home_url()) === false) {
                    return '<a href="' . esc_url($target_url) . '"' . $link_attribute . ' target="' . esc_attr($open_type) . '">';
                }
                return $matches[0];
            },
            $content
        );
    }

    return $content;
}
