<?php
/**
 * Plugin Name: Generic sameAs Schema
 * Plugin URI: https://github.com/thomasgerdes/generic-sameas-schema
 * Description: Adds customizable sameAs Schema.org markup for social media profiles and professional links
 * Version: 1.0.0
 * Author: Thomas Gerdes
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: generic-sameas-schema
 * GitHub Plugin URI: thomasgerdes/generic-sameas-schema
 * Primary Branch: main
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add sameAs Schema markup to all pages
 */
function generic_sameas_schema() {
    // Get plugin options
    $options = get_option('generic_sameas_options', array());
    
    // Default values if options don't exist
    $defaults = array(
        'entity_type' => 'Person',
        'entity_name' => get_bloginfo('name'),
        'entity_url' => home_url(),
        'social_profiles' => array()
    );
    
    $options = wp_parse_args($options, $defaults);
    
    // Only output if we have social profiles
    if (empty($options['social_profiles']) || !is_array($options['social_profiles'])) {
        return;
    }
    
    // Filter out empty URLs
    $social_profiles = array_filter($options['social_profiles'], function($url) {
        return !empty(trim($url));
    });
    
    if (empty($social_profiles)) {
        return;
    }
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => $options['entity_type'],
        'name' => $options['entity_name'],
        'url' => $options['entity_url'],
        'sameAs' => array_values($social_profiles)
    );
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

// Hook the function to wp_head
add_action('wp_head', 'generic_sameas_schema');

/**
 * Add admin menu
 */
function generic_sameas_admin_menu() {
    add_options_page(
        __('sameAs Schema Settings', 'generic-sameas-schema'),
        __('sameAs Schema', 'generic-sameas-schema'),
        'manage_options',
        'generic-sameas-schema',
        'generic_sameas_settings_page'
    );
}
add_action('admin_menu', 'generic_sameas_admin_menu');

/**
 * Settings page
 */
function generic_sameas_settings_page() {
    if (isset($_POST['submit'])) {
        check_admin_referer('generic_sameas_save_settings');
        
        $options = array(
            'entity_type' => sanitize_text_field($_POST['entity_type']),
            'entity_name' => sanitize_text_field($_POST['entity_name']),
            'entity_url' => esc_url_raw($_POST['entity_url']),
            'social_profiles' => array()
        );
        
        // Process social profile URLs
        if (isset($_POST['social_profiles']) && is_array($_POST['social_profiles'])) {
            foreach ($_POST['social_profiles'] as $url) {
                $clean_url = esc_url_raw(trim($url));
                if (!empty($clean_url)) {
                    $options['social_profiles'][] = $clean_url;
                }
            }
        }
        
        update_option('generic_sameas_options', $options);
        echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'generic-sameas-schema') . '</p></div>';
    }
    
    $options = get_option('generic_sameas_options', array());
    $defaults = array(
        'entity_type' => 'Person',
        'entity_name' => get_bloginfo('name'),
        'entity_url' => home_url(),
        'social_profiles' => array()
    );
    $options = wp_parse_args($options, $defaults);
    
    ?>
    <div class="wrap">
        <h1><?php _e('sameAs Schema Settings', 'generic-sameas-schema'); ?></h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('generic_sameas_save_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Entity Type', 'generic-sameas-schema'); ?></th>
                    <td>
                        <select name="entity_type">
                            <option value="Person" <?php selected($options['entity_type'], 'Person'); ?>><?php _e('Person', 'generic-sameas-schema'); ?></option>
                            <option value="Organization" <?php selected($options['entity_type'], 'Organization'); ?>><?php _e('Organization', 'generic-sameas-schema'); ?></option>
                        </select>
                        <p class="description"><?php _e('Choose whether this represents a person or organization.', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Name', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="text" name="entity_name" value="<?php echo esc_attr($options['entity_name']); ?>" class="regular-text" />
                        <p class="description"><?php _e('The name of the person or organization.', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Website URL', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="url" name="entity_url" value="<?php echo esc_attr($options['entity_url']); ?>" class="regular-text" />
                        <p class="description"><?php _e('The main website URL.', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Social Profiles & Links', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="social-profiles">
                            <?php
                            $profiles = !empty($options['social_profiles']) ? $options['social_profiles'] : array('');
                            foreach ($profiles as $index => $url) {
                                echo '<div class="profile-row">';
                                echo '<input type="url" name="social_profiles[]" value="' . esc_attr($url) . '" class="regular-text" placeholder="https://example.com/profile" />';
                                echo '<button type="button" class="button remove-profile" style="margin-left: 10px;">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" id="add-profile" class="button"><?php _e('Add Another Profile', 'generic-sameas-schema'); ?></button>
                        <p class="description">
                            <?php _e('Add URLs to your social media profiles, academic profiles, or other professional links. Examples:', 'generic-sameas-schema'); ?>
                            <br>• https://scholar.google.com/citations?user=...
                            <br>• https://orcid.org/0000-0000-0000-0000
                            <br>• https://linkedin.com/in/username
                            <br>• https://twitter.com/username
                            <br>• https://github.com/username
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    
    <script>
    document.getElementById('add-profile').addEventListener('click', function() {
        var container = document.getElementById('social-profiles');
        var newRow = document.createElement('div');
        newRow.className = 'profile-row';
        newRow.innerHTML = '<input type="url" name="social_profiles[]" value="" class="regular-text" placeholder="https://example.com/profile" />' +
                          '<button type="button" class="button remove-profile" style="margin-left: 10px;">Remove</button>';
        container.appendChild(newRow);
    });
    
    // Event delegation for remove buttons (works for dynamically added elements)
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-profile')) {
            var profileRows = document.querySelectorAll('.profile-row');
            // Don't remove if it's the last row
            if (profileRows.length > 1) {
                e.target.parentNode.remove();
            } else {
                // Clear the input instead of removing the row
                e.target.parentNode.querySelector('input').value = '';
            }
        }
    });
    </script>
    
    <style>
    .profile-row {
        margin-bottom: 10px;
    }
    .profile-row input[type="url"] {
        margin-bottom: 0;
    }
    </style>
    <?php
}

/**
 * Plugin activation hook
 */
function generic_sameas_activate() {
    // Set default options on activation
    $default_options = array(
        'entity_type' => 'Person',
        'entity_name' => get_bloginfo('name'),
        'entity_url' => home_url(),
        'social_profiles' => array()
    );
    
    if (!get_option('generic_sameas_options')) {
        add_option('generic_sameas_options', $default_options);
    }
}
register_activation_hook(__FILE__, 'generic_sameas_activate');

/**
 * Plugin deactivation hook
 */
function generic_sameas_deactivate() {
    // Keep settings on deactivation, only clean up on uninstall
}
register_deactivation_hook(__FILE__, 'generic_sameas_deactivate');

/**
 * Plugin uninstall hook
 */
function generic_sameas_uninstall() {
    delete_option('generic_sameas_options');
}
register_uninstall_hook(__FILE__, 'generic_sameas_uninstall');
?>