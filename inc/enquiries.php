<?php
// DB Setup
function crm_create_enquiries_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'crm_enquiries';
    $followups_table = $wpdb->prefix . 'crm_followups';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        building_name varchar(100) DEFAULT '' NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        date_visit date NOT NULL,
        name varchar(255) NOT NULL,
        contact varchar(50) NOT NULL,
        email varchar(255) NOT NULL,
        residence varchar(255) NOT NULL,
        occupation varchar(100) NOT NULL,
        company_name varchar(255) NOT NULL,
        company_location varchar(255) NOT NULL,
        configuration varchar(100) NOT NULL,
        budget varchar(100) NOT NULL,
        source varchar(100) NOT NULL,
        reference_name varchar(255) NOT NULL,
        cp_name varchar(255) NOT NULL,
        cp_contact varchar(50) NOT NULL,
        signature text NOT NULL,
        closing_manager_id bigint(20) DEFAULT 0 NOT NULL,
        lead_status varchar(50) DEFAULT '' NOT NULL,
        sourcing_manager varchar(255) DEFAULT '' NOT NULL,
        client_age varchar(50) DEFAULT '' NOT NULL,
        visit_type varchar(50) DEFAULT '' NOT NULL,
        visit_attended_by varchar(100) DEFAULT '' NOT NULL,
        funding_source varchar(100) DEFAULT '' NOT NULL,
        sop_amount varchar(100) DEFAULT '' NOT NULL,
        ready_down_payment varchar(100) DEFAULT '' NOT NULL,
        own_contribution varchar(100) DEFAULT '' NOT NULL,
        unit_like varchar(255) DEFAULT '' NOT NULL,
        unit_floor varchar(100) DEFAULT '' NOT NULL,
        unit_budget varchar(100) DEFAULT '' NOT NULL,
        feedback_by varchar(255) DEFAULT '' NOT NULL,
        feedback_details text NOT NULL,
        s_m varchar(255) DEFAULT '' NOT NULL,
        next_action_date date DEFAULT '0000-00-00' NOT NULL,
        next_action_remarks text NOT NULL,
        assessment_saved tinyint(1) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql_followups = "CREATE TABLE $followups_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        enquiry_id bigint(20) NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        s_m varchar(255) DEFAULT '' NOT NULL,
        action_date date DEFAULT '0000-00-00' NOT NULL,
        remarks text NOT NULL,
        added_by varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id),
        KEY enquiry_id (enquiry_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql_followups);
    
    // Safety check: Alter table dynamically if staging db delta did not fire
    $column = $wpdb->get_results($wpdb->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
        DB_NAME, $table_name, 'building_name'
    ));
    if (empty($column)) {
        $wpdb->query("ALTER TABLE $table_name ADD building_name varchar(100) DEFAULT '' NOT NULL AFTER id");
    }
    
    add_option('crm_db_version', '1.0');
}
add_action('after_setup_theme', 'crm_create_enquiries_table');

// Setup Custom Roles
function crm_setup_client_role() {
    // Remove the old roles if present
    remove_role('crm_client');
    remove_role('crm_site_head');

    add_role(
        'site_manager',
        'Site Manager',
        array(
            'read' => true,
            'view_crm_enquiries' => true,
            'create_users' => true,
            'edit_users' => true,
            'list_users' => true,
            'promote_users' => true,
            'delete_users' => true
        )
    );

    add_role(
        'crm_closing_manager',
        'Closing Manager',
        array(
            'read' => true
        )
    );

    // Explicitly add capabilities in case the role was already created
    $site_manager_role = get_role('site_manager');
    if ($site_manager_role) {
        $site_manager_role->add_cap('read');
        $site_manager_role->add_cap('view_crm_enquiries');
        $site_manager_role->add_cap('create_users');
        $site_manager_role->add_cap('edit_users');
        $site_manager_role->add_cap('list_users');
        $site_manager_role->add_cap('promote_users');
        $site_manager_role->add_cap('delete_users');
    }

    $role = get_role('administrator');
    if ($role && !$role->has_cap('view_crm_enquiries')) {
        $role->add_cap('view_crm_enquiries');
    }
}
add_action('after_setup_theme', 'crm_setup_client_role');

// Programmatically Create Closing Manager Page
function crm_create_closing_manager_page() {
    $page_slug = 'closing-manager';
    $page_check = get_page_by_path($page_slug);
    
    if (!isset($page_check->ID)) {
        wp_insert_post(array(
            'post_title'    => 'Closing Manager Portal',
            'post_name'     => $page_slug,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_author'   => 1,
        ));
    }
}
add_action('after_setup_theme', 'crm_create_closing_manager_page');

// Restrict Client Admin Menu
function crm_restrict_client_menu() {
    $user = wp_get_current_user();
    if (!current_user_can('manage_options')) {
        if (in_array('site_manager', $user->roles) || in_array('crm_closing_manager', $user->roles)) {
            remove_menu_page('index.php'); // Hide Dashboard
            remove_menu_page('profile.php'); // Hide Profile
        }
    }
}
add_action('admin_menu', 'crm_restrict_client_menu', 999);

// Hide Admin Bar on Frontend for all users to protect PWA experience
add_filter('show_admin_bar', '__return_false');

// Hide default WordPress header/logo on the wp-login.php page
function crm_custom_login_header() {
    echo '<style type="text/css">
        #login h1 a, .login h1 a {
            display: none !important;
        }
        #login {
            padding: 4% 0 0 !important;
        }
    </style>';
}
add_action('login_enqueue_scripts', 'crm_custom_login_header');

// Redirect Client on Login
function crm_site_manager_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('site_manager', $user->roles)) {
            return admin_url('admin.php?page=crm-enquiries');
        }
        if (in_array('crm_closing_manager', $user->roles)) {
            return home_url('/closing-manager/');
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'crm_site_manager_login_redirect', 10, 3);

// Limit editable roles for Site Manager so they can only manage "Closing Manager" and "Site Manager"
function crm_restrict_editable_roles_for_site_manager($roles) {
    $user = wp_get_current_user();
    if (in_array('site_manager', $user->roles) && !current_user_can('manage_options')) {
        $allowed = array('crm_closing_manager', 'site_manager');
        foreach ($roles as $role_key => $role_data) {
            if (!in_array($role_key, $allowed)) {
                unset($roles[$role_key]);
            }
        }
    }
    return $roles;
}
add_filter('editable_roles', 'crm_restrict_editable_roles_for_site_manager');

// Restrict user list in wp-admin for Site Manager to see only Closing Managers and Site Managers
function crm_restrict_user_list_for_site_manager($query) {
    if (is_admin()) {
        $user = wp_get_current_user();
        if (in_array('site_manager', $user->roles) && !current_user_can('manage_options')) {
            $query->set('role__in', array('crm_closing_manager', 'site_manager'));
        }
    }
}
add_action('pre_get_users', 'crm_restrict_user_list_for_site_manager');

// Handle Frontend Login Failures elegant redirect
add_action('wp_login_failed', function($username) {
    $referrer = wp_get_referer();
    if (!empty($referrer) && strpos($referrer, 'closing-manager') !== false) {
        wp_redirect(add_query_arg('login', 'failed', home_url('/closing-manager/')));
        exit;
    }
});

// AJAX Handler
add_action('wp_ajax_submit_enquiry', 'crm_handle_enquiry_submission');
add_action('wp_ajax_nopriv_submit_enquiry', 'crm_handle_enquiry_submission');
function crm_handle_enquiry_submission() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'crm_enquiries';

    // Sanitize data
    $data = array(
        'building_name' => isset($_POST['building_name']) ? sanitize_text_field($_POST['building_name']) : 'Pearl Grace',
        'created_at' => current_time('mysql'),
        'date_visit' => sanitize_text_field($_POST['date']),
        'name' => sanitize_text_field($_POST['name']),
        'contact' => sanitize_text_field($_POST['contact']),
        'email' => sanitize_email($_POST['email']),
        'residence' => sanitize_text_field($_POST['residence']),
        'occupation' => sanitize_text_field($_POST['occupation']),
        'company_name' => sanitize_text_field($_POST['company_name']),
        'company_location' => sanitize_text_field($_POST['company_location']),
        'configuration' => sanitize_text_field($_POST['configuration']),
        'budget' => sanitize_text_field($_POST['budget']),
        'source' => sanitize_text_field($_POST['source']),
        'reference_name' => isset($_POST['reference_name']) ? sanitize_text_field($_POST['reference_name']) : '',
        'cp_name' => sanitize_text_field($_POST['cp_name']),
        'cp_contact' => sanitize_text_field($_POST['cp_contact']),
        'closing_manager_id' => isset($_POST['closing_manager_id']) ? intval($_POST['closing_manager_id']) : 0,
    );

    $inserted = $wpdb->insert($table_name, $data);

    if ($inserted) {
        wp_send_json_success(array('message' => 'Enquiry submitted successfully!'));
    } else {
        wp_send_json_error(array('message' => 'Failed to submit enquiry.'));
    }
}

// Admin Menu
function crm_enquiries_menu() {
    if (current_user_can('view_crm_enquiries') || current_user_can('manage_options')) {
        add_menu_page(
            'Enquiries',
            'Enquiries',
            'view_crm_enquiries',
            'crm-enquiries',
            'crm_enquiries_page_html',
            'dashicons-clipboard',
            20
        );
    }

    if (current_user_can('manage_options') || crm_is_site_head_master()) {
        add_submenu_page(
            'crm-enquiries',
            'Manage Projects',
            'Manage Projects',
            'read',
            'crm-projects',
            'crm_projects_page_html'
        );
    }
}
add_action('admin_menu', 'crm_enquiries_menu');

