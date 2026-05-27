<?php
// DB Setup
function crm_create_enquiries_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'crm_enquiries';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
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
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    add_option('crm_db_version', '1.0');
}
add_action('after_setup_theme', 'crm_create_enquiries_table');

// Setup Custom Roles
function crm_setup_client_role() {
    // Remove the old role if present
    remove_role('crm_client');

    add_role(
        'site_manager',
        'Site Manager',
        array(
            'read' => true,
            'view_crm_enquiries' => true
        )
    );

    add_role(
        'crm_closing_manager',
        'Closing Manager',
        array(
            'read' => true
        )
    );

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
    if ((in_array('site_manager', $user->roles) || in_array('crm_closing_manager', $user->roles)) && !current_user_can('manage_options')) {
        remove_menu_page('index.php'); // Hide Dashboard
        remove_menu_page('profile.php'); // Hide Profile
    }
}
add_action('admin_menu', 'crm_restrict_client_menu', 999);

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
        'signature' => wp_kses_post($_POST['signature']), // base64 image
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

    $user = wp_get_current_user();
    if (in_array('crm_closing_manager', $user->roles) || current_user_can('manage_options')) {
        add_menu_page(
            'My Clients',
            'My Clients',
            'read',
            'crm-closing-manager-enquiries',
            'crm_closing_manager_page_html',
            'dashicons-groups',
            20
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
            fputcsv($output, array(
                'ID', 'Created At', 'Date Visit', 'Name', 'Contact', 'Email', 
                'Residence', 'Occupation', 'Company Name', 'Company Location', 
                'Configuration', 'Budget', 'Source', 'Reference Name', 
                'Channel Partner Name', 'Channel Partner Contact', 'Signature', 'Closing Manager',
                'Status Rating', 'Sourcing Manager', 'Client Age', 'Visit Frequency',
                'Visit Attended By', 'Funding Option', 'SOP Amount', 'Ready Down Payment',
                'Own Contribution', 'Preferred Unit', 'Preferred Floor', 'Preferred Budget',
                'Feedback By', 'Feedback Details', 'Next Action S.M', 'Next Action Date', 'Next Action Remarks'
            ));
            foreach ($results as $row) {
                $manager_name = 'None';
                if (!empty($row['closing_manager_id'])) {
                    $manager_data = get_userdata($row['closing_manager_id']);
                    if ($manager_data) {
                        $manager_name = $manager_data->display_name;
                    }
                }
                
                $action_date = '';
                if (!empty($row['next_action_date']) && $row['next_action_date'] !== '0000-00-00') {
                    $action_date = $row['next_action_date'];
                }

                fputcsv($output, array(
                    $row['id'],
                    $row['created_at'],
                    $row['date_visit'],
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
                    !empty($row['signature']) ? 'Yes' : 'No',
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
                    $row['feedback_details'],
                    $row['s_m'],
                    $action_date,
                    $row['next_action_remarks']
                ));
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
        'date_to' => $date_to
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
    echo '<label style="display:block; margin-bottom:5px;" for="date_from">From Date:</label>';
    echo '<input type="date" id="date_from" name="date_from" value="' . esc_attr($date_from) . '">';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block; margin-bottom:5px;" for="date_to">To Date:</label>';
    echo '<input type="date" id="date_to" name="date_to" value="' . esc_attr($date_to) . '">';
    echo '</div>';

    echo '<div>';
    echo '<input type="submit" id="search-submit" class="button button-primary" value="Filter">';
    if (!empty($search) || !empty($date_from) || !empty($date_to)) {
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
    echo '<th>Date</th><th>Name</th><th>Closing Manager</th><th>Contact</th><th>Email</th><th>Config</th><th>Budget</th><th>Source</th><th>Partner</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    if ($results) {
        foreach ($results as $row) {
            echo '<tr>';
            echo '<th scope="row" class="check-column"><input type="checkbox" name="enquiry_ids[]" value="' . esc_attr($row->id) . '"></th>';
            echo '<td>' . esc_html(date('d M Y', strtotime($row->date_visit))) . '</td>';
            
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

            echo '<td>' . esc_html($row->contact) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '<td>' . esc_html($row->configuration) . '</td>';
            echo '<td>' . esc_html($row->budget) . '</td>';
            
            $source_display = esc_html($row->source);
            if ($row->source === 'Reference' && !empty($row->reference_name)) {
                $source_display .= '<br><small>(' . esc_html($row->reference_name) . ')</small>';
            }
            echo '<td>' . $source_display . '</td>';

            $cp_display = esc_html($row->cp_name);
            if (!empty($row->cp_contact)) {
                $cp_display .= '<br><small>' . esc_html($row->cp_contact) . '</small>';
            }
            if (empty($cp_display)) $cp_display = '-';
            echo '<td>' . $cp_display . '</td>';

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
        .status-gold { background: #faf5ff; color: #a855f7; border-color: #f3e8ff; }
        .status-none { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }
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
                        <td class="label-cell">Client Name</td>
                        <td class="value-cell" id="feedback-client-name" style="font-weight: 700; font-size: 14px;"></td>
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
                <table class="crm-feedback-table">
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
                    document.getElementById('feedback-client-name').textContent = data.name || '-';
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
    global $wpdb;
    $table_name = $wpdb->prefix . 'crm_enquiries';
    $user = wp_get_current_user();
    $is_admin = current_user_can('manage_options');

    // Access check
    if (!in_array('crm_closing_manager', $user->roles) && !$is_admin) {
        wp_die('Access denied.');
    }

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
            .status-gold { background: #faf5ff; color: #a855f7; border: 1px solid #f3e8ff; }
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
                            <td><strong><?php echo esc_html($row->name); ?></strong><br><small><?php echo esc_html($row->contact); ?></small></td>
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
                                <label>Full Name</label>
                                <span id="info-name" style="font-weight: 700;"></span>
                            </div>
                            <div class="crm-info-item">
                                <label>Contact Info</label>
                                <span id="info-contact"></span>
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
                            <div class="crm-info-item">
                                <label>Client Signature</label>
                                <div class="signature-preview-box" id="info-signature"></div>
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
                                <span class="crm-pill-opt" data-value="Gold" style="color: #a855f7;">Gold</span>
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
                        <h3>Schedule Next Action Follow-Up</h3>
                        <div class="crm-form-grid">
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
                    document.getElementById('info-name').textContent = client.name;
                    document.getElementById('info-contact').textContent = client.contact;
                    document.getElementById('info-residence').textContent = client.residence || '-';
                    document.getElementById('info-occupation').textContent = client.occupation || '-';
                    document.getElementById('info-configuration').textContent = client.configuration || '-';
                    document.getElementById('info-budget').textContent = client.budget || '-';

                    const sigPreview = document.getElementById('info-signature');
                    if (client.signature) {
                        sigPreview.innerHTML = `<img src="${client.signature}" alt="Customer Signature">`;
                    } else {
                        sigPreview.innerHTML = `<span style="color:#64748b; font-size:12px; font-style:italic;">No Signature Captured</span>`;
                    }

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

    $data = array(
        'lead_status' => sanitize_text_field($_POST['lead_status']),
        'sourcing_manager' => sanitize_text_field($_POST['sourcing_manager']),
        'client_age' => sanitize_text_field($_POST['client_age']),
        'visit_type' => sanitize_text_field($_POST['visit_type']),
        'visit_attended_by' => sanitize_text_field($_POST['visit_attended_by']),
        'funding_source' => sanitize_text_field($_POST['funding_source']),
        'sop_amount' => sanitize_text_field($_POST['sop_amount']),
        'ready_down_payment' => sanitize_text_field($_POST['ready_down_payment']),
        'own_contribution' => sanitize_text_field($_POST['own_contribution']),
        'unit_like' => sanitize_text_field($_POST['unit_like']),
        'unit_floor' => sanitize_text_field($_POST['unit_floor']),
        'unit_budget' => sanitize_text_field($_POST['unit_budget']),
        'feedback_by' => $user->display_name,
        'feedback_details' => sanitize_textarea_field($_POST['feedback_details']),
        's_m' => sanitize_text_field($_POST['s_m']),
        'next_action_date' => !empty($_POST['next_action_date']) ? sanitize_text_field($_POST['next_action_date']) : '0000-00-00',
        'next_action_remarks' => sanitize_textarea_field($_POST['next_action_remarks']),
    );

    $updated = $wpdb->update($table_name, $data, array('id' => $id));

    if ($updated !== false) {
        wp_send_json_success(array('message' => 'Client annotations saved successfully!'));
    } else {
        wp_send_json_error(array('message' => 'Failed to save annotation sheet.'));
    }
}
