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
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    add_option('crm_db_version', '1.0');
}
add_action('after_setup_theme', 'crm_create_enquiries_table');

// Setup Custom Client Role
function crm_setup_client_role() {
    add_role(
        'crm_client',
        'CRM Client',
        array(
            'read' => true,
            'view_crm_enquiries' => true
        )
    );

    $role = get_role('administrator');
    if ($role && !$role->has_cap('view_crm_enquiries')) {
        $role->add_cap('view_crm_enquiries');
    }
}
add_action('after_setup_theme', 'crm_setup_client_role');

// Restrict Client Admin Menu
function crm_restrict_client_menu() {
    if (current_user_can('view_crm_enquiries') && !current_user_can('manage_options')) {
        remove_menu_page('index.php'); // Hide Dashboard
        remove_menu_page('profile.php'); // Hide Profile
    }
}
add_action('admin_menu', 'crm_restrict_client_menu', 999);

// Redirect Client on Login
function crm_client_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('crm_client', $user->roles)) {
            return admin_url('admin.php?page=crm-enquiries');
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'crm_client_login_redirect', 10, 3);

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
            fputcsv($output, array_keys($results[0]));
            foreach ($results as $row) {
                $row['signature'] = !empty($row['signature']) ? 'Yes' : 'No';
                fputcsv($output, $row);
            }
        }
        fclose($output);
        exit;
    }
}

// Admin Page HTML
function crm_enquiries_page_html() {
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

    $results = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY id DESC");

    $export_url = add_query_arg(array(
        'page' => 'crm-enquiries',
        'export_csv' => '1',
        's' => $search,
        'date_from' => $date_from,
        'date_to' => $date_to
    ), admin_url('admin.php'));

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Enquiries</h1>';
    echo '<a href="' . esc_url($export_url) . '" class="page-title-action">Export CSV</a>';
    echo '<hr class="wp-header-end">';

    echo '<form method="get" style="display:flex; gap:15px; align-items:flex-end; margin-bottom:15px; flex-wrap:wrap;">';
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

    echo '<table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 15px;">';
    echo '<thead><tr>';
    echo '<th>Date</th><th>Name</th><th>Contact</th><th>Email</th><th>Config</th><th>Budget</th><th>Source</th><th>Partner</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    if ($results) {
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html(date('d M Y', strtotime($row->date_visit))) . '</td>';
            echo '<td><strong>' . esc_html($row->name) . '</strong><br><small>' . esc_html($row->occupation) . '</small></td>';
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
        echo '<tr><td colspan="8">No enquiries found.</td></tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}