// CSV Export Logic
add_action('admin_init', 'crm_export_enquiries_csv');
function crm_export_enquiries_csv() {
    if (isset($_GET['page']) && $_GET['page'] == 'crm-enquiries' && isset($_GET['export_csv'])) {
        if (!current_user_can('view_crm_enquiries')) return;

        global $wpdb;
        $table_name = $wpdb->prefix . 'crm_enquiries';
        
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $project_filter = isset($_GET['building_name']) ? sanitize_text_field($_GET['building_name']) : '';
        
        $where_clauses = array();
        $prepare_args = array();

        if (!empty($search)) {
            $where_clauses[] = "(name LIKE %s OR email LIKE %s OR contact LIKE %s)";
            $prepare_args[] = '%' . $search . '%';
            $prepare_args[] = '%' . $search . '%';
            $prepare_args[] = '%' . $search . '%';
        }

        if (!empty($date_from)) {
            $where_clauses[] = "date_visit >= %s";
            $prepare_args[] = $date_from;
        }

        if (!empty($date_to)) {
            $where_clauses[] = "date_visit <= %s";
            $prepare_args[] = $date_to;
        }

        if (!empty($project_filter)) {
            if ($project_filter === 'Pearl Grace') {
                $where_clauses[] = "(building_name = 'Pearl Grace' OR building_name = '')";
            } else {
                $where_clauses[] = "building_name = %s";
                $prepare_args[] = $project_filter;
            }
        }

        $where = '';
        if (!empty($where_clauses)) {
            $where = " WHERE " . implode(' AND ', $where_clauses);
            if (!empty($prepare_args)) {
                $where = $wpdb->prepare($where, $prepare_args);
            }
        }

        $results = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY id DESC", ARRAY_A);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="enquiries-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        if (!empty($results)) {
            $followups_table = $wpdb->prefix . 'crm_followups';
            $client_actions = array();
            $max_actions = 0;

            // Pre-process action plans for each client
            foreach ($results as $row) {
                $actions = array();

                // 1. Get chronological history from wp_crm_followups (oldest first)
                $history = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $followups_table WHERE enquiry_id = %d ORDER BY created_at ASC",
                    $row['id']
                ));

                foreach ($history as $item) {
                    $date_str = ($item->action_date && $item->action_date !== '0000-00-00') ? date('d M Y', strtotime($item->action_date)) : 'N/A';
                    $actions[] = "Date: {$date_str} | S.M: {$item->s_m} | Remarks: {$item->remarks} (Logged by {$item->added_by})";
                }

                // 2. Append active next action follow-up if present
                $has_active = !empty($row['s_m']) || (!empty($row['next_action_date']) && $row['next_action_date'] !== '0000-00-00') || !empty($row['next_action_remarks']);
                if ($has_active) {
                    $date_str = (!empty($row['next_action_date']) && $row['next_action_date'] !== '0000-00-00') ? date('d M Y', strtotime($row['next_action_date'])) : 'N/A';
                    $actions[] = "Date: {$date_str} | S.M: {$row['s_m']} | Remarks: {$row['next_action_remarks']} (Active)";
                }

                $client_actions[$row['id']] = $actions;
                if (count($actions) > $max_actions) {
                    $max_actions = count($actions);
                }
            }

            // Build dynamic CSV header columns
            $headers = array(
                'ID', 'Created At', 'Date Visit', 'Project', 'Name', 'Contact', 'Email', 
                'Residence', 'Occupation', 'Company Name', 'Company Location', 
                'Configuration', 'Budget', 'Source', 'Reference Name', 
                'Channel Partner Name', 'Channel Partner Contact', 'Closing Manager',
                'Status Rating', 'Sourcing Manager', 'Client Age', 'Visit Frequency',
                'Visit Attended By', 'Funding Option', 'SOP Amount', 'Ready Down Payment',
                'Own Contribution', 'Preferred Unit', 'Preferred Floor', 'Preferred Budget',
                'Feedback By', 'Feedback Details'
            );

            for ($i = 1; $i <= $max_actions; $i++) {
                $headers[] = 'Action ' . $i;
            }

            fputcsv($output, $headers);

            foreach ($results as $row) {
                $manager_name = 'None';
                if (!empty($row['closing_manager_id'])) {
                    $manager_data = get_userdata($row['closing_manager_id']);
                    if ($manager_data) {
                        $manager_name = $manager_data->display_name;
                    }
                }

                $row_data = array(
                    $row['id'],
                    $row['created_at'],
                    $row['date_visit'],
                    !empty($row['building_name']) ? $row['building_name'] : 'Pearl Grace',
                    $row['name'],
                    $row['contact'],
                    $row['email'],
                    $row['residence'],
                    $row['occupation'],
                    $row['company_name'],
                    $row['company_location'],
                    $row['configuration'],
                    $row['budget'],
                    $row['source'],
                    $row['reference_name'],
                    $row['cp_name'],
                    $row['cp_contact'],
                    $manager_name,
                    $row['lead_status'],
                    $row['sourcing_manager'],
                    $row['client_age'],
                    $row['visit_type'],
                    $row['visit_attended_by'],
                    $row['funding_source'],
                    $row['sop_amount'],
                    $row['ready_down_payment'],
                    $row['own_contribution'],
                    $row['unit_like'],
                    $row['unit_floor'],
                    $row['unit_budget'],
                    $row['feedback_by'],
                    $row['feedback_details']
                );

                // Append actions formatted strings padded to max_actions count
                $actions = isset($client_actions[$row['id']]) ? $client_actions[$row['id']] : array();
                for ($i = 0; $i < $max_actions; $i++) {
                    $row_data[] = isset($actions[$i]) ? $actions[$i] : '';
                }

                fputcsv($output, $row_data);
            }
        }
        fclose($output);
        exit;
    }
}

// CRUD Actions
add_action('admin_init', 'crm_handle_enquiries_crud');
function crm_handle_enquiries_crud() {
    if (isset($_GET['page']) && $_GET['page'] == 'crm-enquiries') {
        if (!current_user_can('view_crm_enquiries')) return;

        global $wpdb;
        $table_name = $wpdb->prefix . 'crm_enquiries';

        if (isset($_POST['bulk_action']) && $_POST['bulk_action'] === 'delete' && isset($_POST['enquiry_ids']) && is_array($_POST['enquiry_ids'])) {
            check_admin_referer('bulk_actions_enquiries');
            foreach ($_POST['enquiry_ids'] as $id) {
                $wpdb->delete($table_name, array('id' => intval($id)));
            }
            wp_redirect(admin_url('admin.php?page=crm-enquiries&bulk_deleted=' . count($_POST['enquiry_ids'])));
            exit;
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            check_admin_referer('delete_enquiry_' . $_GET['id']);
            $wpdb->delete($table_name, array('id' => intval($_GET['id'])));
            wp_redirect(admin_url('admin.php?page=crm-enquiries&deleted=1'));
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] === 'update_enquiry' && isset($_POST['id'])) {
            check_admin_referer('update_enquiry_' . $_POST['id']);
            
            $data = array(
                'building_name' => isset($_POST['building_name']) ? sanitize_text_field($_POST['building_name']) : 'Pearl Grace',
                'date_visit' => sanitize_text_field($_POST['date_visit']),
                'name' => sanitize_text_field($_POST['name']),
                'contact' => sanitize_text_field($_POST['contact']),
                'email' => sanitize_email($_POST['email']),
                'residence' => sanitize_text_field($_POST['residence']),
                'occupation' => sanitize_text_field($_POST['occupation']),
                'company_name' => sanitize_text_field($_POST['company_name']),
                'company_location' => sanitize_text_field($_POST['company_location']),
                'configuration' => sanitize_text_field($_POST['configuration']),
                'budget' => sanitize_text_field($_POST['budget']),
                'source' => sanitize_text_field($_POST['source']),
                'reference_name' => isset($_POST['reference_name']) ? sanitize_text_field($_POST['reference_name']) : '',
                'cp_name' => sanitize_text_field($_POST['cp_name']),
                'cp_contact' => sanitize_text_field($_POST['cp_contact']),
                'closing_manager_id' => isset($_POST['closing_manager_id']) ? intval($_POST['closing_manager_id']) : 0,
            );

            $wpdb->update($table_name, $data, array('id' => intval($_POST['id'])));
            wp_redirect(admin_url('admin.php?page=crm-enquiries&updated=1'));
            exit;
        }
    }
}

