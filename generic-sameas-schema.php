<?php
/**
 * Plugin Name: Generic sameAs Schema
 * Plugin URI: https://github.com/thomasgerdes/generic-sameas-schema
 * Description: Adds customizable sameAs Schema.org markup for social media profiles and professional links with job details
 * Version: 1.3.0
 * Author: Thomas Gerdes
 * Author URI: https://thomasgerdes.de
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: generic-sameas-schema
 * GitHub Plugin URI: thomasgerdes/generic-sameas-schema
 * Primary Branch: main
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main function to output Schema.org JSON-LD markup
 */
function generic_sameas_schema() {
    $options = get_option('generic_sameas_options', array());
    
    $defaults = array(
        'entity_type' => 'Person',
        'entity_name' => get_bloginfo('name'),
        'entity_url' => home_url(),
        'alternate_names' => array(),
        'job_title' => '',
        'company_name' => '',
        'company_url' => '',
        'knows_about' => array(),
        'alumni_of' => array(),
        'social_profiles' => array(),
        'honorific_prefix' => '',
        'knows_languages' => array(),
        'work_locations' => array(),
        'image_url' => ''
    );
    
    $options = wp_parse_args($options, $defaults);
    
    if (empty($options['social_profiles']) || !is_array($options['social_profiles'])) {
        return;
    }
    
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
    
    if (!empty($options['honorific_prefix'])) {
        $schema['honorificPrefix'] = $options['honorific_prefix'];
    }
    
    if (!empty($options['image_url'])) {
        $schema['image'] = $options['image_url'];
    }
    
    if (!empty($options['alternate_names']) && is_array($options['alternate_names'])) {
        $alternate_names = array_filter($options['alternate_names'], function($name) {
            return !empty(trim($name));
        });
        if (!empty($alternate_names)) {
            if (count($alternate_names) === 1) {
                $schema['alternateName'] = $alternate_names[0];
            } else {
                $schema['alternateName'] = array_values($alternate_names);
            }
        }
    }
    
    if (!empty($options['job_title'])) {
        $schema['jobTitle'] = $options['job_title'];
    }
    
    if (!empty($options['work_locations']) && is_array($options['work_locations'])) {
        $work_locations = array_filter($options['work_locations'], function($location) {
            return !empty(trim($location));
        });
        if (!empty($work_locations)) {
            if (count($work_locations) === 1) {
                $schema['workLocation'] = $work_locations[0];
            } else {
                $schema['workLocation'] = array_values($work_locations);
            }
        }
    }
    
    if (!empty($options['company_name'])) {
        $works_for = array(
            '@type' => 'Organization',
            'name' => $options['company_name']
        );
        
        if (!empty($options['company_url'])) {
            $works_for['url'] = $options['company_url'];
        }
        
        $schema['worksFor'] = $works_for;
    }
    
    if (!empty($options['knows_about']) && is_array($options['knows_about'])) {
        $knows_about = array_filter($options['knows_about'], function($item) {
            return !empty(trim($item));
        });
        if (!empty($knows_about)) {
            $schema['knowsAbout'] = array_values($knows_about);
        }
    }
    
    if (!empty($options['knows_languages']) && is_array($options['knows_languages'])) {
        $knows_languages = array_filter($options['knows_languages'], function($lang) {
            return !empty(trim($lang));
        });
        if (!empty($knows_languages)) {
            $schema['knowsLanguage'] = array_values($knows_languages);
        }
    }
    
    if (!empty($options['alumni_of']) && is_array($options['alumni_of'])) {
        $alumni_of = array_filter($options['alumni_of'], function($item) {
            return !empty(trim($item));
        });
        if (!empty($alumni_of)) {
            if (count($alumni_of) === 1) {
                $schema['alumniOf'] = $alumni_of[0];
            } else {
                $schema['alumniOf'] = array_values($alumni_of);
            }
        }
    }
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

add_action('wp_head', 'generic_sameas_schema');

/**
 * Add admin menu item
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
            'honorific_prefix' => sanitize_text_field($_POST['honorific_prefix']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'job_title' => sanitize_text_field($_POST['job_title']),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'company_url' => esc_url_raw($_POST['company_url']),
            'alternate_names' => array(),
            'knows_about' => array(),
            'knows_languages' => array(),
            'work_locations' => array(),
            'alumni_of' => array(),
            'social_profiles' => array()
        );
        
        if (isset($_POST['alternate_names']) && is_array($_POST['alternate_names'])) {
            foreach ($_POST['alternate_names'] as $name) {
                $clean_name = sanitize_text_field(trim($name));
                if (!empty($clean_name)) {
                    $options['alternate_names'][] = $clean_name;
                }
            }
        }
        
        if (isset($_POST['knows_about']) && is_array($_POST['knows_about'])) {
            foreach ($_POST['knows_about'] as $item) {
                $clean_item = sanitize_text_field(trim($item));
                if (!empty($clean_item)) {
                    $options['knows_about'][] = $clean_item;
                }
            }
        }
        
        if (isset($_POST['knows_languages']) && is_array($_POST['knows_languages'])) {
            foreach ($_POST['knows_languages'] as $lang) {
                $clean_lang = sanitize_text_field(trim($lang));
                if (!empty($clean_lang)) {
                    $options['knows_languages'][] = $clean_lang;
                }
            }
        }
        
        if (isset($_POST['work_locations']) && is_array($_POST['work_locations'])) {
            foreach ($_POST['work_locations'] as $location) {
                $clean_location = sanitize_text_field(trim($location));
                if (!empty($clean_location)) {
                    $options['work_locations'][] = $clean_location;
                }
            }
        }
        
        if (isset($_POST['alumni_of']) && is_array($_POST['alumni_of'])) {
            foreach ($_POST['alumni_of'] as $item) {
                $clean_item = sanitize_text_field(trim($item));
                if (!empty($clean_item)) {
                    $options['alumni_of'][] = $clean_item;
                }
            }
        }
        
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
        'honorific_prefix' => '',
        'image_url' => '',
        'job_title' => '',
        'company_name' => '',
        'company_url' => '',
        'alternate_names' => array(),
        'knows_about' => array(),
        'knows_languages' => array(),
        'work_locations' => array(),
        'alumni_of' => array(),
        'social_profiles' => array()
    );
    $options = wp_parse_args($options, $defaults);
    ?>
    <div class="wrap">
        <h1><?php _e('sameAs Schema Settings', 'generic-sameas-schema'); ?></h1>
        <p><?php _e('Configure Schema.org markup for your professional and social media profiles.', 'generic-sameas-schema'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('generic_sameas_save_settings'); ?>
            
            <h2><?php _e('Basic Information', 'generic-sameas-schema'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Entity Type', 'generic-sameas-schema'); ?></th>
                    <td>
                        <select name="entity_type">
                            <option value="Person" <?php selected($options['entity_type'], 'Person'); ?>><?php _e('Person', 'generic-sameas-schema'); ?></option>
                            <option value="Organization" <?php selected($options['entity_type'], 'Organization'); ?>><?php _e('Organization', 'generic-sameas-schema'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Honorific Prefix', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="text" name="honorific_prefix" value="<?php echo esc_attr($options['honorific_prefix']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Title like "Dr.", "Prof.", "Mr.", "Ms.", etc.', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Name', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="text" name="entity_name" value="<?php echo esc_attr($options['entity_name']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Image URL', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="url" name="image_url" value="<?php echo esc_attr($options['image_url']); ?>" class="regular-text" />
                        <button type="button" id="upload-image-button" class="button"><?php _e('Upload Image', 'generic-sameas-schema'); ?></button>
                        <p class="description"><?php _e('Professional photo or logo.', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Website URL', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="url" name="entity_url" value="<?php echo esc_attr($options['entity_url']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Alternative Names', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="alternate-names">
                            <?php
                            $alt_names = !empty($options['alternate_names']) ? $options['alternate_names'] : array('');
                            foreach ($alt_names as $name) {
                                echo '<div class="field-row">';
                                echo '<input type="text" name="alternate_names[]" value="' . esc_attr($name) . '" class="regular-text" />';
                                echo '<button type="button" class="button remove-field">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" class="add-field" data-target="alternate-names" data-name="alternate_names[]"><?php _e('Add Name', 'generic-sameas-schema'); ?></button>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Languages', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="knows-languages">
                            <?php
                            $languages = !empty($options['knows_languages']) ? $options['knows_languages'] : array('');
                            foreach ($languages as $lang) {
                                echo '<div class="field-row">';
                                echo '<input type="text" name="knows_languages[]" value="' . esc_attr($lang) . '" class="regular-text" />';
                                echo '<button type="button" class="button remove-field">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" class="add-field" data-target="knows-languages" data-name="knows_languages[]"><?php _e('Add Language', 'generic-sameas-schema'); ?></button>
                    </td>
                </tr>
            </table>
            
            <h2><?php _e('Professional Information', 'generic-sameas-schema'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Job Title', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="text" name="job_title" value="<?php echo esc_attr($options['job_title']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Work Locations', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="work-locations">
                            <?php
                            $locations = !empty($options['work_locations']) ? $options['work_locations'] : array('');
                            foreach ($locations as $location) {
                                echo '<div class="field-row">';
                                echo '<input type="text" name="work_locations[]" value="' . esc_attr($location) . '" class="regular-text" />';
                                echo '<button type="button" class="button remove-field">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" class="add-field" data-target="work-locations" data-name="work_locations[]"><?php _e('Add Location', 'generic-sameas-schema'); ?></button>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Company', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="text" name="company_name" value="<?php echo esc_attr($options['company_name']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Company Website', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="url" name="company_url" value="<?php echo esc_attr($options['company_url']); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Areas of Expertise', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="knows-about">
                            <?php
                            $expertise = !empty($options['knows_about']) ? $options['knows_about'] : array('');
                            foreach ($expertise as $item) {
                                echo '<div class="field-row">';
                                echo '<input type="text" name="knows_about[]" value="' . esc_attr($item) . '" class="regular-text" />';
                                echo '<button type="button" class="button remove-field">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" class="add-field" data-target="knows-about" data-name="knows_about[]"><?php _e('Add Expertise', 'generic-sameas-schema'); ?></button>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Education', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="alumni-of">
                            <?php
                            $alumni = !empty($options['alumni_of']) ? $options['alumni_of'] : array('');
                            foreach ($alumni as $item) {
                                echo '<div class="field-row">';
                                echo '<input type="text" name="alumni_of[]" value="' . esc_attr($item) . '" class="regular-text" />';
                                echo '<button type="button" class="button remove-field">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" class="add-field" data-target="alumni-of" data-name="alumni_of[]"><?php _e('Add Institution', 'generic-sameas-schema'); ?></button>
                    </td>
                </tr>
            </table>
            
            <h2><?php _e('Social Profiles', 'generic-sameas-schema'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Profile URLs', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="social-profiles">
                            <?php
                            $profiles = !empty($options['social_profiles']) ? $options['social_profiles'] : array('');
                            foreach ($profiles as $url) {
                                echo '<div class="field-row">';
                                echo '<input type="url" name="social_profiles[]" value="' . esc_attr($url) . '" class="regular-text" />';
                                echo '<button type="button" class="button remove-field">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" class="add-field" data-target="social-profiles" data-name="social_profiles[]"><?php _e('Add Profile', 'generic-sameas-schema'); ?></button>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <h2><?php _e('Schema Preview', 'generic-sameas-schema'); ?></h2>
        <div style="background: #f1f1f1; padding: 15px; border-radius: 4px;">
            <pre id="schema-preview" style="background: white; padding: 10px; border-radius: 4px; font-size: 12px; max-height: 400px; overflow: auto;"></pre>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function updatePreview() {
            const entityType = document.querySelector('select[name="entity_type"]').value;
            const entityName = document.querySelector('input[name="entity_name"]').value;
            const entityUrl = document.querySelector('input[name="entity_url"]').value;
            const honorificPrefix = document.querySelector('input[name="honorific_prefix"]').value;
            const imageUrl = document.querySelector('input[name="image_url"]').value;
            const jobTitle = document.querySelector('input[name="job_title"]').value;
            const companyName = document.querySelector('input[name="company_name"]').value;
            const companyUrl = document.querySelector('input[name="company_url"]').value;
            
            const alternateNames = Array.from(document.querySelectorAll('input[name="alternate_names[]"]'))
                .map(input => input.value.trim()).filter(val => val !== '');
            const knowsAbout = Array.from(document.querySelectorAll('input[name="knows_about[]"]'))
                .map(input => input.value.trim()).filter(val => val !== '');
            const knowsLanguages = Array.from(document.querySelectorAll('input[name="knows_languages[]"]'))
                .map(input => input.value.trim()).filter(val => val !== '');
            const workLocations = Array.from(document.querySelectorAll('input[name="work_locations[]"]'))
                .map(input => input.value.trim()).filter(val => val !== '');
            const alumniOf = Array.from(document.querySelectorAll('input[name="alumni_of[]"]'))
                .map(input => input.value.trim()).filter(val => val !== '');
            const sameAs = Array.from(document.querySelectorAll('input[name="social_profiles[]"]'))
                .map(input => input.value.trim()).filter(val => val !== '');
            
            let schema = {
                "@context": "https://schema.org",
                "@type": entityType,
                "name": entityName,
                "url": entityUrl
            };
            
            if (honorificPrefix) schema.honorificPrefix = honorificPrefix;
            if (imageUrl) schema.image = imageUrl;
            if (alternateNames.length > 0) {
                schema.alternateName = alternateNames.length === 1 ? alternateNames[0] : alternateNames;
            }
            if (jobTitle) schema.jobTitle = jobTitle;
            if (workLocations.length > 0) {
                schema.workLocation = workLocations.length === 1 ? workLocations[0] : workLocations;
            }
            if (companyName) {
                schema.worksFor = {"@type": "Organization", "name": companyName};
                if (companyUrl) schema.worksFor.url = companyUrl;
            }
            if (knowsAbout.length > 0) schema.knowsAbout = knowsAbout;
            if (knowsLanguages.length > 0) schema.knowsLanguage = knowsLanguages;
            if (alumniOf.length > 0) {
                schema.alumniOf = alumniOf.length === 1 ? alumniOf[0] : alumniOf;
            }
            if (sameAs.length > 0) schema.sameAs = sameAs;
            
            document.getElementById('schema-preview').textContent = JSON.stringify(schema, null, 2);
        }
        
        document.addEventListener('input', updatePreview);
        document.addEventListener('change', updatePreview);
        updatePreview();
        
        // Add field functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-field')) {
                const target = e.target.getAttribute('data-target');
                const name = e.target.getAttribute('data-name');
                const container = document.getElementById(target);
                const inputType = name.includes('social_profiles') || name.includes('entity_url') || name.includes('company_url') || name.includes('image_url') ? 'url' : 'text';
                
                const newRow = document.createElement('div');
                newRow.className = 'field-row';
                newRow.innerHTML = '<input type="' + inputType + '" name="' + name + '" value="" class="regular-text" />' +
                                  '<button type="button" class="button remove-field">Remove</button>';
                container.appendChild(newRow);
                updatePreview();
            }
            
            if (e.target.classList.contains('remove-field')) {
                const fieldRows = e.target.closest('div').parentNode.querySelectorAll('.field-row');
                if (fieldRows.length > 1) {
                    e.target.closest('.field-row').remove();
                } else {
                    e.target.closest('.field-row').querySelector('input').value = '';
                }
                updatePreview();
            }
        });
        
        // Media uploader
        if (document.getElementById('upload-image-button')) {
            document.getElementById('upload-image-button').addEventListener('click', function(e) {
                e.preventDefault();
                
                if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                    alert('WordPress Media Library not available.');
                    return;
                }
                
                const mediaUploader = wp.media({
                    title: 'Select Profile Image',
                    button: {text: 'Use this image'},
                    multiple: false,
                    library: {type: 'image'}
                });
                
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    document.querySelector('input[name="image_url"]').value = attachment.url;
                    updatePreview();
                });
                
                mediaUploader.open();
            });
        }
    });
    </script>
    
    <style>
    .field-row {
        margin-bottom: 10px;
    }
    .field-row input {
        margin-right: 10px;
        margin-bottom: 0;
    }
    .add-field {
        margin-top: 5px;
    }
    h2 {
        border-bottom: 1px solid #ccc;
        padding-bottom: 10px;
        margin-top: 30px;
    }
    #schema-preview {
        font-family: monospace;
        white-space: pre-wrap;
    }
    </style>
    <?php
}

/**
 * Enqueue admin scripts
 */
function generic_sameas_admin_scripts($hook) {
    if ($hook !== 'settings_page_generic-sameas-schema') {
        return;
    }
    
    wp_enqueue_media();
    wp_enqueue_script('jquery');
}
add_action('admin_enqueue_scripts', 'generic_sameas_admin_scripts');

/**
 * Plugin activation hook
 */
function generic_sameas_activate() {
    $default_options = array(
        'entity_type' => 'Person',
        'entity_name' => get_bloginfo('name'),
        'entity_url' => home_url(),
        'honorific_prefix' => '',
        'image_url' => '',
        'job_title' => '',
        'company_name' => '',
        'company_url' => '',
        'alternate_names' => array(),
        'knows_about' => array(),
        'knows_languages' => array(),
        'work_locations' => array(),
        'alumni_of' => array(),
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
    // Settings preserved on deactivation
}
register_deactivation_hook(__FILE__, 'generic_sameas_deactivate');

/**
 * Plugin uninstall hook
 */
function generic_sameas_uninstall() {
    delete_option('generic_sameas_options');
}
register_uninstall_hook(__FILE__, 'generic_sameas_uninstall');
