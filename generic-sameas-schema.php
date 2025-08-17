<?php
/**
 * Plugin Name: Generic sameAs Schema
 * Plugin URI: https://github.com/thomasgerdes/generic-sameas-schema
 * Description: Adds customizable sameAs Schema.org markup for social media profiles and professional links with job details
 * Version: 1.2.0
 * Author: Thomas Gerdes
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
 * 
 * Generates Person/Organization schema with professional details,
 * alternative names, education, expertise, and social media profiles.
 * Designed to complement existing SEO plugins like Yoast.
 */
function generic_sameas_schema() {
    // Retrieve plugin options from WordPress database
    $options = get_option('generic_sameas_options', array());
    
    // Set default values for all schema fields
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
        'social_profiles' => array()
    );
    
    // Merge user options with defaults
    $options = wp_parse_args($options, $defaults);
    
    // Exit early if no social profiles are configured
    if (empty($options['social_profiles']) || !is_array($options['social_profiles'])) {
        return;
    }
    
    // Filter out empty profile URLs
    $social_profiles = array_filter($options['social_profiles'], function($url) {
        return !empty(trim($url));
    });
    
    // Exit if no valid social profiles remain
    if (empty($social_profiles)) {
        return;
    }
    
    // Build basic schema structure
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => $options['entity_type'],
        'name' => $options['entity_name'],
        'url' => $options['entity_url'],
        'sameAs' => array_values($social_profiles)
    );
    
    // Add alternative names if provided
    if (!empty($options['alternate_names']) && is_array($options['alternate_names'])) {
        $alternate_names = array_filter($options['alternate_names'], function($name) {
            return !empty(trim($name));
        });
        if (!empty($alternate_names)) {
            // Use single string if only one alternate name, array if multiple
            if (count($alternate_names) === 1) {
                $schema['alternateName'] = $alternate_names[0];
            } else {
                $schema['alternateName'] = array_values($alternate_names);
            }
        }
    }
    
    // Add job title if provided
    if (!empty($options['job_title'])) {
        $schema['jobTitle'] = $options['job_title'];
    }
    
    // Add employer/organization information
    if (!empty($options['company_name'])) {
        $works_for = array(
            '@type' => 'Organization',
            'name' => $options['company_name']
        );
        
        // Include company URL if provided
        if (!empty($options['company_url'])) {
            $works_for['url'] = $options['company_url'];
        }
        
        $schema['worksFor'] = $works_for;
    }
    
    // Add areas of expertise/knowledge
    if (!empty($options['knows_about']) && is_array($options['knows_about'])) {
        $knows_about = array_filter($options['knows_about'], function($item) {
            return !empty(trim($item));
        });
        if (!empty($knows_about)) {
            $schema['knowsAbout'] = array_values($knows_about);
        }
    }
    
    // Add educational background
    if (!empty($options['alumni_of']) && is_array($options['alumni_of'])) {
        $alumni_of = array_filter($options['alumni_of'], function($item) {
            return !empty(trim($item));
        });
        if (!empty($alumni_of)) {
            // Use single string if only one institution, array if multiple
            if (count($alumni_of) === 1) {
                $schema['alumniOf'] = $alumni_of[0];
            } else {
                $schema['alumniOf'] = array_values($alumni_of);
            }
        }
    }
    
    // Output the JSON-LD script tag
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

// Hook the schema output function to wp_head
add_action('wp_head', 'generic_sameas_schema');

/**
 * Add admin menu item to WordPress settings
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
 * Render the admin settings page
 * 
 * Provides interface for configuring all schema fields including:
 * - Basic entity information (name, type, URL)
 * - Alternative names
 * - Professional details (job, company, expertise)
 * - Educational background
 * - Social media and professional profiles
 * - Live preview of generated schema
 */