// Admin Page HTML
function crm_enquiries_page_html() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'crm_enquiries';

    // Handle Edit View
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $enquiry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
        if ($enquiry) {
            echo '<div class="wrap">';
            echo '<h1 class="wp-heading-inline">Edit Enquiry</h1>';
            echo '<a href="?page=crm-enquiries" class="page-title-action">Back to Enquiries</a>';
            echo '<hr class="wp-header-end">';
            
            echo '<form method="post" action="' . admin_url('admin.php?page=crm-enquiries') . '">';
            wp_nonce_field('update_enquiry_' . $id);
            echo '<input type="hidden" name="action" value="update_enquiry">';
            echo '<input type="hidden" name="id" value="' . $id . '">';
            
            $closing_managers = get_users(array(
                'role' => 'crm_closing_manager',
                'orderby' => 'display_name',
                'order' => 'ASC'
            ));

            echo '<table class="form-table" role="presentation">';
            echo '<tr><th scope="row"><label for="building_name">Project</label></th><td>';
            echo '<select name="building_name" id="building_name" class="regular-text">';
            $all_projects = crm_get_projects();
            foreach ($all_projects as $proj) {
                $p_name = esc_attr($proj['name']);
                echo '<option value="' . $p_name . '"' . selected($enquiry->building_name, $p_name, false) . '>' . esc_html($p_name) . '</option>';
            }
            echo '</select></td></tr>';
            echo '<tr><th scope="row"><label for="date_visit">Date Visit</label></th><td><input name="date_visit" type="date" id="date_visit" value="' . esc_attr($enquiry->date_visit) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="closing_manager_id">Closing Manager</label></th><td>';
            echo '<select name="closing_manager_id" id="closing_manager_id" class="regular-text">';
            echo '<option value="0"' . selected($enquiry->closing_manager_id, 0, false) . '>None Selected</option>';
            if (!empty($closing_managers)) {
                foreach ($closing_managers as $manager) {
                    echo '<option value="' . esc_attr($manager->ID) . '"' . selected($enquiry->closing_manager_id, $manager->ID, false) . '>' . esc_html($manager->display_name) . '</option>';
                }
            }
            echo '</select></td></tr>';
            echo '<tr><th scope="row"><label for="name">Name</label></th><td><input name="name" type="text" id="name" value="' . esc_attr($enquiry->name) . '" class="regular-text" required></td></tr>';
            echo '<tr><th scope="row"><label for="contact">Contact</label></th><td><input name="contact" type="text" id="contact" value="' . esc_attr($enquiry->contact) . '" class="regular-text" required></td></tr>';
            echo '<tr><th scope="row"><label for="email">Email</label></th><td><input name="email" type="email" id="email" value="' . esc_attr($enquiry->email) . '" class="regular-text" required></td></tr>';
            echo '<tr><th scope="row"><label for="residence">Residence</label></th><td><input name="residence" type="text" id="residence" value="' . esc_attr($enquiry->residence) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="occupation">Occupation</label></th><td><input name="occupation" type="text" id="occupation" value="' . esc_attr($enquiry->occupation) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="company_name">Company Name</label></th><td><input name="company_name" type="text" id="company_name" value="' . esc_attr($enquiry->company_name) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="company_location">Company Location</label></th><td><input name="company_location" type="text" id="company_location" value="' . esc_attr($enquiry->company_location) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="configuration">Configuration</label></th><td><input name="configuration" type="text" id="configuration" value="' . esc_attr($enquiry->configuration) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="budget">Budget</label></th><td><input name="budget" type="text" id="budget" value="' . esc_attr($enquiry->budget) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="source">Source</label></th><td><input name="source" type="text" id="source" value="' . esc_attr($enquiry->source) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="reference_name">Reference Name</label></th><td><input name="reference_name" type="text" id="reference_name" value="' . esc_attr($enquiry->reference_name) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="cp_name">Channel Partner Name</label></th><td><input name="cp_name" type="text" id="cp_name" value="' . esc_attr($enquiry->cp_name) . '" class="regular-text"></td></tr>';
            echo '<tr><th scope="row"><label for="cp_contact">Channel Partner Contact</label></th><td><input name="cp_contact" type="text" id="cp_contact" value="' . esc_attr($enquiry->cp_contact) . '" class="regular-text"></td></tr>';
            echo '</table>';
            
            submit_button('Update Enquiry');
            echo '</form>';
            echo '</div>';
            return;
        }
    }

    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    $project_filter = isset($_GET['building_name']) ? sanitize_text_field($_GET['building_name']) : '';
    
    $where_clauses = array();
    $prepare_args = array();

    if (!empty($search)) {
        $where_clauses[] = "(name LIKE %s OR email LIKE %s OR contact LIKE %s)";
        $prepare_args[] = '%' . $search . '%';
        $prepare_args[] = '%' . $search . '%';
        $prepare_args[] = '%' . $search . '%';
    }

    if (!empty($date_from)) {
        $where_clauses[] = "date_visit >= %s";
        $prepare_args[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_clauses[] = "date_visit <= %s";
        $prepare_args[] = $date_to;
    }

    if (!empty($project_filter)) {
        if ($project_filter === 'Pearl Grace') {
            $where_clauses[] = "(building_name = 'Pearl Grace' OR building_name = '')";
        } else {
            $where_clauses[] = "building_name = %s";
            $prepare_args[] = $project_filter;
        }
    }

    $current_user = wp_get_current_user();
    // Restrict Site Managers to only see their assigned projects
    if (in_array('site_manager', (array)$current_user->roles) && !current_user_can('manage_options') && !in_array('crm_site_head_master', (array)$current_user->roles)) {
        $assigned_projects = get_user_meta($current_user->ID, 'crm_assigned_projects', true);
        if (!empty($assigned_projects) && is_array($assigned_projects)) {
            $placeholders = implode(',', array_fill(0, count($assigned_projects), '%s'));
            if (in_array('Pearl Grace', $assigned_projects)) {
                $where_clauses[] = "(building_name IN ($placeholders) OR building_name = '')";
            } else {
                $where_clauses[] = "building_name IN ($placeholders)";
            }
            foreach ($assigned_projects as $proj) {
                $prepare_args[] = $proj;
            }
        } else {
            // If they have no assigned projects, hide all enquiries
            $where_clauses[] = "1 = 0";
        }
    }

    $where = '';
    if (!empty($where_clauses)) {
        $where = " WHERE " . implode(' AND ', $where_clauses);
        if (!empty($prepare_args)) {
            $where = $wpdb->prepare($where, $prepare_args);
        }
    }

    $results = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY id DESC");

    $export_url = add_query_arg(array(
        'page' => 'crm-enquiries',
        'export_csv' => '1',
        's' => $search,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'building_name' => $project_filter
    ), admin_url('admin.php'));

    echo '<div class="wrap">';
    echo '<style>
    .widefat .check-column {
        width: 2.2em;
        padding: 9px 0 0px 3px !important;
        vertical-align: top;
    }
    </style>';
    echo '<h1 class="wp-heading-inline">Enquiries</h1>';
    echo '<a href="' . esc_url($export_url) . '" class="page-title-action">Export CSV</a>';
    echo '<hr class="wp-header-end">';

    if (isset($_GET['deleted'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Enquiry deleted.</p></div>';
    }
    if (isset($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Enquiry updated.</p></div>';
    }
    if (isset($_GET['bulk_deleted'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . intval($_GET['bulk_deleted']) . ' enquiries deleted.</p></div>';
    }

    echo '<form method="get" style="margin-top: 1rem; display:flex; gap:15px; align-items:flex-end; margin-bottom:15px; flex-wrap:wrap;">';
    echo '<input type="hidden" name="page" value="crm-enquiries">';
    
    echo '<div>';
    echo '<label style="display:block; margin-bottom:5px;" for="post-search-input">Search:</label>';
    echo '<input type="search" id="post-search-input" name="s" value="' . esc_attr($search) . '" placeholder="Name, Email, Contact...">';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block; margin-bottom:5px;" for="building_name_filter">Project:</label>';
    echo '<select id="building_name_filter" name="building_name" style="padding: 3px 8px; height: 28px; width: 120px">';
    echo '<option value="">All Projects</option>';
    $all_projects = crm_get_projects();
    
    // Filter projects for Site Manager
    $allowed_projects = array();
    $current_user = wp_get_current_user();
    if (in_array('site_manager', (array)$current_user->roles) && !current_user_can('manage_options') && !in_array('crm_site_head_master', (array)$current_user->roles)) {
        $assigned = get_user_meta($current_user->ID, 'crm_assigned_projects', true);
        if (is_array($assigned)) {
            $allowed_projects = $assigned;
        }
    } else {
        // Admin or Site-Head Master sees all
        foreach ($all_projects as $p) {
            $allowed_projects[] = $p['name'];
        }
    }

    foreach ($all_projects as $proj) {
        $p_name = esc_attr($proj['name']);
        if (in_array($p_name, $allowed_projects) || (in_array('Pearl Grace', $allowed_projects) && $p_name === 'Pearl Grace')) {
            echo '<option value="' . $p_name . '"' . selected($project_filter, $p_name, false) . '>' . esc_html($p_name) . '</option>';
        }
    }
    echo '</select>';
    echo '</div>';
    
    echo '<div>';
    echo '<label style="display:block; margin-bottom:5px;" for="date_from">From Date:</label>';
    echo '<input type="date" id="date_from" name="date_from" value="' . esc_attr($date_from) . '">';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block; margin-bottom:5px;" for="date_to">To Date:</label>';
    echo '<input type="date" id="date_to" name="date_to" value="' . esc_attr($date_to) . '">';
    echo '</div>';

    echo '<div>';
    echo '<input type="submit" id="search-submit" class="button button-primary" value="Filter">';
    if (!empty($search) || !empty($date_from) || !empty($date_to) || !empty($project_filter)) {
        echo ' <a href="?page=crm-enquiries" class="button">Clear</a>';
    }
    echo '</div>';
    
    echo '</form>';

    echo '<form method="post" action="' . admin_url('admin.php?page=crm-enquiries') . '">';
    wp_nonce_field('bulk_actions_enquiries');
    
    echo '<div class="tablenav top" style="margin-bottom: 10px;">';
    echo '<div class="alignleft actions bulkactions">';
    echo '<select name="bulk_action">';
    echo '<option value="-1">Bulk actions</option>';
    echo '<option value="delete">Delete</option>';
    echo '</select>';
    echo '<input type="submit" id="doaction" class="button action" value="Apply" onclick="var action = document.querySelector(\'select[name=bulk_action]\').value; if(action == \'delete\') { return confirm(\'Are you sure you want to delete the selected enquiries?\'); } else if(action == \'-1\') { alert(\'Please select an action.\'); return false; }">';
    echo '</div></div>';

    echo '<table class="wp-list-table widefat fixed striped table-view-list">';
    echo '<thead><tr>';
    echo '<th class="manage-column column-cb check-column" style="width:2.2em;"><input type="checkbox" id="cb-select-all-1"></th>';
    echo '<th>Date</th><th>Project</th><th>Name</th><th>Closing Manager</th><th>Contact</th><th>Lead</th><th>Config</th><th>Budget</th><th>Source</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    if ($results) {
        foreach ($results as $row) {
            echo '<tr>';
            echo '<th scope="row" class="check-column"><input type="checkbox" name="enquiry_ids[]" value="' . esc_attr($row->id) . '"></th>';
            echo '<td>' . esc_html(date('d M Y', strtotime($row->date_visit))) . '</td>';
            
            $building = !empty($row->building_name) ? $row->building_name : 'Pearl Grace';
            echo '<td>' . esc_html($building) . '</td>';
            
            $edit_link = wp_nonce_url(admin_url('admin.php?page=crm-enquiries&action=edit&id=' . $row->id), 'edit_enquiry_' . $row->id);
            $delete_link = wp_nonce_url(admin_url('admin.php?page=crm-enquiries&action=delete&id=' . $row->id), 'delete_enquiry_' . $row->id);
            
            $actions = '<div class="row-actions">';
            $actions .= '<span class="edit"><a href="' . esc_url($edit_link) . '">Edit</a> | </span>';
            $actions .= '<span class="trash"><a href="' . esc_url($delete_link) . '" onclick="return confirm(\'Are you sure you want to delete this enquiry?\');" style="color: #a00;">Delete</a></span>';
            $actions .= '</div>';

            echo '<td><strong>' . esc_html($row->name) . '</strong><br><small>' . esc_html($row->occupation) . '</small>' . $actions . '</td>';
            
            $manager_name = '-';
            $has_manager = false;
            if (!empty($row->closing_manager_id)) {
                $manager_data = get_userdata($row->closing_manager_id);
                if ($manager_data) {
                    $manager_name = $manager_data->display_name;
                    $has_manager = true;
                }
            }
            echo '<td>';
            echo '<strong>' . esc_html($manager_name) . '</strong>';
            if ($has_manager) {
                $safe_row_json = esc_attr(wp_json_encode($row));
                echo '<br><button type="button" class="view-feedback-btn" data-enquiry="' . $safe_row_json . '" style="background: none; border: none; padding: 0; margin-top: 4px; color: #d4af37; font-weight: 600; font-size: 11px; cursor: pointer; text-decoration: none; outline: none; transition: color 0.2s;" onmouseover="this.style.color=\'#b5952f\'" onmouseout="this.style.color=\'#d4af37\'">View Feedback</button>';
            }
            echo '</td>';

            echo '<td> ' . esc_html($row->contact) . ' </td>';
            $status_class = 'status-none';
            $status_lbl = 'Not Rated';
            if (!empty($row->lead_status)) {
                $status_class = 'status-' . strtolower($row->lead_status);
                $status_lbl = $row->lead_status;
            }
            echo '<td><span class="status-pill ' . esc_attr($status_class) . '">' . esc_html($status_lbl) . '</span></td>';
            echo '<td>' . esc_html($row->configuration) . '</td>';
            echo '<td>' . esc_html($row->budget) . '</td>';
            
            $source_display = esc_html($row->source);
            if ($row->source === 'Reference' && !empty($row->reference_name)) {
                $source_display .= '<br><small>(' . esc_html($row->reference_name) . ')</small>';
            } elseif ($row->source === 'Channel Partner' && !empty($row->cp_name)) {
                $source_display .= '<br><small>(' . esc_html($row->cp_name) . ')</small>';
            }
            echo '<td>' . $source_display . '</td>';


            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="10">No enquiries found.</td></tr>';
    }
    echo '</tbody></table>';
    echo '</form>';
    echo '<script>
    document.getElementById("cb-select-all-1").addEventListener("click", function() {
        var checkboxes = document.querySelectorAll("input[name=\'enquiry_ids[]\']");
        for (var checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    });
    </script>';
    ?>
    <!-- Sliding Sidebar Drawer HTML, CSS & JS -->
    <style>
        :root {
            --crm-gold: #d4af37;
            --crm-gold-dark: #b5952f;
            --crm-bg: #f8f9fa;
            --crm-card: #ffffff;
            --crm-border: #e2e8f0;
            --crm-text: #1e293b;
            --crm-muted: #64748b;
            --crm-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            --crm-radius: 12px;
        }
        /* Modern Sliding Drawer */
        .crm-drawer-overlay {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 99998;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .crm-drawer-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .crm-drawer {
            position: fixed;
            top: 0;
            right: -700px;
            width: 650px;
            max-width: 90%;
            bottom: 0;
            background: #ffffff;
            box-shadow: -10px 0 30px rgba(0,0,0,0.1);
            z-index: 99999;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }
        .crm-drawer.active {
            transform: translateX(-700px);
        }
        .crm-drawer-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--crm-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
        }
        .crm-drawer-header h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--crm-text);
        }
        .crm-drawer-close {
            background: none;
            border: none;
            font-size: 28px;
            color: var(--crm-muted);
            cursor: pointer;
            line-height: 1;
        }
        .crm-drawer-close:hover {
            color: var(--crm-text);
        }
        .crm-drawer-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background: var(--crm-bg);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            box-sizing: border-box;
        }
        .crm-drawer-card {
            background: #ffffff;
            border-radius: var(--crm-radius);
            padding: 1.5rem;
            border: 1px solid var(--crm-border);
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .crm-drawer-card h3 {
            margin: 0 0 1rem 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--crm-gold-dark);
            border-bottom: 2px solid var(--crm-bg);
            padding-bottom: 8px;
            font-weight: 700;
        }
        .crm-feedback-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .crm-feedback-table tr {
            border-bottom: 1px solid var(--crm-border);
        }
        .crm-feedback-table tr:last-child {
            border-bottom: none;
        }
        .crm-feedback-table td {
            padding: 10px 8px;
            vertical-align: top;
            font-size: 13px;
        }
        .crm-feedback-table td.label-cell {
            font-weight: 600;
            color: var(--crm-muted);
            width: 35%;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.03em;
            padding-top: 12px;
        }
        .crm-feedback-table td.value-cell {
            color: var(--crm-text);
            font-weight: 500;
        }
        .status-pill {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            border: 1px solid;
        }
        .status-hot { background: #fef2f2; color: #ef4444; border-color: #fee2e2; }
        .status-warm { background: #fffbeb; color: #d97706; border-color: #fef3c7; }
        .status-cold { background: #f0f9ff; color: #0284c7; border-color: #e0f2fe; }
        .status-lost { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
        .status-none { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

        /* Timeline styles */
        .crm-timeline {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
            margin-top: 10px;
            border-left: 2px solid var(--crm-border);
        }
        .crm-timeline-item {
            position: relative;
            margin-bottom: 1.2rem;
        }
        .crm-timeline-item:last-child {
            margin-bottom: 0;
        }
        .crm-timeline-dot {
            position: absolute;
            left: calc(-1.5rem - 6px);
            top: 4px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--crm-gold);
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 2px var(--crm-gold);
        }
        .crm-timeline-content {
            background: var(--crm-bg);
            border: 1px solid var(--crm-border);
            border-radius: 8px;
            padding: 0.8rem 1rem;
        }
        .crm-timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.4rem;
            flex-wrap: wrap;
            gap: 8px;
        }
        .crm-timeline-sm {
            font-weight: 600;
            font-size: 12px;
            color: var(--crm-text);
        }
        .crm-timeline-date {
            font-size: 11px;
            color: var(--crm-muted);
            font-weight: 500;
        }
        .crm-timeline-remarks {
            font-size: 12px;
            color: #475569;
            line-height: 1.4;
            margin: 0;
            white-space: pre-wrap;
        }
        .crm-timeline-footer {
            font-size: 10px;
            color: var(--crm-muted);
            margin-top: 6px;
            font-weight: 500;
            text-align: right;
        }
    </style>

    <div class="crm-drawer-overlay" id="crm-feedback-drawer-overlay"></div>
    <div class="crm-drawer" id="crm-feedback-drawer">
        <div class="crm-drawer-header">
            <h2>Closing Manager Feedback</h2>
            <button type="button" class="crm-drawer-close" id="crm-feedback-drawer-close">&times;</button>
        </div>
        <div class="crm-drawer-body">
            <!-- Section 1: Feedback Overview -->
            <div class="crm-drawer-card">
                <h3>Overview</h3>
                <table class="crm-feedback-table">
                    <tr>
                        <td class="label-cell">Project</td>
                        <td class="value-cell" id="feedback-project" style="font-weight: 600;"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Client Name</td>
                        <td class="value-cell" id="feedback-client-name" style="font-weight: 700; font-size: 14px;"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Contact Info</td>
                        <td class="value-cell">
                            <a href="#" id="feedback-contact-link" style="color: var(--crm-gold-dark, #b5952f); text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;"></a>
                        </td>
                    </tr>
                    <tr>
                        <td class="label-cell">Closing Manager</td>
                        <td class="value-cell" id="feedback-provided-by"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Rating Status</td>
                        <td class="value-cell" id="feedback-lead-status"></td>
                    </tr>
                </table>
            </div>
            
            <!-- Section 2: Property Preferences -->
            <div class="crm-drawer-card">
                <h3>Property Preferences</h3>
                <table class="crm-feedback-table">
                    <tr>
                        <td class="label-cell">Preferred Unit</td>
                        <td class="value-cell" id="feedback-unit-like"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Preferred Floor</td>
                        <td class="value-cell" id="feedback-unit-floor"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Target Budget</td>
                        <td class="value-cell" id="feedback-unit-budget"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Feedback Details</td>
                        <td class="value-cell" id="feedback-details" style="line-height: 1.5; font-style: italic; color: #475569;"></td>
                    </tr>
                </table>
            </div>
            
            <!-- Section 3: Engagement & Financial Details -->
            <div class="crm-drawer-card">
                <h3>Engagement & Financials</h3>
                <table class="crm-feedback-table">
                    <tr>
                        <td class="label-cell">Visit Frequency</td>
                        <td class="value-cell" id="feedback-visit-type"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Visit Attended By</td>
                        <td class="value-cell" id="feedback-visit-attended"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Sourcing Manager</td>
                        <td class="value-cell" id="feedback-sourcing-manager"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Client Age</td>
                        <td class="value-cell" id="feedback-client-age"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Funding Source</td>
                        <td class="value-cell" id="feedback-funding-source"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">SOP Amount</td>
                        <td class="value-cell" id="feedback-sop-amount"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Ready Down Payment</td>
                        <td class="value-cell" id="feedback-ready-down"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Own Contribution</td>
                        <td class="value-cell" id="feedback-own-contribution"></td>
                    </tr>
                </table>
            </div>
            
            <!-- Section 4: Next Action Plan -->
            <div class="crm-drawer-card">
                <h3>Next Action Follow-Up</h3>
                <table class="crm-feedback-table" style="margin-bottom: 15px;">
                    <tr>
                        <td class="label-cell">Action Date</td>
                        <td class="value-cell" id="feedback-next-date" style="font-weight: 600; color: #1e293b;"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Sales Manager (S.M)</td>
                        <td class="value-cell" id="feedback-next-sm"></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Action Plan Remarks</td>
                        <td class="value-cell" id="feedback-next-remarks" style="line-height: 1.5; color: #475569;"></td>
                    </tr>
                </table>

                <!-- Follow-up Timeline History for Site Manager -->
                <div id="feedback-drawer-followup-timeline-container" style="border-top: 1px solid var(--crm-border); padding-top: 12px; display: none;">
                    <h4 style="font-size: 13px; font-weight: 600; color: var(--crm-gold); margin: 0 0 10px 0;">Follow-Up History Log</h4>
                    <div class="crm-timeline-list" id="feedback-drawer-followup-timeline"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('crm-feedback-drawer-overlay');
            const drawer = document.getElementById('crm-feedback-drawer');
            const closeBtn = document.getElementById('crm-feedback-drawer-close');
            
            const viewBtns = document.querySelectorAll('.view-feedback-btn');
            viewBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    let rawData = this.dataset.enquiry;
                    let data = {};
                    try {
                        data = JSON.parse(rawData);
                    } catch(e) {
                        console.error('Failed to parse enquiry data', e);
                        return;
                    }
                    
                    // Populate fields
                    document.getElementById('feedback-project').textContent = data.building_name || 'Pearl Grace';
                    document.getElementById('feedback-client-name').textContent = data.name || '-';
                    
                    const feedbackContactLink = document.getElementById('feedback-contact-link');
                    if (feedbackContactLink) {
                        if (data.contact) {
                            feedbackContactLink.href = 'tel:' + data.contact;
                            feedbackContactLink.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>${data.contact}`;
                        } else {
                            feedbackContactLink.removeAttribute('href');
                            feedbackContactLink.innerHTML = '-';
                        }
                    }
                    
                    document.getElementById('feedback-provided-by').textContent = data.feedback_by || 'Closing Manager';
                    
                    // Status Pill
                    const statusEl = document.getElementById('feedback-lead-status');
                    if (data.lead_status) {
                        const statusClass = 'status-' + data.lead_status.toLowerCase();
                        statusEl.innerHTML = `<span class="status-pill ${statusClass}">${data.lead_status}</span>`;
                    } else {
                        statusEl.innerHTML = `<span class="status-pill status-none">Not Rated</span>`;
                    }
                    
                    document.getElementById('feedback-unit-like').textContent = data.unit_like || '-';
                    document.getElementById('feedback-unit-floor').textContent = data.unit_floor || '-';
                    document.getElementById('feedback-unit-budget').textContent = data.unit_budget || '-';
                    document.getElementById('feedback-details').textContent = data.feedback_details || 'No feedback details entered.';
                    
                    document.getElementById('feedback-visit-type').textContent = data.visit_type || '-';
                    document.getElementById('feedback-visit-attended').textContent = data.visit_attended_by || '-';
                    document.getElementById('feedback-sourcing-manager').textContent = data.sourcing_manager || '-';
                    document.getElementById('feedback-client-age').textContent = data.client_age || '-';
                    document.getElementById('feedback-funding-source').textContent = data.funding_source || '-';
                    document.getElementById('feedback-sop-amount').textContent = data.sop_amount || '-';
                    document.getElementById('feedback-ready-down').textContent = data.ready_down_payment || '-';
                    document.getElementById('feedback-own-contribution').textContent = data.own_contribution || '-';
                    
                    // Date formatting
                    let nextDateDisplay = '-';
                    if (data.next_action_date && data.next_action_date !== '0000-00-00') {
                        try {
                            const dateObj = new Date(data.next_action_date);
                            if (!isNaN(dateObj.getTime())) {
                                const options = { day: '2-digit', month: 'short', year: 'numeric' };
                                nextDateDisplay = dateObj.toLocaleDateString('en-GB', options);
                            }
                        } catch(err) {
                            nextDateDisplay = data.next_action_date;
                        }
                    }
                    document.getElementById('feedback-next-date').textContent = nextDateDisplay;
                    document.getElementById('feedback-next-sm').textContent = data.s_m || '-';
                    document.getElementById('feedback-next-remarks').textContent = data.next_action_remarks || 'No action plan remarks.';
                    
                    // Fetch follow-up history for Site Manager drawer
                    const timelineContainer = document.getElementById('feedback-drawer-followup-timeline-container');
                    const timelineList = document.getElementById('feedback-drawer-followup-timeline');
                    
                    if (timelineContainer && timelineList) {
                        timelineList.innerHTML = '<div style="color:var(--crm-muted); font-size:12px; font-style:italic;">Loading follow-up history...</div>';
                        timelineContainer.style.display = 'block';
                        
                        fetch(`${ajaxurl}?action=get_client_followup_history&enquiry_id=${data.id}`)
                            .then(response => response.json())
                            .then(res => {
                                if (res.success && res.data.history && res.data.history.length > 0) {
                                    let html = '<div class="crm-timeline">';
                                    res.data.history.forEach(item => {
                                        html += `
                                            <div class="crm-timeline-item">
                                                <div class="crm-timeline-dot"></div>
                                                <div class="crm-timeline-content">
                                                    <div class="crm-timeline-header">
                                                        <span class="crm-timeline-sm">S.M: ${item.s_m}</span>
                                                        <span class="crm-timeline-date">${item.formatted_action_date}</span>
                                                    </div>
                                                    <p class="crm-timeline-remarks">${item.remarks}</p>
                                                    <div class="crm-timeline-footer">
                                                        Logged by: ${item.added_by} on ${item.formatted_created_at}
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                    });
                                    html += '</div>';
                                    timelineList.innerHTML = html;
                                } else {
                                    timelineContainer.style.display = 'none';
                                    timelineList.innerHTML = '';
                                }
                            })
                            .catch(err => {
                                console.error('Error fetching followup history:', err);
                                timelineList.innerHTML = '<div style="color:#ef4444; font-size:12px;">Failed to load follow-up history.</div>';
                            });
                    }

                    // Open drawer
                    overlay.classList.add('active');
                    drawer.classList.add('active');
                });
            });
            
            function closeFeedbackDrawer() {
                overlay.classList.remove('active');
                drawer.classList.remove('active');
            }
            
            closeBtn.addEventListener('click', closeFeedbackDrawer);
            overlay.addEventListener('click', closeFeedbackDrawer);
        });
    </script>
    <?php
    echo '</div>';
}

