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
            
            echo '<table class="form-table" role="presentation">';
            echo '<tr><th scope="row"><label for="date_visit">Date Visit</label></th><td><input name="date_visit" type="date" id="date_visit" value="' . esc_attr($enquiry->date_visit) . '" class="regular-text"></td></tr>';
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
    echo '<th>Date</th><th>Name</th><th>Contact</th><th>Email</th><th>Config</th><th>Budget</th><th>Source</th><th>Partner</th>';
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
        echo '<tr><td colspan="9">No enquiries found.</td></tr>';
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
    echo '</div>';
}