function generic_sameas_settings_page() {
    // Handle form submission
    if (isset($_POST['submit'])) {
        // Verify nonce for security
        check_admin_referer('generic_sameas_save_settings');
        
        // Sanitize and prepare all form data
        $options = array(
            'entity_type' => sanitize_text_field($_POST['entity_type']),
            'entity_name' => sanitize_text_field($_POST['entity_name']),
            'entity_url' => esc_url_raw($_POST['entity_url']),
            'job_title' => sanitize_text_field($_POST['job_title']),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'company_url' => esc_url_raw($_POST['company_url']),
            'alternate_names' => array(),
            'knows_about' => array(),
            'alumni_of' => array(),
            'social_profiles' => array()
        );
        
        // Process alternative names array
        if (isset($_POST['alternate_names']) && is_array($_POST['alternate_names'])) {
            foreach ($_POST['alternate_names'] as $name) {
                $clean_name = sanitize_text_field(trim($name));
                if (!empty($clean_name)) {
                    $options['alternate_names'][] = $clean_name;
                }
            }
        }
        
        // Process areas of expertise array
        if (isset($_POST['knows_about']) && is_array($_POST['knows_about'])) {
            foreach ($_POST['knows_about'] as $item) {
                $clean_item = sanitize_text_field(trim($item));
                if (!empty($clean_item)) {
                    $options['knows_about'][] = $clean_item;
                }
            }
        }
        
        // Process alumni institutions array
        if (isset($_POST['alumni_of']) && is_array($_POST['alumni_of'])) {
            foreach ($_POST['alumni_of'] as $item) {
                $clean_item = sanitize_text_field(trim($item));
                if (!empty($clean_item)) {
                    $options['alumni_of'][] = $clean_item;
                }
            }
        }
        
        // Process social profile URLs array
        if (isset($_POST['social_profiles']) && is_array($_POST['social_profiles'])) {
            foreach ($_POST['social_profiles'] as $url) {
                $clean_url = esc_url_raw(trim($url));
                if (!empty($clean_url)) {
                    $options['social_profiles'][] = $clean_url;
                }
            }
        }
        
        // Save options to database
        update_option('generic_sameas_options', $options);
        echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'generic-sameas-schema') . '</p></div>';
    }
    
    // Load current options with defaults
    $options = get_option('generic_sameas_options', array());
    $defaults = array(
        'entity_type' => 'Person',
        'entity_name' => get_bloginfo('name'),
        'entity_url' => home_url(),
        'job_title' => '',
        'company_name' => '',
        'company_url' => '',
        'alternate_names' => array(),
        'knows_about' => array(),
        'alumni_of' => array(),
        'social_profiles' => array()
    );
    $options = wp_parse_args($options, $defaults);
    
    ?>
    <div class="wrap">
        <h1><?php _e('sameAs Schema Settings', 'generic-sameas-schema'); ?></h1>
        <p><?php _e('Configure Schema.org markup for your professional and social media profiles. This plugin complements existing SEO plugins by focusing on professional details and social media links.', 'generic-sameas-schema'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('generic_sameas_save_settings'); ?>
            
            <!-- Basic Information Section -->
            <h2><?php _e('Basic Information', 'generic-sameas-schema'); ?></h2>
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
                        <p class="description"><?php _e('The primary name of the person or organization.', 'generic-sameas-schema'); ?></p>
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
                    <th scope="row"><?php _e('Alternative Names', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="alternate-names">
                            <?php
                            $alt_names = !empty($options['alternate_names']) ? $options['alternate_names'] : array('');
                            foreach ($alt_names as $index => $name) {
                                echo '<div class="alt-name-row">';
                                echo '<input type="text" name="alternate_names[]" value="' . esc_attr($name) . '" class="regular-text" placeholder="e.g., Dr. John Smith, J. Smith" />';
                                echo '<button type="button" class="button remove-alt-name" style="margin-left: 10px;">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" id="add-alt-name" class="button"><?php _e('Add Alternative Name', 'generic-sameas-schema'); ?></button>
                        <p class="description"><?php _e('Add alternative forms of your name (with/without titles, middle names, etc.).', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
            </table>
            
            <!-- Professional Information Section -->
            <h2><?php _e('Professional Information', 'generic-sameas-schema'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Job Title', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="text" name="job_title" value="<?php echo esc_attr($options['job_title']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Your current job title, e.g., "Head of Library" or "Information Specialist".', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Company/Organization', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="text" name="company_name" value="<?php echo esc_attr($options['company_name']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Name of your employer or organization.', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Company Website', 'generic-sameas-schema'); ?></th>
                    <td>
                        <input type="url" name="company_url" value="<?php echo esc_attr($options['company_url']); ?>" class="regular-text" />
                        <p class="description"><?php _e('Website URL of your employer/organization (optional).', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Areas of Expertise', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="knows-about">
                            <?php
                            $expertise = !empty($options['knows_about']) ? $options['knows_about'] : array('');
                            foreach ($expertise as $index => $item) {
                                echo '<div class="expertise-row">';
                                echo '<input type="text" name="knows_about[]" value="' . esc_attr($item) . '" class="regular-text" placeholder="e.g., Open Science, Digital Libraries" />';
                                echo '<button type="button" class="button remove-expertise" style="margin-left: 10px;">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" id="add-expertise" class="button"><?php _e('Add Expertise', 'generic-sameas-schema'); ?></button>
                        <p class="description"><?php _e('Add your areas of expertise, skills, or research interests.', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Education (Alumni Of)', 'generic-sameas-schema'); ?></th>
                    <td>
                        <div id="alumni-of">
                            <?php
                            $alumni = !empty($options['alumni_of']) ? $options['alumni_of'] : array('');
                            foreach ($alumni as $index => $item) {
                                echo '<div class="alumni-row">';
                                echo '<input type="text" name="alumni_of[]" value="' . esc_attr($item) . '" class="regular-text" placeholder="e.g., Universität Bremen" />';
                                echo '<button type="button" class="button remove-alumni" style="margin-left: 10px;">Remove</button>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button type="button" id="add-alumni" class="button"><?php _e('Add Institution', 'generic-sameas-schema'); ?></button>
                        <p class="description"><?php _e('Add educational institutions where you studied (universities, colleges, etc.).', 'generic-sameas-schema'); ?></p>
                    </td>
                </tr>
            </table>
            
            <!-- Social Profiles Section -->
            <h2><?php _e('Social Profiles & Professional Links', 'generic-sameas-schema'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Profile URLs', 'generic-sameas-schema'); ?></th>
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
                        <button type="button" id="add-profile" class="button"><?php _e('Add Profile URL', 'generic-sameas-schema'); ?></button>
                        <p class="description">
                            <?php _e('Add URLs to your social media profiles, academic profiles, or other professional links. Examples:', 'generic-sameas-schema'); ?>
                            <br>• https://linkedin.com/in/username
                            <br>• https://scholar.google.com/citations?user=...
                            <br>• https://www.researchgate.net/profile/...
                            <br>• https://orcid.org/0000-0000-0000-0000
                            <br>• https://github.com/username
                            <br>• https://bsky.app/profile/username
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <!-- Live Schema Preview Section -->
        <h2><?php _e('Live Schema Preview', 'generic-sameas-schema'); ?></h2>
        <div style="background: #f1f1f1; padding: 15px; border-radius: 4px; margin-top: 20px;">
            <p><strong><?php _e('Generated Schema.org JSON-LD:', 'generic-sameas-schema'); ?></strong></p>
            <pre id="schema-preview" style="background: white; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; max-height: 400px;"></pre>
        </div>
    </div>
    
    <script>
    /**
     * Update the live schema preview based on current form values
     */
    function updateSchemaPreview() {
        // Collect all form values
        const entityType = document.querySelector('select[name="entity_type"]').value;
        const entityName = document.querySelector('input[name="entity_name"]').value;
        const entityUrl = document.querySelector('input[name="entity_url"]').value;
        const jobTitle = document.querySelector('input[name="job_title"]').value;
        const companyName = document.querySelector('input[name="company_name"]').value;
        const companyUrl = document.querySelector('input[name="company_url"]').value;
        
        // Collect array values and filter empty entries
        const alternateNames = Array.from(document.querySelectorAll('input[name="alternate_names[]"]'))
            .map(input => input.value.trim())
            .filter(val => val !== '');
            
        const knowsAbout = Array.from(document.querySelectorAll('input[name="knows_about[]"]'))
            .map(input => input.value.trim())
            .filter(val => val !== '');
            
        const alumniOf = Array.from(document.querySelectorAll('input[name="alumni_of[]"]'))
            .map(input => input.value.trim())
            .filter(val => val !== '');
            
        const sameAs = Array.from(document.querySelectorAll('input[name="social_profiles[]"]'))
            .map(input => input.value.trim())
            .filter(val => val !== '');
        
        // Build schema object
        let schema = {
            "@context": "https://schema.org",
            "@type": entityType,
            "name": entityName,
            "url": entityUrl
        };
        
        // Add conditional fields
        if (alternateNames.length > 0) {
            schema.alternateName = alternateNames.length === 1 ? alternateNames[0] : alternateNames;
        }
        if (jobTitle) schema.jobTitle = jobTitle;
        if (companyName) {
            schema.worksFor = {
                "@type": "Organization",
                "name": companyName
            };
            if (companyUrl) schema.worksFor.url = companyUrl;
        }
        if (knowsAbout.length > 0) schema.knowsAbout = knowsAbout;
        if (alumniOf.length > 0) {
            schema.alumniOf = alumniOf.length === 1 ? alumniOf[0] : alumniOf;
        }
        if (sameAs.length > 0) schema.sameAs = sameAs;
        
        // Update preview display
        document.getElementById('schema-preview').textContent = JSON.stringify(schema, null, 2);
    }
    
    // Attach event listeners for live preview updates
    document.addEventListener('input', updateSchemaPreview);
    document.addEventListener('change', updateSchemaPreview);
    
    // Initialize preview on page load
    updateSchemaPreview();
    
    /**
     * Event handlers for adding new field rows
     */
    
    // Add alternative name row
    document.getElementById('add-alt-name').addEventListener('click', function() {
        var container = document.getElementById('alternate-names');
        var newRow = document.createElement('div');
        newRow.className = 'alt-name-row';
        newRow.innerHTML = '<input type="text" name="alternate_names[]" value="" class="regular-text" placeholder="e.g., Dr. John Smith, J. Smith" />' +
                          '<button type="button" class="button remove-alt-name" style="margin-left: 10px;">Remove</button>';
        container.appendChild(newRow);
        updateSchemaPreview();
    });
    
    // Add expertise row
    document.getElementById('add-expertise').addEventListener('click', function() {
        var container = document.getElementById('knows-about');
        var newRow = document.createElement('div');
        newRow.className = 'expertise-row';
        newRow.innerHTML = '<input type="text" name="knows_about[]" value="" class="regular-text" placeholder="e.g., Open Science, Digital Libraries" />' +
                          '<button type="button" class="button remove-expertise" style="margin-left: 10px;">Remove</button>';
        container.appendChild(newRow);
        updateSchemaPreview();
    });
    
    // Add alumni institution row
    document.getElementById('add-alumni').addEventListener('click', function() {
        var container = document.getElementById('alumni-of');
        var newRow = document.createElement('div');
        newRow.className = 'alumni-row';
        newRow.innerHTML = '<input type="text" name="alumni_of[]" value="" class="regular-text" placeholder="e.g., Universität Bremen" />' +
                          '<button type="button" class="button remove-alumni" style="margin-left: 10px;">Remove</button>';
        container.appendChild(newRow);
        updateSchemaPreview();
    });
    
    // Add social profile row
    document.getElementById('add-profile').addEventListener('click', function() {
        var container = document.getElementById('social-profiles');
        var newRow = document.createElement('div');
        newRow.className = 'profile-row';
        newRow.innerHTML = '<input type="url" name="social_profiles[]" value="" class="regular-text" placeholder="https://example.com/profile" />' +
                          '<button type="button" class="button remove-profile" style="margin-left: 10px;">Remove</button>';
        container.appendChild(newRow);
        updateSchemaPreview();
    });
    
    /**
     * Event delegation for remove buttons (handles dynamically added elements)
     */
    document.addEventListener('click', function(e) {
        // Remove alternative name row
        if (e.target && e.target.classList.contains('remove-alt-name')) {
            var altNameRows = document.querySelectorAll('.alt-name-row');
            if (altNameRows.length > 1) {
                e.target.parentNode.remove();
            } else {
                e.target.parentNode.querySelector('input').value = '';
            }
            updateSchemaPreview();
        }
        
        // Remove expertise row
        if (e.target && e.target.classList.contains('remove-expertise')) {
            var expertiseRows = document.querySelectorAll('.expertise-row');
            if (expertiseRows.length > 1) {
                e.target.parentNode.remove();
            } else {
                e.target.parentNode.querySelector('input').value = '';
            }
            updateSchemaPreview();
        }
        
        // Remove alumni row
        if (e.target && e.target.classList.contains('remove-alumni')) {
            var alumniRows = document.querySelectorAll('.alumni-row');
            if (alumniRows.length > 1) {
                e.target.parentNode.remove();
            } else {
                e.target.parentNode.querySelector('input').value = '';
            }
            updateSchemaPreview();
        }
        
        // Remove profile row
        if (e.target && e.target.classList.contains('remove-profile')) {
            var profileRows = document.querySelectorAll('.profile-row');
            if (profileRows.length > 1) {
                e.target.parentNode.remove();
            } else {
                e.target.parentNode.querySelector('input').value = '';
            }
            updateSchemaPreview();
        }
    });
    </script>
    
    <style>
    /* Styling for admin interface */
    .profile-row, .expertise-row, .alumni-row, .alt-name-row {
        margin-bottom: 10px;
    }
    .profile-row input[type="url"], 
    .expertise-row input[type="text"], 
    .alumni-row input[type="text"],
    .alt-name-row input[type="text"] {
        margin-bottom: 0;
    }
    h2 {
        border-bottom: 1px solid #ccc;
        padding-bottom: 10px;
        margin-top: 30px;
    }
    #schema-preview {
        font-family: 'Courier New', monospace;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    </style>
    <?php
}

/**
 * Plugin activation hook
 * 
 * Sets up default options when the plugin is first activated.
 * Uses WordPress site information as sensible defaults.
 */
function generic_sameas_activate() {
    $default_options = array(
        'entity_type' => 'Person',
        'entity_name' => get_bloginfo('name'),
        'entity_url' => home_url(),
        'job_title' => '',
        'company_name' => '',
        'company_url' => '',
        'alternate_names' => array(),
        'knows_about' => array(),
        'alumni_of' => array(),
        'social_profiles' => array()
    );
    
    // Only add options if they don't already exist
    if (!get_option('generic_sameas_options')) {
        add_option('generic_sameas_options', $default_options);
    }
}
register_activation_hook(__FILE__, 'generic_sameas_activate');

/**
 * Plugin deactivation hook
 * 
 * Currently does nothing - settings are preserved when plugin is deactivated.
 * This allows users to temporarily disable the plugin without losing configuration.
 */
function generic_sameas_deactivate() {
    // Settings are kept on deactivation for user convenience
    // They are only removed on complete uninstall
}
register_deactivation_hook(__FILE__, 'generic_sameas_deactivate');

/**
 * Plugin uninstall hook
 * 
 * Removes all plugin data from the database when plugin is uninstalled.
 * This ensures clean removal and follows WordPress best practices.
 */
function generic_sameas_uninstall() {
    delete_option('generic_sameas_options');
}
register_uninstall_hook(__FILE__, 'generic_sameas_uninstall');
?>