// ----------------------------------------------------
// Closing Manager Dashboard & Annotations Portal
// ----------------------------------------------------

function crm_closing_manager_page_html() {
    wp_die('Access denied.');
    return;

    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

    $where_clauses = array();
    $prepare_args = array();

    // Enforce role visibility
    if (!$is_admin) {
        $where_clauses[] = "closing_manager_id = %d";
        $prepare_args[] = $user->ID;
    }

    if (!empty($search)) {
        $where_clauses[] = "(name LIKE %s OR email LIKE %s OR contact LIKE %s)";
        $prepare_args[] = '%' . $search . '%';
        $prepare_args[] = '%' . $search . '%';
        $prepare_args[] = '%' . $search . '%';
    }

    if (!empty($status_filter)) {
        $where_clauses[] = "lead_status = %s";
        $prepare_args[] = $status_filter;
    }

    if (!empty($date_from)) {
        $where_clauses[] = "date_visit >= %s";
        $prepare_args[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_clauses[] = "date_visit <= %s";
        $prepare_args[] = $date_to;
    }

    $where = '';
    if (!empty($where_clauses)) {
        $where = " WHERE " . implode(' AND ', $where_clauses);
        if (!empty($prepare_args)) {
            $where = $wpdb->prepare($where, $prepare_args);
        }
    }

    $results = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY id DESC");

    ?>
    <div class="wrap crm-manager-wrap">
        <h1 class="wp-heading-inline">My Client Portfolios</h1>
        <hr class="wp-header-end">

        <!-- Custom Styling for Closing Manager Portal -->
        <style>
            :root {
                --crm-gold: #d4af37;
                --crm-gold-dark: #b5952f;
                --crm-bg: #f8f9fa;
                --crm-card: #ffffff;
                --crm-border: #e2e8f0;
                --crm-text: #1e293b;
                --crm-muted: #64748b;
                --crm-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                --crm-radius: 12px;
            }
            .crm-manager-wrap {
                margin: 20px 20px 0 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .crm-filter-bar {
                background: #ffffff;
                border: 1px solid var(--crm-border);
                border-radius: var(--crm-radius);
                padding: 1.5rem;
                margin: 20px 0;
                box-shadow: var(--crm-shadow);
                display: flex;
                flex-wrap: wrap;
                gap: 15px;
                align-items: flex-end;
            }
            .crm-filter-bar .filter-group {
                display: flex;
                flex-direction: column;
                min-width: 180px;
                flex-grow: 1;
            }
            .crm-filter-bar label {
                font-weight: 600;
                font-size: 13px;
                margin-bottom: 5px;
                color: var(--crm-text);
            }
            .crm-filter-bar select, .crm-filter-bar input {
                padding: 8px 12px;
                border: 1px solid var(--crm-border);
                border-radius: 6px;
                font-size: 14px;
                background-color: var(--crm-bg);
            }
            .crm-filter-bar select:focus, .crm-filter-bar input:focus {
                border-color: var(--crm-gold);
                outline: none;
                background: #fff;
            }
            .crm-btn-primary {
                background-color: var(--crm-gold) !important;
                border-color: var(--crm-gold) !important;
                color: #fff !important;
                font-weight: 600 !important;
                border-radius: 6px !important;
                padding: 5px 16px !important;
                height: 38px !important;
                cursor: pointer;
            }
            .crm-btn-primary:hover {
                background-color: var(--crm-gold-dark) !important;
                border-color: var(--crm-gold-dark) !important;
            }
            .crm-table {
                box-shadow: var(--crm-shadow) !important;
                border-radius: var(--crm-radius) !important;
                overflow: hidden !important;
                border: 1px solid var(--crm-border) !important;
            }
            .status-pill {
                padding: 4px 8px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                display: inline-block;
            }
            .status-hot { background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; }
            .status-warm { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
            .status-cold { background: #f0f9ff; color: #0284c7; border: 1px solid #e0f2fe; }
            .status-lost { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
            .status-none { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

            /* Modern Sliding Drawer */
            .crm-drawer-overlay {
                position: fixed;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                background: rgba(15, 23, 42, 0.4);
                backdrop-filter: blur(4px);
                z-index: 99998;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease;
            }
            .crm-drawer-overlay.active {
                opacity: 1;
                pointer-events: auto;
            }
            .crm-drawer {
                position: fixed;
                top: 0;
                right: -900px;
                width: 850px;
                max-width: 95%;
                bottom: 0;
                background: #ffffff;
                box-shadow: -10px 0 30px rgba(0,0,0,0.1);
                z-index: 99999;
                transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
                display: flex;
                flex-direction: column;
            }
            .crm-drawer.active {
                transform: translateX(-900px);
            }
            .crm-drawer-header {
                padding: 1.5rem;
                border-bottom: 1px solid var(--crm-border);
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #ffffff;
            }
            .crm-drawer-header h2 {
                margin: 0;
                font-size: 1.5rem;
                font-weight: 700;
                color: var(--crm-text);
            }
            .crm-drawer-close {
                background: none;
                border: none;
                font-size: 28px;
                color: var(--crm-muted);
                cursor: pointer;
                line-height: 1;
            }
            .crm-drawer-close:hover {
                color: var(--crm-text);
            }
            .crm-drawer-body {
                flex: 1;
                overflow-y: auto;
                padding: 1.5rem;
                background: var(--crm-bg);
                display: grid;
                grid-template-columns: 1fr 1.2fr;
                gap: 1.5rem;
            }
            .crm-drawer-column {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }
            .crm-drawer-card {
                background: #ffffff;
                border-radius: var(--crm-radius);
                padding: 1.5rem;
                border: 1px solid var(--crm-border);
                box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            }
            .crm-drawer-card h3 {
                margin: 0 0 1rem 0;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: var(--crm-gold-dark);
                border-bottom: 2px solid var(--crm-bg);
                padding-bottom: 8px;
            }
            .crm-info-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .crm-info-item {
                display: flex;
                flex-direction: column;
            }
            .crm-info-item label {
                font-size: 11px;
                color: var(--crm-muted);
                margin-bottom: 2px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .crm-info-item span {
                font-size: 14px;
                font-weight: 500;
                color: var(--crm-text);
            }
            .crm-form-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            .crm-form-full {
                grid-column: 1 / -1;
            }
            .crm-form-group {
                display: flex;
                flex-direction: column;
            }
            .crm-form-group label {
                font-size: 12px;
                font-weight: 600;
                color: var(--crm-text);
                margin-bottom: 4px;
            }
            .crm-form-group input, .crm-form-group select, .crm-form-group textarea {
                padding: 8px 10px;
                border: 1px solid var(--crm-border);
                border-radius: 6px;
                font-size: 13px;
                font-family: inherit;
            }
            .crm-form-group input:focus, .crm-form-group select:focus, .crm-form-group textarea:focus {
                border-color: var(--crm-gold);
                outline: none;
                box-shadow: 0 0 0 2px rgba(212, 175, 55, 0.1);
            }
            /* Pill button selector inside backend */
            .crm-pill-selector {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 3px;
            }
            .crm-pill-opt {
                padding: 5px 10px;
                border: 1px solid var(--crm-border);
                background: var(--crm-bg);
                border-radius: 6px;
                font-size: 12px;
                cursor: pointer;
                font-weight: 500;
                transition: all 0.2s ease;
            }
            .crm-pill-opt:hover {
                border-color: #cbd5e1;
            }
            .crm-pill-opt.active {
                border-color: var(--crm-gold);
                background-color: #fffbeb;
                color: var(--crm-gold-dark);
            }
            .crm-drawer-footer {
                padding: 1.2rem 1.5rem;
                border-top: 1px solid var(--crm-border);
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                background: #ffffff;
            }
            .crm-btn {
                padding: 8px 18px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .crm-btn-secondary {
                background: #e2e8f0;
                border: 1px solid #cbd5e1;
                color: #334155;
            }
            .crm-btn-secondary:hover {
                background: #cbd5e1;
            }
            .crm-toast {
                position: fixed;
                bottom: 25px;
                right: 25px;
                padding: 12px 24px;
                border-radius: 6px;
                color: white;
                font-weight: 600;
                box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                z-index: 999999;
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                pointer-events: none;
            }
            .crm-toast.active {
                opacity: 1;
                transform: translateY(0);
            }
            .crm-toast.success { background: #10b981; }
            .crm-toast.error { background: #ef4444; }

            .signature-preview-box {
                margin-top: 5px;
                border: 1px solid var(--crm-border);
                border-radius: 6px;
                padding: 5px;
                background: #f8fafc;
                text-align: center;
            }
             .signature-preview-box img {
                max-height: 80px;
                object-fit: contain;
                display: block;
                margin: 0 auto;
            }

            /* Timeline styles */
            .crm-timeline {
                position: relative;
                padding-left: 1.5rem;
                margin-bottom: 1.5rem;
                margin-top: 10px;
                border-left: 2px solid var(--crm-border);
            }
            .crm-timeline-item {
                position: relative;
                margin-bottom: 1.2rem;
            }
            .crm-timeline-item:last-child {
                margin-bottom: 0;
            }
            .crm-timeline-dot {
                position: absolute;
                left: calc(-1.5rem - 6px);
                top: 4px;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: var(--crm-gold);
                border: 2px solid #ffffff;
                box-shadow: 0 0 0 2px var(--crm-gold);
            }
            .crm-timeline-content {
                background: var(--crm-bg);
                border: 1px solid var(--crm-border);
                border-radius: 8px;
                padding: 0.8rem 1rem;
            }
            .crm-timeline-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.4rem;
                flex-wrap: wrap;
                gap: 8px;
            }
            .crm-timeline-sm {
                font-weight: 600;
                font-size: 12px;
                color: var(--crm-text);
            }
            .crm-timeline-date {
                font-size: 11px;
                color: var(--crm-muted);
                font-weight: 500;
            }
            .crm-timeline-remarks {
                font-size: 12px;
                color: #475569;
                line-height: 1.4;
                margin: 0;
                white-space: pre-wrap;
            }
            .crm-timeline-footer {
                font-size: 10px;
                color: var(--crm-muted);
                margin-top: 6px;
                font-weight: 500;
                text-align: right;
            }
        </style>

        <!-- Filters Form -->
        <form method="get" class="crm-filter-bar">
            <input type="hidden" name="page" value="crm-closing-manager-enquiries">
            
            <div class="filter-group">
                <label for="post-search-input">Search Client:</label>
                <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Name, contact...">
            </div>

            <div class="filter-group">
                <label for="status-filter">Status Rating:</label>
                <select name="status" id="status-filter">
                    <option value="">All Ratings</option>
                    <option value="Hot" <?php selected($status_filter, 'Hot'); ?>>Hot</option>
                    <option value="Warm" <?php selected($status_filter, 'Warm'); ?>>Warm</option>
                    <option value="Cold" <?php selected($status_filter, 'Cold'); ?>>Cold</option>
                    <option value="Gold" <?php selected($status_filter, 'Gold'); ?>>Gold</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="date_from">From Visit:</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($date_from); ?>">
            </div>

            <div class="filter-group">
                <label for="date_to">To Visit:</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($date_to); ?>">
            </div>

            <div>
                <input type="submit" class="crm-btn-primary" value="Apply Filters">
                <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)) : ?>
                    <a href="?page=crm-closing-manager-enquiries" class="button" style="height: 38px; line-height: 36px; border-radius: 6px;">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Listing Table Grid -->
        <table class="wp-list-table widefat fixed striped table-view-list crm-table">
            <thead>
                <tr>
                    <th style="width: 110px;">Date Visit</th>
                    <th>Client Name</th>
                    <th>Occupation</th>
                    <th>Configuration</th>
                    <th>Budget</th>
                    <th style="width: 100px;">Status Rating</th>
                    <th>Next Action Date</th>
                    <th>Next Action Remarks</th>
                    <th style="width: 120px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($results)) : ?>
                    <?php foreach ($results as $row) : ?>
                        <?php 
                        $status_class = 'status-none';
                        $status_lbl = 'Not Rated';
                        if (!empty($row->lead_status)) {
                            $status_class = 'status-' . strtolower($row->lead_status);
                            $status_lbl = $row->lead_status;
                        }
                        
                        $action_date_display = '-';
                        if (!empty($row->next_action_date) && $row->next_action_date !== '0000-00-00') {
                            $action_date_display = date('d M Y', strtotime($row->next_action_date));
                        }
                        
                        $remarks_display = !empty($row->next_action_remarks) ? esc_html($row->next_action_remarks) : '-';
                        if (strlen($remarks_display) > 30) {
                            $remarks_display = substr($remarks_display, 0, 27) . '...';
                        }
                        ?>
                        <tr id="client-row-<?php echo esc_attr($row->id); ?>">
                            <td><?php echo esc_html(date('d M Y', strtotime($row->date_visit))); ?></td>
                            <td>
                                <strong><?php echo esc_html($row->name); ?></strong><br>
                                <a href="tel:<?php echo esc_attr($row->contact); ?>" style="color: var(--crm-gold-dark, #b5952f); text-decoration: none; font-weight: 600; font-size: 11px; display: inline-flex; align-items: center; gap: 4px; margin-top: 2px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                    <?php echo esc_html($row->contact); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($row->occupation); ?></td>
                            <td><?php echo esc_html($row->configuration); ?></td>
                            <td><?php echo esc_html($row->budget); ?></td>
                            <td><span class="status-pill <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_lbl); ?></span></td>
                            <td class="col-action-date"><?php echo esc_html($action_date_display); ?></td>
                            <td class="col-action-remarks"><?php echo esc_html($remarks_display); ?></td>
                            <td style="text-align: center;">
                                <button type="button" class="button crm-btn-primary open-annotate-btn" data-client="<?php echo esc_attr(json_encode($row)); ?>">View & Annotate</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9">No clients found assigned to you.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Sliding Sidebar Drawer HTML -->
    <div class="crm-drawer-overlay" id="crm-drawer-overlay"></div>
    <div class="crm-drawer" id="crm-drawer">
        <div class="crm-drawer-header">
            <h2>Client Engagement Profile</h2>
            <button type="button" class="crm-drawer-close" id="crm-drawer-close">&times;</button>
        </div>
        <form id="crm-annotations-form">
            <?php wp_nonce_field('crm_save_sheet_nonce', 'crm_sheet_nonce'); ?>
            <input type="hidden" name="enquiry_id" id="form-enquiry-id">
            <input type="hidden" name="lead_status" id="form-lead-status">
            <input type="hidden" name="visit_type" id="form-visit-type">
            <input type="hidden" name="visit_attended_by" id="form-visit-attended-by">
            <input type="hidden" name="funding_source" id="form-funding-source">

            <div class="crm-drawer-body">
                <!-- Column Left: Read Only Visitor Profile -->
                <div class="crm-drawer-column">
                    <div class="crm-drawer-card">
                        <h3>Client Details (Read Only)</h3>
                        <div class="crm-info-grid">
                            <div class="crm-info-item">
                                <label>Date of Visit</label>
                                <span id="info-date-visit"></span>
                            </div>
                            <div class="crm-info-item">
                                <label>Project</label>
                                <span id="info-project" style="font-weight: 600;"></span>
                            </div>
                            <div class="crm-info-item">
                                <label>Full Name</label>
                                <span id="info-name" style="font-weight: 700;"></span>
                            </div>
                            <div class="crm-info-item">
                                <label>Contact Info</label>
                                <a href="#" id="info-contact-link" style="color: var(--crm-gold-dark); text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;"></a>
                            </div>
                            <div class="crm-info-item">
                                <label>Residence Address</label>
                                <span id="info-residence"></span>
                            </div>
                            <div class="crm-info-item">
                                <label>Occupation</label>
                                <span id="info-occupation"></span>
                            </div>
                            <div class="crm-info-item">
                                <label>Configuration Needed</label>
                                <span id="info-configuration"></span>
                            </div>
                            <div class="crm-info-item">
                                <label>Stated Budget</label>
                                <span id="info-budget"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column Right: Closing Manager Actions Sheet -->
                <div class="crm-drawer-column">
                    <div class="crm-drawer-card">
                        <h3>Closing Manager Assessment</h3>
                        
                        <div class="crm-form-full" style="margin-bottom: 12px;">
                            <label style="font-size: 12px; font-weight: 600; color: var(--crm-text);">Client Rating Status:</label>
                            <div class="crm-pill-selector" id="rating-pills">
                                <span class="crm-pill-opt" data-value="Hot" style="color: #ef4444;">Hot</span>
                                <span class="crm-pill-opt" data-value="Warm" style="color: #d97706;">Warm</span>
                                <span class="crm-pill-opt" data-value="Cold" style="color: #0284c7;">Cold</span>
                                <span class="crm-pill-opt" data-value="Lost" style="color: #64748b;">Lost</span>
                            </div>
                        </div>

                        <div class="crm-form-grid">
                            <div class="crm-form-group">
                                <label for="form-sourcing-manager">Sourcing Manager</label>
                                <input type="text" name="sourcing_manager" id="form-sourcing-manager" placeholder="e.g. Rahul Sharma">
                            </div>
                            <div class="crm-form-group">
                                <label for="form-client-age">Client Age</label>
                                <input type="text" name="client_age" id="form-client-age" placeholder="e.g. 35">
                            </div>
                            
                            <div class="crm-form-full">
                                <label>Visit Frequency</label>
                                <div class="crm-pill-selector" id="visit-type-pills">
                                    <span class="crm-pill-opt" data-value="First visit">First Visit</span>
                                    <span class="crm-pill-opt" data-value="Revisit">Revisit</span>
                                    <span class="crm-pill-opt" data-value="Multi visit">Multi Visit</span>
                                </div>
                            </div>

                            <div class="crm-form-full" style="margin-top: 6px;">
                                <label>Visit Attended By</label>
                                <div class="crm-pill-selector" id="visit-attended-pills">
                                    <span class="crm-pill-opt" data-value="Husband">Husband</span>
                                    <span class="crm-pill-opt" data-value="Wife">Wife</span>
                                    <span class="crm-pill-opt" data-value="Father">Father</span>
                                    <span class="crm-pill-opt" data-value="Mother">Mother</span>
                                    <span class="crm-pill-opt" data-value="Brother">Brother</span>
                                </div>
                            </div>

                            <div class="crm-form-full" style="margin-top: 6px;">
                                <label>Funding Options</label>
                                <div class="crm-pill-selector" id="funding-source-pills">
                                    <span class="crm-pill-opt" data-value="SOP">SOP</span>
                                    <span class="crm-pill-opt" data-value="Bank Loan">Bank Loan</span>
                                    <span class="crm-pill-opt" data-value="Loan Funding">Loan Funding</span>
                                </div>
                            </div>

                            <div class="crm-form-group">
                                <label for="form-sop-amount">SOP Amount</label>
                                <input type="text" name="sop_amount" id="form-sop-amount" placeholder="e.g. 10 Lakhs">
                            </div>
                            <div class="crm-form-group">
                                <label for="form-ready-down">Ready Down Payment</label>
                                <input type="text" name="ready_down_payment" id="form-ready-down" placeholder="e.g. 15 Lakhs">
                            </div>
                            <div class="crm-form-group crm-form-full">
                                <label for="form-own-contribution">Own Contribution</label>
                                <input type="text" name="own_contribution" id="form-own-contribution" placeholder="e.g. 20 Lakhs">
                            </div>
                        </div>
                    </div>

                    <div class="crm-drawer-card">
                        <h3>Property Preferences & Feedback</h3>
                        <div class="crm-form-grid">
                            <div class="crm-form-group">
                                <label for="form-unit-like">Unit Like</label>
                                <input type="text" name="unit_like" id="form-unit-like" placeholder="e.g. 302">
                            </div>
                            <div class="crm-form-group">
                                <label for="form-unit-floor">Floor</label>
                                <input type="text" name="unit_floor" id="form-unit-floor" placeholder="e.g. 3rd Floor">
                            </div>
                            <div class="crm-form-group crm-form-full">
                                <label for="form-unit-budget">Target Unit Budget</label>
                                <input type="text" name="unit_budget" id="form-unit-budget" placeholder="e.g. 85 Lakhs">
                            </div>
                            <div class="crm-form-group crm-form-full">
                                <label for="form-feedback-details">Feedback Details</label>
                                <textarea name="feedback_details" id="form-feedback-details" rows="2" placeholder="Details..."></textarea>
                            </div>
                        </div>
                    </div>

                     <div class="crm-drawer-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--crm-bg); padding-bottom: 8px; margin-bottom: 1rem;">
                            <h3 style="margin: 0; border: none; padding: 0; color: var(--crm-gold-dark);">Schedule Next Action Follow-Up</h3>
                            <button type="button" id="drawer-btn-add-action" class="crm-btn-secondary" style="font-size: 11px; padding: 4px 8px; border-radius: 4px; display: flex; align-items: center; gap: 4px; border: 1px solid var(--crm-gold); color: var(--crm-gold-dark); background: #fffbeb; cursor: pointer; font-weight: 600;">
                                <span style="font-size: 12px; font-weight: bold;">+</span> Add Action Details
                            </button>
                        </div>
                        <div class="crm-form-grid" style="margin-bottom: 15px;">
                            <div class="crm-form-group">
                                <label for="form-next-sm">Sales/Sourcing Manager (S.M)</label>
                                <input type="text" name="s_m" id="form-next-sm" placeholder="Manager Name">
                            </div>
                            <div class="crm-form-group">
                                <label for="form-next-date">Next Action Date</label>
                                <input type="date" name="next_action_date" id="form-next-date">
                            </div>
                            <div class="crm-form-group crm-form-full">
                                <label for="form-next-remarks">Action Plan Remarks</label>
                                <textarea name="next_action_remarks" id="form-next-remarks" rows="2" placeholder="Action remarks..."></textarea>
                            </div>
                        </div>

                        <!-- Follow-up Timeline History for Closing Manager -->
                        <div id="drawer-followup-timeline-container" style="border-top: 1px solid var(--crm-border); padding-top: 12px; display: none;">
                            <h4 style="font-size: 13px; font-weight: 600; color: var(--crm-gold); margin: 0 0 10px 0;">Follow-Up History Log</h4>
                            <div class="crm-timeline-list" id="drawer-followup-timeline"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="crm-drawer-footer">
                <button type="button" class="crm-btn crm-btn-secondary" id="crm-btn-cancel">Cancel</button>
                <button type="submit" class="crm-btn crm-btn-primary" id="crm-btn-save">
                    <span id="btn-save-text">Save Annotation Sheet</span>
                </button>
            </div>
        </form>
    </div>

    <div class="crm-toast" id="crm-toast"></div>

    <!-- Interactive Drawer Logic JS Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('crm-drawer-overlay');
            const drawer = document.getElementById('crm-drawer');
            const closeBtn = document.getElementById('crm-drawer-close');
            const cancelBtn = document.getElementById('crm-btn-cancel');
            const form = document.getElementById('crm-annotations-form');

            // Handle Pill button toggle selectors
            function setupDrawerPills(containerId, hiddenInputId) {
                const container = document.getElementById(containerId);
                const hiddenInput = document.getElementById(hiddenInputId);
                if (!container || !hiddenInput) return;

                const pills = container.querySelectorAll('.crm-pill-opt');
                pills.forEach(pill => {
                    pill.addEventListener('click', function() {
                        pills.forEach(p => p.classList.remove('active'));
                        this.classList.add('active');
                        hiddenInput.value = this.dataset.value;
                    });
                });
            }

            setupDrawerPills('rating-pills', 'form-lead-status');
            setupDrawerPills('visit-type-pills', 'form-visit-type');
            setupDrawerPills('visit-attended-pills', 'form-visit-attended-by');
            setupDrawerPills('funding-source-pills', 'form-funding-source');

            // Set Pill visual active state based on value
            function setPillActiveState(containerId, value) {
                const container = document.getElementById(containerId);
                if (!container) return;
                const pills = container.querySelectorAll('.crm-pill-opt');
                pills.forEach(pill => {
                    if (pill.dataset.value === value) {
                        pill.classList.add('active');
                    } else {
                        pill.classList.remove('active');
                    }
                });
            }

            // Open Drawer
            const openButtons = document.querySelectorAll('.open-annotate-btn');
            openButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const client = JSON.parse(this.dataset.client);

                    // 1. Populate Read-Only columns
                    document.getElementById('info-date-visit').textContent = client.date_visit;
                    document.getElementById('info-project').textContent = client.building_name || 'Pearl Grace';
                    document.getElementById('info-name').textContent = client.name;
                    const contactLink = document.getElementById('info-contact-link');
                    if (contactLink) {
                        contactLink.href = 'tel:' + client.contact;
                        contactLink.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>${client.contact}`;
                    }
                    document.getElementById('info-residence').textContent = client.residence || '-';
                    document.getElementById('info-occupation').textContent = client.occupation || '-';
                    document.getElementById('info-configuration').textContent = client.configuration || '-';
                    document.getElementById('info-budget').textContent = client.budget || '-';

                    // 2. Populate form fields
                    document.getElementById('form-enquiry-id').value = client.id;
                    document.getElementById('form-sourcing-manager').value = client.sourcing_manager || '';
                    document.getElementById('form-client-age').value = client.client_age || '';
                    document.getElementById('form-sop-amount').value = client.sop_amount || '';
                    document.getElementById('form-ready-down').value = client.ready_down_payment || '';
                    document.getElementById('form-own-contribution').value = client.own_contribution || '';
                    document.getElementById('form-unit-like').value = client.unit_like || '';
                    document.getElementById('form-unit-floor').value = client.unit_floor || '';
                    document.getElementById('form-unit-budget').value = client.unit_budget || '';
                    document.getElementById('form-feedback-by').value = client.feedback_by || '';
                    document.getElementById('form-feedback-details').value = client.feedback_details || '';
                    document.getElementById('form-next-sm').value = client.s_m || '';
                    document.getElementById('form-next-date').value = (client.next_action_date && client.next_action_date !== '0000-00-00') ? client.next_action_date : '';
                    document.getElementById('form-next-remarks').value = client.next_action_remarks || '';

                    // Set hidden input values
                    document.getElementById('form-lead-status').value = client.lead_status || '';
                    document.getElementById('form-visit-type').value = client.visit_type || '';
                    document.getElementById('form-visit-attended-by').value = client.visit_attended_by || '';
                    document.getElementById('form-funding-source').value = client.funding_source || '';

                    // Trigger active pills
                    setPillActiveState('rating-pills', client.lead_status);
                    setPillActiveState('visit-type-pills', client.visit_type);
                    setPillActiveState('visit-attended-pills', client.visit_attended_by);
                    setPillActiveState('funding-source-pills', client.funding_source);

                    // Fetch follow-up history for Closing Manager drawer
                    const timelineContainer = document.getElementById('drawer-followup-timeline-container');
                    const timelineList = document.getElementById('drawer-followup-timeline');
                    
                    if (timelineContainer && timelineList) {
                        timelineList.innerHTML = '<div style="color:var(--crm-muted); font-size:12px; font-style:italic;">Loading follow-up history...</div>';
                        timelineContainer.style.display = 'block';
                        
                        fetch(`${ajaxurl}?action=get_client_followup_history&enquiry_id=${client.id}`)
                            .then(response => response.json())
                            .then(res => {
                                if (res.success && res.data.history && res.data.history.length > 0) {
                                    let html = '<div class="crm-timeline">';
                                    res.data.history.forEach(item => {
                                        html += `
                                            <div class="crm-timeline-item">
                                                <div class="crm-timeline-dot"></div>
                                                <div class="crm-timeline-content">
                                                    <div class="crm-timeline-header">
                                                        <span class="crm-timeline-sm">S.M: ${item.s_m}</span>
                                                        <span class="crm-timeline-date">${item.formatted_action_date}</span>
                                                    </div>
                                                    <p class="crm-timeline-remarks">${item.remarks}</p>
                                                    <div class="crm-timeline-footer">
                                                        Logged by: ${item.added_by} on ${item.formatted_created_at}
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                    });
                                    html += '</div>';
                                    timelineList.innerHTML = html;
                                } else {
                                    timelineContainer.style.display = 'none';
                                    timelineList.innerHTML = '';
                                }
                            })
                            .catch(err => {
                                console.error('Error fetching followup history:', err);
                                timelineList.innerHTML = '<div style="color:#ef4444; font-size:12px;">Failed to load follow-up history.</div>';
                            });
                    }

                    // Open visual drawers
                    overlay.classList.add('active');
                    drawer.classList.add('active');
                });
            });

            // Close Drawer function
            function closeDrawer() {
                overlay.classList.remove('active');
                drawer.classList.remove('active');
            }

            closeBtn.addEventListener('click', closeDrawer);
            cancelBtn.addEventListener('click', closeDrawer);
            overlay.addEventListener('click', closeDrawer);

            // Add action details listener for the drawer
            const drawerBtnAddAction = document.getElementById('drawer-btn-add-action');
            if (drawerBtnAddAction) {
                drawerBtnAddAction.addEventListener('click', function(e) {
                    e.preventDefault();
                    const dateInput = document.getElementById('form-next-date');
                    const remarksInput = document.getElementById('form-next-remarks');
                    if (dateInput && remarksInput) {
                        dateInput.value = '';
                        remarksInput.value = '';
                        dateInput.focus();
                        showToast('Previous plan scheduled for archiving. You can now type the new action plan details.', 'success');
                    }
                });
            }

            // Toast Alerts
            function showToast(msg, type) {
                const toast = document.getElementById('crm-toast');
                toast.textContent = msg;
                toast.className = 'crm-toast ' + type + ' active';
                setTimeout(() => {
                    toast.classList.remove('active');
                }, 4000);
            }

            // AJAX Form Submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const saveBtn = document.getElementById('crm-btn-save');
                const btnText = document.getElementById('btn-save-text');
                btnText.textContent = 'Saving...';
                saveBtn.disabled = true;

                const formData = new FormData(form);
                formData.append('action', 'save_closing_manager_sheet');
                formData.append('nonce', document.getElementById('crm_sheet_nonce').value);

                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    btnText.textContent = 'Save Annotation Sheet';
                    saveBtn.disabled = false;

                    if (data.success) {
                        showToast(data.data.message, 'success');
                        closeDrawer();

                        // Dynamic UI Row updates
                        const enquiryId = document.getElementById('form-enquiry-id').value;
                        const row = document.getElementById('client-row-' + enquiryId);
                        if (row) {
                            // Update lead status pill
                            const statusVal = document.getElementById('form-lead-status').value;
                            const statusPill = row.querySelector('.status-pill');
                            if (statusPill) {
                                statusPill.className = 'status-pill status-' + statusVal.toLowerCase();
                                statusPill.textContent = statusVal ? statusVal : 'Not Rated';
                            }

                            // Update next action date
                            const nextDate = document.getElementById('form-next-date').value;
                            const dateCol = row.querySelector('.col-action-date');
                            if (dateCol) {
                                if (nextDate) {
                                    const parsedDate = new Date(nextDate);
                                    const options = { day: '2-digit', month: 'short', year: 'numeric' };
                                    dateCol.textContent = parsedDate.toLocaleDateString('en-GB', options);
                                } else {
                                    dateCol.textContent = '-';
                                }
                            }

                            // Update next action remarks
                            const nextRemarks = document.getElementById('form-next-remarks').value;
                            const remarksCol = row.querySelector('.col-action-remarks');
                            if (remarksCol) {
                                remarksCol.textContent = nextRemarks ? (nextRemarks.length > 30 ? nextRemarks.substring(0, 27) + '...' : nextRemarks) : '-';
                            }

                            // Update client data attribute to cache changes
                            const openBtn = row.querySelector('.open-annotate-btn');
                            if (openBtn) {
                                const currentClient = JSON.parse(openBtn.dataset.client);
                                
                                // Merge modifications
                                currentClient.lead_status = statusVal;
                                currentClient.sourcing_manager = document.getElementById('form-sourcing-manager').value;
                                currentClient.client_age = document.getElementById('form-client-age').value;
                                currentClient.visit_type = document.getElementById('form-visit-type').value;
                                currentClient.visit_attended_by = document.getElementById('form-visit-attended-by').value;
                                currentClient.funding_source = document.getElementById('form-funding-source').value;
                                currentClient.sop_amount = document.getElementById('form-sop-amount').value;
                                currentClient.ready_down_payment = document.getElementById('form-ready-down').value;
                                currentClient.own_contribution = document.getElementById('form-own-contribution').value;
                                currentClient.unit_like = document.getElementById('form-unit-like').value;
                                currentClient.unit_floor = document.getElementById('form-unit-floor').value;
                                currentClient.unit_budget = document.getElementById('form-unit-budget').value;
                                currentClient.feedback_by = document.getElementById('form-feedback-by').value;
                                currentClient.feedback_details = document.getElementById('form-feedback-details').value;
                                currentClient.s_m = document.getElementById('form-next-sm').value;
                                currentClient.next_action_date = nextDate;
                                currentClient.next_action_remarks = nextRemarks;

                                openBtn.dataset.client = JSON.stringify(currentClient);
                            }
                        }
                    } else {
                        showToast(data.data.message || 'An error occurred.', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    btnText.textContent = 'Save Annotation Sheet';
                    saveBtn.disabled = false;
                    showToast('A network error occurred.', 'error');
                });
            });
        });
    </script>
    <?php
}

