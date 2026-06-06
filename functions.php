<?php
function crm_custom_theme_enqueue_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style('crm-theme-style', get_stylesheet_uri(), array(), '1.0');
    
    // Enqueue custom CSS
    wp_enqueue_style('crm-custom-css', get_template_directory_uri() . '/css/custom.css', array(), time());

    // Enqueue custom JS
    wp_enqueue_script('crm-custom-js', get_template_directory_uri() . '/js/custom.js', array(), time(), true);

    // Localize script for AJAX URL
    wp_localize_script('crm-custom-js', 'crmAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'crm_custom_theme_enqueue_scripts');

// Add theme support
function crm_custom_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'crm_custom_theme_setup');

// Require enquiry backend logic
require_once get_template_directory() . '/inc/enquiries.php';

// Require PWA functionality
require_once get_template_directory() . '/inc/pwa.php';


add_filter('admin_footer_text', function () {
    return '';
});

add_filter('update_footer', '__return_empty_string', 11);

/**
 * CRM Helper Functions
 */

// Get all dynamic projects
function crm_get_projects() {
    $projects = get_option('crm_projects');
    
    $default_budgets = array("70 L to 85 L", "85 L to 1 CR", "1 CR to 1.25 CR", "1.25 CR to 1.50 CR", "1.50 CR to 1.75 CR", "1.75 CR to 2.00 CR", "2.00 CR to 2.25 CR");

    // Fallback to defaults if empty
    if (empty($projects) || !is_array($projects)) {
        $projects = array(
            array('name' => 'Pearl Grace', 'logo' => get_template_directory_uri() . '/pearl-grace-logo.png', 'budget_ranges' => $default_budgets),
            array('name' => 'MK Crown', 'logo' => get_template_directory_uri() . '/mk-crown-logo.png', 'budget_ranges' => $default_budgets),
            array('name' => 'Navrang Elite', 'logo' => get_template_directory_uri() . '/navrang-elite-logo.jpg', 'budget_ranges' => $default_budgets),
            array('name' => 'MK Harmony', 'logo' => get_template_directory_uri() . '/mk-harmony-logo.jpg', 'budget_ranges' => $default_budgets),
            array('name' => 'MK Imperial', 'logo' => get_template_directory_uri() . '/mk-imperial-logo.png', 'budget_ranges' => $default_budgets)
        );
        // Optionally save the default state so they are editable right away
        update_option('crm_projects', $projects);
    } else {
        // Ensure all existing projects have budget_ranges backward compatibility
        $updated = false;
        foreach ($projects as &$project) {
            if (!isset($project['budget_ranges']) || !is_array($project['budget_ranges'])) {
                $project['budget_ranges'] = $default_budgets;
                $updated = true;
            }
        }
        if ($updated) {
            update_option('crm_projects', $projects);
        }
    }
    return $projects;
}

// Role Checkers
function crm_is_site_head($user = null) {
    if (!$user) $user = wp_get_current_user();
    return in_array('crm_site_head', (array) $user->roles);
}

function crm_is_site_head_master($user = null) {
    if (!$user) $user = wp_get_current_user();
    return in_array('crm_site_head_master', (array) $user->roles);
}

function crm_is_closing_manager($user = null) {
    if (!$user) $user = wp_get_current_user();
    return in_array('crm_closing_manager', (array) $user->roles);
}

// =========================================================================
// PROJECT ASSIGNMENT FOR SITE MANAGERS
// =========================================================================

// Display the Assigned Projects fields in User Profile
add_action('show_user_profile', 'crm_add_site_manager_project_fields');
add_action('edit_user_profile', 'crm_add_site_manager_project_fields');

function crm_add_site_manager_project_fields($user) {
    // Only show if the user being edited is a site_manager
    if (!in_array('site_manager', (array) $user->roles)) {
        return;
    }

    // Only allow Admin or Site-Head Master to see/edit these assignments
    $current_user = wp_get_current_user();
    if (!current_user_can('manage_options') && !in_array('crm_site_head_master', (array) $current_user->roles)) {
        return;
    }

    $projects = crm_get_projects();
    $assigned_projects = get_user_meta($user->ID, 'crm_assigned_projects', true);
    if (!is_array($assigned_projects)) {
        $assigned_projects = array();
    }
    ?>
    <h3>Project Assignment</h3>
    <p>Select which projects this Site Manager is assigned to. (Only Site-Head Masters and Admins can modify this).</p>
    <table class="form-table">
        <tr>
            <th><label>Assigned Projects</label></th>
            <td>
                <?php if (empty($projects)) : ?>
                    <p>No projects available.</p>
                <?php else : ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                        <?php foreach ($projects as $project) : ?>
                            <label style="display: flex; align-items: center; gap: 5px; background: #f8fafc; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;">
                                <input type="checkbox" name="crm_assigned_projects[]" value="<?php echo esc_attr($project['name']); ?>" <?php checked(in_array($project['name'], $assigned_projects)); ?>>
                                <strong><?php echo esc_html($project['name']); ?></strong>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
}

// Save the Assigned Projects fields
add_action('personal_options_update', 'crm_save_site_manager_project_fields');
add_action('edit_user_profile_update', 'crm_save_site_manager_project_fields');

function crm_save_site_manager_project_fields($user_id) {
    // Check permissions
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    $current_user = wp_get_current_user();
    if (!current_user_can('manage_options') && !in_array('crm_site_head_master', (array) $current_user->roles)) {
        return false;
    }

    $user_obj = get_userdata($user_id);
    if (!in_array('site_manager', (array) $user_obj->roles)) {
        return false;
    }

    if (isset($_POST['crm_assigned_projects']) && is_array($_POST['crm_assigned_projects'])) {
        $assigned = array_map('sanitize_text_field', $_POST['crm_assigned_projects']);
        update_user_meta($user_id, 'crm_assigned_projects', $assigned);
    } else {
        // If nothing is checked, clear it
        update_user_meta($user_id, 'crm_assigned_projects', array());
    }
}