// ----------------------------------------------------
// AJAX Save Handler for Closing Manager Sheet
// ----------------------------------------------------

add_action('wp_ajax_save_closing_manager_sheet', 'crm_handle_save_closing_manager_sheet');
function crm_handle_save_closing_manager_sheet() {
    // Security nonces validation
    check_ajax_referer('crm_save_sheet_nonce', 'nonce');
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Unauthorized. Session expired.'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'crm_enquiries';
    $id = intval($_POST['enquiry_id']);

    $enquiry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    if (!$enquiry) {
        wp_send_json_error(array('message' => 'Client record not found.'));
    }

    // Role safety verification
    $user = wp_get_current_user();
    if (!current_user_can('manage_options') && intval($enquiry->closing_manager_id) !== $user->ID) {
        wp_send_json_error(array('message' => 'Access denied. You are not authorized to update this client.'));
    }

    // Detect if follow-up changed, and archive previous one
    $s_m_new = sanitize_text_field($_POST['s_m']);
    $date_new = !empty($_POST['next_action_date']) ? sanitize_text_field($_POST['next_action_date']) : '0000-00-00';
    $remarks_new = sanitize_textarea_field($_POST['next_action_remarks']);

    $has_old_followup = !empty($enquiry->s_m) || ($enquiry->next_action_date && $enquiry->next_action_date !== '0000-00-00') || !empty($enquiry->next_action_remarks);
    $changed = ($s_m_new !== $enquiry->s_m) || ($date_new !== $enquiry->next_action_date) || ($remarks_new !== $enquiry->next_action_remarks);

    if ($has_old_followup && $changed) {
        $followups_table = $wpdb->prefix . 'crm_followups';
        $wpdb->insert(
            $followups_table,
            array(
                'enquiry_id' => $id,
                'created_at' => current_time('mysql'),
                's_m' => $enquiry->s_m,
                'action_date' => $enquiry->next_action_date,
                'remarks' => $enquiry->next_action_remarks,
                'added_by' => $user->display_name
            )
        );
    }

    $data = array(
        'lead_status' => sanitize_text_field($_POST['lead_status']),
        'visit_type' => sanitize_text_field($_POST['visit_type']),
        's_m' => sanitize_text_field($_POST['s_m']),
        'next_action_date' => !empty($_POST['next_action_date']) ? sanitize_text_field($_POST['next_action_date']) : '0000-00-00',
        'next_action_remarks' => sanitize_textarea_field($_POST['next_action_remarks']),
    );

    $can_override = current_user_can('manage_options') || in_array('crm_site_head', (array) $user->roles) || in_array('crm_site_head_master', (array) $user->roles);

    if (empty($enquiry->assessment_saved) || $can_override) {
        $data['sourcing_manager'] = sanitize_text_field($_POST['sourcing_manager']);
        $data['client_age'] = sanitize_text_field($_POST['client_age']);
        $data['visit_attended_by'] = sanitize_text_field($_POST['visit_attended_by']);
        $data['funding_source'] = sanitize_text_field($_POST['funding_source']);
        $data['sop_amount'] = sanitize_text_field($_POST['sop_amount']);
        $data['ready_down_payment'] = sanitize_text_field($_POST['ready_down_payment']);
        $data['own_contribution'] = sanitize_text_field($_POST['own_contribution']);
        $data['unit_like'] = sanitize_text_field($_POST['unit_like']);
        $data['unit_floor'] = sanitize_text_field($_POST['unit_floor']);
        $data['unit_budget'] = sanitize_text_field($_POST['unit_budget']);
        $data['feedback_by'] = $user->display_name;
        $data['feedback_details'] = sanitize_textarea_field($_POST['feedback_details']);
        $data['assessment_saved'] = 1;
    }

    $updated = $wpdb->update($table_name, $data, array('id' => $id));

    if ($updated !== false) {
        wp_send_json_success(array('message' => 'Client annotations saved successfully!'));
    } else {
        wp_send_json_error(array('message' => 'Failed to save annotation sheet.'));
    }
}

// ----------------------------------------------------
// AJAX Get Client Follow-Up History
// ----------------------------------------------------

add_action('wp_ajax_get_client_followup_history', 'crm_handle_get_client_followup_history');
function crm_handle_get_client_followup_history() {
    // Check user permissions
    if (!current_user_can('read')) {
        wp_send_json_error(array('message' => 'Unauthorized.'));
    }

    global $wpdb;
    $enquiry_id = isset($_GET['enquiry_id']) ? intval($_GET['enquiry_id']) : 0;
    if (!$enquiry_id) {
        wp_send_json_error(array('message' => 'Invalid client ID.'));
    }

    $followups_table = $wpdb->prefix . 'crm_followups';
    
    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $followups_table WHERE enquiry_id = %d ORDER BY created_at DESC",
        $enquiry_id
    ));

    $formatted_history = array();
    foreach ($history as $item) {
        $dt = new DateTime($item->created_at, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));

        $formatted_history[] = array(
            'id' => $item->id,
            's_m' => esc_html($item->s_m),
            'action_date' => $item->action_date,
            'formatted_action_date' => date('d M Y', strtotime($item->action_date)),
            'remarks' => esc_html($item->remarks),
            'added_by' => esc_html($item->added_by),
            'created_at' => $item->created_at,
            'formatted_created_at' => $dt->format('d M Y, h:i A'),
        );
    }

    wp_send_json_success(array('history' => $formatted_history));
}

// Manage Projects HTML Callback
function crm_projects_page_html() {
    if (!current_user_can('manage_options') && !crm_is_site_head_master()) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crm_project_action']) && check_admin_referer('crm_save_project_nonce')) {
        $projects = crm_get_projects();
        $action = sanitize_text_field($_POST['crm_project_action']);

        if ($action === 'add' || $action === 'edit') {
            $name = sanitize_text_field($_POST['project_name']);
            $logo = esc_url_raw($_POST['project_logo']);
            $budgets_raw = sanitize_text_field($_POST['project_budgets']);
            $budgets = array_map('trim', explode(',', $budgets_raw));
            $budgets = array_filter($budgets); // Remove empty

            if (empty($budgets)) {
                $budgets = array("70 L to 85 L", "85 L to 1 CR", "1 CR to 1.25 CR");
            }

            if ($action === 'add') {
                $projects[] = array(
                    'name' => $name,
                    'logo' => $logo,
                    'budget_ranges' => array_values($budgets)
                );
            } else if ($action === 'edit') {
                $index = intval($_POST['project_index']);
                if (isset($projects[$index])) {
                    $projects[$index]['name'] = $name;
                    $projects[$index]['logo'] = $logo;
                    $projects[$index]['budget_ranges'] = array_values($budgets);
                }
            }
            update_option('crm_projects', $projects);
            echo '<div class="updated"><p>Project saved successfully.</p></div>';
        } elseif ($action === 'delete') {
            $index = intval($_POST['project_index']);
            if (isset($projects[$index])) {
                unset($projects[$index]);
                $projects = array_values($projects); // Re-index
                update_option('crm_projects', $projects);
                echo '<div class="updated"><p>Project deleted successfully.</p></div>';
            }
        }
    }

    $projects = crm_get_projects();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Manage Projects & Budgets</h1>
        <hr class="wp-header-end">

        <div style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
            <!-- Add/Edit Form -->
            <div style="flex: 1; min-width: 300px; max-width: 400px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 id="form-title" style="margin-top: 0;">Add New Project</h2>
                <form method="POST">
                    <?php wp_nonce_field('crm_save_project_nonce'); ?>
                    <input type="hidden" name="crm_project_action" id="crm_project_action" value="add">
                    <input type="hidden" name="project_index" id="project_index" value="">

                    <p>
                        <label for="project_name" style="font-weight: 600;">Project Name</label><br>
                        <input type="text" name="project_name" id="project_name" class="regular-text" style="width: 100%; margin-top: 5px;" required>
                    </p>
                    <p>
                        <label for="project_logo" style="font-weight: 600;">Logo URL</label><br>
                        <input type="url" name="project_logo" id="project_logo" class="regular-text" style="width: 100%; margin-top: 5px;" required>
                        <small>Provide an absolute URL to the project logo image.</small>
                    </p>
                    <p>
                        <label for="project_budgets" style="font-weight: 600;">Budget Ranges (Comma Separated)</label><br>
                        <textarea name="project_budgets" id="project_budgets" rows="4" style="width: 100%; margin-top: 5px;" required></textarea>
                        <small>e.g. 70 L to 85 L, 85 L to 1 CR, 1 CR to 1.25 CR</small>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary" id="submit_btn">Add Project</button>
                        <button type="button" class="button" id="cancel_btn" style="display:none;" onclick="resetForm()">Cancel Edit</button>
                    </p>
                </form>
            </div>

            <!-- List Projects -->
            <div style="flex: 2; min-width: 400px;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Logo</th>
                            <th>Project Name</th>
                            <th>Budget Ranges</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr><td colspan="4">No projects found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($projects as $index => $project): ?>
                                <tr>
                                    <td><img src="<?php echo esc_url($project['logo']); ?>" style="max-width: 50px; max-height: 50px;"></td>
                                    <td><strong><?php echo esc_html($project['name']); ?></strong></td>
                                    <td>
                                        <?php 
                                        if (isset($project['budget_ranges']) && is_array($project['budget_ranges'])) {
                                            foreach ($project['budget_ranges'] as $budget) {
                                                echo '<span style="display:inline-block; background:#e2e8f0; padding:2px 6px; border-radius:4px; font-size:11px; margin:2px;">' . esc_html($budget) . '</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small" onclick="editProject(<?php echo $index; ?>, '<?php echo esc_js($project['name']); ?>', '<?php echo esc_js($project['logo']); ?>', '<?php echo esc_js(implode(', ', $project['budget_ranges'])); ?>')">Edit</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this project?');">
                                            <?php wp_nonce_field('crm_save_project_nonce'); ?>
                                            <input type="hidden" name="crm_project_action" value="delete">
                                            <input type="hidden" name="project_index" value="<?php echo $index; ?>">
                                            <button type="submit" class="button button-small button-link-delete" style="color: #a00;">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function editProject(index, name, logo, budgets) {
            document.getElementById('form-title').innerText = 'Edit Project';
            document.getElementById('crm_project_action').value = 'edit';
            document.getElementById('project_index').value = index;
            document.getElementById('project_name').value = name;
            document.getElementById('project_logo').value = logo;
            document.getElementById('project_budgets').value = budgets;
            document.getElementById('submit_btn').innerText = 'Update Project';
            document.getElementById('cancel_btn').style.display = 'inline-block';
        }

        function resetForm() {
            document.getElementById('form-title').innerText = 'Add New Project';
            document.getElementById('crm_project_action').value = 'add';
            document.getElementById('project_index').value = '';
            document.getElementById('project_name').value = '';
            document.getElementById('project_logo').value = '';
            document.getElementById('project_budgets').value = '';
            document.getElementById('submit_btn').innerText = 'Add Project';
            document.getElementById('cancel_btn').style.display = 'none';
        }
    </script>
    <?php
}
