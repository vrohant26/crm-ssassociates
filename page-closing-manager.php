<?php
/**
 * Template Name: Closing Manager Portal
 */

$user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$is_authorized = $is_logged_in && (in_array('crm_closing_manager', $user->roles) || current_user_can('manage_options'));

$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
$single_client = null;

if ($is_authorized && $client_id > 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'crm_enquiries';
    $single_client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $client_id));
    
    // Security check: redirect back if not found or unauthorized
    $is_admin = current_user_can('manage_options');
    if (!$single_client || (!$is_admin && intval($single_client->closing_manager_id) !== $user->ID)) {
        wp_safe_redirect(home_url('/closing-manager/'));
        exit;
    }
}

get_header();
?>

<main class="site-main app-main" style="background-color: #f8f9fa; min-height: 100vh; padding: 2rem 1rem;">
    <div class="app-layout" style="max-width: 1200px; margin: 0 auto; display: block;">
        
        <?php if (!$is_logged_in) : ?>
            <!-- ==================================================== -->
            <!-- 1. AUTHENTICATION LOGIN CARD                         -->
            <!-- ==================================================== -->
            <div class="crm-login-container" style="max-width: 420px; margin: 4rem auto; background: #ffffff; border-radius: 12px; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; text-align: center;">
                <img src="<?php echo get_template_directory_uri(); ?>/pearl-grace-logo.png" alt="Pearl Grace Logo" style="max-width: 100px; height: auto; margin-bottom: 1.5rem; border-radius: 10px;">
                <h2 style="font-size: 1.6rem; color: #1e293b; margin: 0 0 0.5rem 0; font-weight: 700;">Closing Manager Portal</h2>
                <p style="color: #64748b; font-size: 0.9rem; margin: 0 0 2rem 0;">Please log in to manage your client portfolios.</p>

                <?php 
                if (isset($_GET['login']) && $_GET['login'] === 'failed') {
                    echo '<div style="background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 0.8rem 1rem; border-radius: 8px; font-size: 0.85rem; font-weight: 500; margin-bottom: 1.5rem; text-align: left;">';
                    echo '<strong>Error:</strong> Invalid username or password. Please try again.';
                    echo '</div>';
                }
                ?>

                <form method="post" action="<?php echo esc_url( wp_login_url( home_url( '/closing-manager/' ) ) ); ?>" style="text-align: left;">
                    <div style="margin-bottom: 1.25rem;">
                        <label for="user_login" style="display: block; font-size: 0.8rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem; text-transform: uppercase;">Username or Email</label>
                        <div style="position: relative;">
                            <input type="text" name="log" id="user_login" required style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: #f8fafc; outline: none; box-sizing: border-box;" placeholder="Enter username...">
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="user_pass" style="display: block; font-size: 0.8rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem; text-transform: uppercase;">Password</label>
                        <input type="password" name="pwd" id="user_pass" required style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: #f8fafc; outline: none; box-sizing: border-box;" placeholder="••••••••">
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.85rem;">
                        <label style="color: #64748b; font-weight: 500; cursor: pointer;">
                            <input type="checkbox" name="rememberme" value="forever" style="margin-right: 5px;"> Remember Me
                        </label>
                    </div>

                    <button type="submit" style="width: 100%; padding: 0.9rem; background: #d4af37; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#b5952f'" onmouseout="this.style.background='#d4af37'">
                        Log In to Portal
                    </button>
                </form>
            </div>

        <?php elseif (!$is_authorized) : ?>
            <!-- ==================================================== -->
            <!-- 2. UNAUTHORIZED ACCESS STATE                         -->
            <!-- ==================================================== -->
            <div class="crm-login-container" style="max-width: 420px; margin: 6rem auto; background: #ffffff; border-radius: 12px; padding: 2.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; text-align: center;">
                <div style="width: 60px; height: 60px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto; color: #ef4444;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                </div>
                <h2 style="font-size: 1.4rem; color: #1e293b; margin: 0 0 0.5rem 0; font-weight: 700;">Access Denied</h2>
                <p style="color: #64748b; font-size: 0.95rem; margin: 0 0 2rem 0; line-height: 1.5;">You do not have the required permissions to access the Closing Manager Portal.</p>
                
                <a href="<?php echo wp_logout_url( home_url( '/closing-manager/' ) ); ?>" style="display: inline-block; padding: 0.8rem 1.8rem; background: #ef4444; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: background 0.3s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                    Log Out
                </a>
            </div>

        <?php else : ?>
            <!-- ==================================================== -->
            <!-- 3. AUTHORIZED CLOSING MANAGER PORTAL                 -->
            <!-- ==================================================== -->
            <?php if ($single_client === null) : ?>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'crm_enquiries';

                $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
                $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
                $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
                $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

                $where_clauses = array();
                $prepare_args = array();

                // Strictly filter by assigned manager, unless administrator
                $is_admin = current_user_can('manage_options');
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
                
                // Fetch Today's Tasks
                $today = date('Y-m-d');
                $today_where = "WHERE next_action_date = %s";
                $today_args = array($today);
                if (!$is_admin) {
                    $today_where .= " AND closing_manager_id = %d";
                    $today_args[] = $user->ID;
                }
                $today_tasks_query = $wpdb->prepare("SELECT id, name, contact, building_name, next_action_date, next_action_remarks FROM $table_name $today_where ORDER BY id DESC", ...$today_args);
                $today_tasks = $wpdb->get_results($today_tasks_query);
                ?>

                <style>
                    /* Premium Table Container with horizontal scrolling */
                    .crm-table-container {
                        background: #ffffff;
                        border: 1px solid #e2e8f0;
                        border-radius: 12px;
                        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
                        overflow-x: auto; /* Enables smooth horizontal scroll */
                        position: relative;
                    }
                    
                    .crm-table-container table {
                        width: 100%;
                        border-collapse: collapse;
                        text-align: left;
                        font-size: 0.95rem;
                        min-width: 850px; /* Guarantees comfortable, breathable column widths on all devices */
                    }
                    
                    .crm-table-container th,
                    .crm-table-container td {
                        padding: 1rem 1.5rem;
                        box-sizing: border-box;
                    }
                    
                    /* Floating Sticky Last Column (Action Column) */
                    .crm-table-container th:last-child,
                    .crm-table-container td:last-child {
                        position: -webkit-sticky;
                        position: sticky;
                        right: 0;
                        width: 75px !important;
                        min-width: 75px !important;
                        text-align: center !important;
                        background: #ffffff !important;
                        z-index: 10;
                        box-shadow: -6px 0 10px rgba(0, 0, 0, 0.04); /* Elegant drop shadow on the left edge */
                        border-left: 1px solid #f1f5f9;
                    }
                    
                    /* Floating header needs higher z-index and correct background */
                    .crm-table-container th:last-child {
                        background: #f8fafc !important;
                        z-index: 11;
                    }
                    
                    /* Ensure row hover styling applies correctly to the sticky cell */
                    .crm-table-container tr:hover td:last-child {
                        background: #fafafb !important;
                    }
                    
                    /* Gold Arrow-Right Action Icon Button */
                    .crm-btn-details-icon {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 32px;
                        height: 32px;
                        background: #d4af37;
                        color: white;
                        border-radius: 8px;
                        text-decoration: none;
                        transition: all 0.2s ease;
                        box-shadow: 0 2px 6px rgba(212, 175, 55, 0.15);
                    }
                    
                    .crm-btn-details-icon:hover {
                        background: #b5952f;
                        transform: translateX(1px);
                        box-shadow: 0 4px 10px rgba(212, 175, 55, 0.3);
                    }
                    
                    .crm-btn-details-icon:active {
                        transform: translateX(0);
                    }

                    /* Client Status Pills in table list */
                    .status-pill {
                        text-wrap : nowrap;
                        padding: 4px 10px;
                        border-radius: 20px;
                        font-size: 11px;
                        font-weight: 700;
                        text-transform: uppercase;
                        display: inline-block;
                    }
                    .status-hot { background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; }
                    .status-warm { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
                    .status-cold { background: #f0f9ff; color: #0284c7; border: 1px solid #e0f2fe; }
                    .status-booked { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
                    .status-lost { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
                    .status-none { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
                </style>

                <div class="crm-portal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h1 style="font-size: 1.8rem; font-weight: 700; color: #1e293b; margin: 0 0 0.25rem 0;">My Client Portfolios</h1>
                        <p style="color: #64748b; font-size: 0.95rem; margin: 0;">Logged in as: <strong style="color: #d4af37;"><?php echo esc_html($user->display_name); ?></strong> (Closing Manager)</p>
                    </div>
                    <div>
                        <a href="<?php echo wp_logout_url( home_url( '/closing-manager/' ) ); ?>" style="padding: 0.6rem 1.2rem; background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#1e293b';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569';">
                            Log Out Portal
                        </a>
                    </div>
                </div>

                <!-- Today's Tasks Module -->
                <div class="crm-todays-tasks" style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 1rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d4af37" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        <h2 style="font-size: 1.3rem; font-weight: 700; color: #1e293b; margin: 0;">Today's Tasks</h2>
                    </div>
                    
                    <?php if (!empty($today_tasks)) : ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                            <?php foreach ($today_tasks as $task) : ?>
                                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 10px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div>
                                            <h4 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: #1e293b;"><?php echo esc_html($task->name); ?></h4>
                                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;"><?php echo esc_html($task->building_name ?: 'Pearl Grace'); ?></span>
                                        </div>
                                        <a href="?client_id=<?php echo $task->id; ?>" title="View Assessment" style="color: #cbd5e1; transition: color 0.2s;" onmouseover="this.style.color='#d4af37';" onmouseout="this.style.color='#cbd5e1';">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        </a>
                                    </div>
                                    <p style="margin: 0; font-size: 0.9rem; color: #475569; line-height: 1.4; flex-grow: 1;">
                                        <strong>Action:</strong> <?php echo esc_html($task->next_action_remarks ?: 'No details provided.'); ?>
                                    </p>
                                    <div style="margin-top: 5px;">
                                        <a href="tel:<?php echo esc_attr($task->contact); ?>" class="crm-btn-primary" style="display: flex; align-items: center; justify-content: center; gap: 6px; padding: 8px; width: 100%; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; background: #d4af37; color: white; transition: background 0.2s;" onmouseover="this.style.background='#b5952f';" onmouseout="this.style.background='#d4af37';">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                            Call <?php echo esc_html($task->contact); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 2rem; text-align: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <p style="margin: 0; color: #64748b; font-size: 0.95rem; font-weight: 500;">You have no scheduled tasks for today!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Dashboard Filters -->
                <form method="get" class="crm-filter-bar" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.02); display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                    <div class="filter-group" style="display: flex; flex-direction: column; min-width: 200px; flex-grow: 1;">
                        <label style="font-weight: 600; font-size: 0.8rem; margin-bottom: 6px; color: #1e293b; text-transform: uppercase;">Search Client</label>
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name or contact..." style="padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; background: #f8fafc; outline: none;">
                    </div>

                    <div class="filter-group" style="display: flex; flex-direction: column; min-width: 150px; flex-grow: 1;">
                        <label style="font-weight: 600; font-size: 0.8rem; margin-bottom: 6px; color: #1e293b; text-transform: uppercase;">Lead Rating</label>
                        <select name="status" style="padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; background: #f8fafc; outline: none; cursor: pointer;">
                            <option value="">All Ratings</option>
                            <option value="Hot" <?php selected($status_filter, 'Hot'); ?>>Hot</option>
                            <option value="Warm" <?php selected($status_filter, 'Warm'); ?>>Warm</option>
                            <option value="Cold" <?php selected($status_filter, 'Cold'); ?>>Cold</option>
                            <option value="Lost" <?php selected($status_filter, 'Lost'); ?>>Lost</option>
                        </select>
                    </div>

                    <div class="filter-group" style="display: flex; flex-direction: column; min-width: 150px; flex-grow: 1;">
                        <label style="font-weight: 600; font-size: 0.8rem; margin-bottom: 6px; color: #1e293b; text-transform: uppercase;">From Visit</label>
                        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" style="padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; background: #f8fafc; outline: none;">
                    </div>

                    <div class="filter-group" style="display: flex; flex-direction: column; min-width: 150px; flex-grow: 1;">
                        <label style="font-weight: 600; font-size: 0.8rem; margin-bottom: 6px; color: #1e293b; text-transform: uppercase;">To Visit</label>
                        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" style="padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; background: #f8fafc; outline: none;">
                    </div>

                    <div style="display: flex; gap: 8px; flex-shrink: 0; margin-bottom: 1px;">
                        <input type="submit" value="Filter" style="padding: 11px 24px; background: #d4af37; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#b5952f'" onmouseout="this.style.background='#d4af37'">
                        <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)) : ?>
                            <a href="?" style="padding: 10px 18px; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.95rem; line-height: 20px; transition: all 0.3s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='#ffffff';">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Table Card -->
                <div class="crm-table-container">
                    <table>
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <th style="width: 120px;">Date Visit</th>
                                <th>Client</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 180px;">Next Action</th>
                                <th>Action</th>
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

                                    // Next Action details
                                    $next_action_date_display = '-';
                                    if (!empty($row->next_action_date) && $row->next_action_date !== '0000-00-00') {
                                        $next_action_date_display = date('d M Y', strtotime($row->next_action_date));
                                    }
                                    
                                    $next_action_remarks_display = !empty($row->next_action_remarks) ? esc_html($row->next_action_remarks) : '-';
                                    if (strlen($next_action_remarks_display) > 40) {
                                        $next_action_remarks_display = substr($next_action_remarks_display, 0, 37) . '...';
                                    }
                                    ?>
                                    <tr id="client-row-<?php echo esc_attr($row->id); ?>" style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#fafafb';" onmouseout="this.style.background='transparent';">
                                        <td data-label="Date Visit"><?php echo esc_html(date('d M Y', strtotime($row->date_visit))); ?></td>
                                        <td class="client-cell">
                                            <strong style="color: #1e293b;"><?php echo esc_html($row->name); ?></strong><br>
                                            <a href="tel:<?php echo esc_attr($row->contact); ?>" style="color: var(--crm-gold-dark, #b5952f); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; margin-top: 2.5px; font-size: 0.85rem;" class="tel-link">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                                call
                                            </a>
                                            <small style="color: #475569; display: block; margin-top: 2px;"><?php echo esc_html($row->occupation); ?></small>
                                        </td>
                                        <td data-label="Status"><span class="status-pill <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_lbl); ?></span></td>
                                        <td data-label="Next Action">
                                            <strong style="color: #1e293b; font-size: 0.85rem; display: block;"><?php echo esc_html($next_action_date_display); ?></strong>
                                            <span style="font-size: 0.8rem; color: #64748b;"><?php echo esc_html($next_action_remarks_display); ?></span>
                                        </td>
                                        <td>
                                            <a href="?client_id=<?php echo esc_attr($row->id); ?>" class="crm-btn-details-icon" title="View Details">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" style="padding: 3rem; text-align: center; color: #64748b; font-style: italic;">No clients found assigned to you.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>

            <!-- Custom Styling for Closing Manager Sheet -->
            <style>
                #crm-fullscreen-body {
                    display: flex;
                    flex-direction: column;
                    gap: 1.5rem;
                    margin-top: 1rem;
                    max-width: 800px;
                    margin-left: auto;
                    margin-right: auto;
                }

                /* Accordion Styles */
                .crm-accordion { margin-bottom: 0.5rem; }
                .crm-accordion-header {
                    display: flex; justify-content: space-between; align-items: center;
                    cursor: pointer; padding: 1.25rem 1.5rem; background: var(--card-bg, #ffffff);
                    border-radius: 12px; border: 1px solid #e2e8f0;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: all 0.3s ease;
                }
                .crm-accordion-header:hover { background: #f8fafc; }
                .crm-accordion-header.active {
                    border-bottom-left-radius: 0; border-bottom-right-radius: 0;
                    border-bottom: 1px solid #f1f5f9;
                }
                .crm-accordion-content {
                    background: var(--card-bg, #ffffff); padding: 1.5rem;
                    border: 1px solid #e2e8f0; border-top: none;
                    border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                    display: none;
                }
                .crm-accordion-content.active { display: block; }
                .crm-accordion-icon { transition: transform 0.3s ease; color: #64748b; }
                .crm-accordion-header.active .crm-accordion-icon { transform: rotate(180deg); }

                
                /* Toast styles */
                .crm-toast {
                    position: fixed;
                    bottom: 25px;
                    right: 25px;
                    padding: 12px 24px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 600;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
                    z-index: 99999;
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

                /* Client Status Pills in table list */
                .status-pill {
                    padding: 4px 10px;
                    border-radius: 20px;
                    font-size: 11px;
                    font-weight: 700;
                    text-transform: uppercase;
                    display: inline-block;
                }
                .status-hot { background: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; }
                .status-warm { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
                .status-cold { background: #f0f9ff; color: #0284c7; border: 1px solid #e0f2fe; }
                .status-booked { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
                .status-lost { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
                .status-none { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

                .signature-preview-box {
                    margin-top: 5px;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 8px;
                    background: #f8fafc;
                }
                
                .crm-btn {
                    padding: 0.8rem 1.8rem;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                .crm-btn-secondary {
                    background: #f1f5f9;
                    border: 1px solid #cbd5e1;
                    color: #475569;
                }
                .crm-btn-secondary:hover {
                    background: #e2e8f0;
                    color: #0f172a;
                }
                .crm-btn-primary {
                    background: #d4af37;
                    border: 1px solid #d4af37;
                    color: white;
                }
                .crm-btn-primary:hover {
                    background: #b5952f;
                    border-color: #b5952f;
                }

                .crm-info-grid {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 15px;
                }
                .crm-info-item {
                    display: flex;
                    flex-direction: column;
                    border-bottom: 1px solid #f1f5f9;
                    padding-bottom: 8px;
                }
                .crm-info-item:last-child {
                    border-bottom: none;
                }
                .crm-info-item label {
                    font-size: 11px;
                    color: #64748b;
                    font-weight: 600;
                    text-transform: uppercase;
                    margin-bottom: 3px;
                    letter-spacing: 0.05em;
                }
                .crm-info-item span {
                    font-size: 14px;
                    font-weight: 500;
                    color: #1e293b;
                }
                
                /* Custom Status Pills evaluations styling */
                .pill.pill-hot {
                    border-color: #fca5a5 !important;
                    color: #ef4444 !important;
                    background: transparent;
                }
                .pill.pill-hot:hover {
                    background: #fef2f2 !important;
                }
                .pill.pill-hot.active {
                    background: #ef4444 !important;
                    color: white !important;
                    border-color: #ef4444 !important;
                }

                .pill.pill-warm {
                    border-color: #fde68a !important;
                    color: #d97706 !important;
                    background: transparent;
                }
                .pill.pill-warm:hover {
                    background: #fffbeb !important;
                }
                .pill.pill-warm.active {
                    background: #d97706 !important;
                    color: white !important;
                    border-color: #d97706 !important;
                }

                .pill.pill-cold {
                    border-color: #bae6fd !important;
                    color: #0284c7 !important;
                    background: transparent;
                }
                .pill.pill-cold:hover {
                    background: #f0f9ff !important;
                }
                .pill.pill-cold.active {
                    background: #0284c7 !important;
                    color: white !important;
                    border-color: #0284c7 !important;
                }
                
                .pill.pill-booked {
                    border-color: #a7f3d0 !important;
                    color: #059669 !important;
                    background: transparent;
                }
                .pill.pill-booked:hover {
                    background: #ecfdf5 !important;
                }
                .pill.pill-booked.active {
                    background: #10b981 !important;
                    color: white !important;
                    border-color: #10b981 !important;
                }

                .pill.pill-lost {
                    border-color: #cbd5e1 !important;
                    color: #64748b !important;
                    background: transparent;
                }
                .pill.pill-lost:hover {
                    background: #f1f5f9 !important;
                }
                .pill.pill-lost.active {
                    background: #64748b !important;
                    color: white !important;
                    border-color: #64748b !important;
                }

                @media (max-width: 900px) {
                    #crm-fullscreen-body {
                        grid-template-columns: 1fr !important;
                    }
                }

                /* Mobile responsive card styles for client list */
                @media (max-width: 768px) {
                    .crm-table-container thead {
                        display: none !important;
                    }
                    .crm-table-container table,
                    .crm-table-container tbody,
                    .crm-table-container tr,
                    .crm-table-container td {
                        display: block !important;
                        width: 100% !important;
                        box-sizing: border-box !important;
                    }
                    .crm-table-container tr {
                        background: #ffffff;
                        border: 1px solid #e2e8f0 !important;
                        border-radius: 12px !important;
                        margin-bottom: 1.25rem !important;
                        padding: 1.25rem !important;
                        box-shadow: 0 4px 15px rgba(0,0,0,0.02) !important;
                        position: relative !important;
                    }
                    .crm-table-container td {
                        padding: 0.4rem 0 !important;
                        border-bottom: none !important;
                        display: flex !important;
                        flex-direction: row !important;
                        align-items: baseline !important;
                        text-align: left !important;
                    }
                    .crm-table-container td::before {
                        content: attr(data-label);
                        display: inline-block !important;
                        width: 110px !important;
                        flex-shrink: 0 !important;
                        font-size: 0.72rem !important;
                        text-transform: uppercase !important;
                        color: #64748b !important;
                        font-weight: 700 !important;
                        letter-spacing: 0.05em !important;
                    }
                    .crm-table-container td.client-cell {
                        display: block !important;
                        padding-top: 0 !important;
                        margin-bottom: 8px !important;
                        border-bottom: 1px solid #f1f5f9 !important;
                        padding-bottom: 10px !important;
                    }
                    .crm-table-container td.client-cell::before {
                        display: none !important;
                    }
                    .crm-table-container td.client-cell strong {
                        font-size: 1.15rem !important;
                        display: inline-block !important;
                        margin-bottom: 2px !important;
                    }
                    .crm-table-container td.client-cell .tel-link {
                        margin-top: 4px !important;
                        font-size: 0.9rem !important;
                    }
                    .crm-table-container td.client-cell small {
                        font-size: 0.85rem !important;
                    }
                    .crm-table-container td[data-label="Status"] .status-pill {
                        font-size: 10px !important;
                        padding: 2px 8px !important;
                    }
                    .crm-table-container td[data-label="Next Action"] {
                        flex-direction: column !important;
                        align-items: flex-start !important;
                    }
                    .crm-table-container td[data-label="Next Action"]::before {
                        margin-bottom: 4px !important;
                    }
                    .crm-table-container td[data-label="Next Action"] strong {
                        font-size: 0.9rem !important;
                        color: #1e293b !important;
                        display: block !important;
                        margin-bottom: 2px !important;
                    }
                    .crm-table-container td[data-label="Next Action"] span {
                        font-size: 0.85rem !important;
                    }
                    .crm-table-container td:last-child {
                        margin-top: 10px !important;
                        border-top: 1px solid #f1f5f9 !important;
                        padding-top: 12px !important;
                        display: flex !important;
                        gap: 10px !important;
                        justify-content: stretch !important;
                        align-items: center !important;
                    }
                    .crm-table-container td:last-child::before {
                        display: none !important;
                    }
                    .crm-table-container td:last-child a {
                        flex: 1 !important;
                        text-align: center !important;
                        padding: 10px 14px !important;
                        font-size: 0.88rem !important;
                        height: 38px !important;
                        box-sizing: border-box !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        margin: 0 !important;
                    }
                    .crm-table-container td:last-child .crm-btn-call-mobile {
                        display: inline-flex !important;
                    }
                }

                /* Timeline styles */
                .crm-timeline {
                    position: relative;
                    padding-left: 1.5rem;
                    margin-bottom: 2rem;
                    border-left: 2px solid #e2e8f0;
                }
                .crm-timeline-item {
                    position: relative;
                    margin-bottom: 1.5rem;
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
                    background: #d4af37;
                    border: 2px solid #ffffff;
                    box-shadow: 0 0 0 2px #d4af37;
                }
                .crm-timeline-content {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 1rem;
                }
                .crm-timeline-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 0.5rem;
                    flex-wrap: wrap;
                    gap: 8px;
                }
                .crm-timeline-sm {
                    font-weight: 600;
                    font-size: 0.9rem;
                    color: #1e293b;
                }
                .crm-timeline-date {
                    font-size: 0.8rem;
                    color: #64748b;
                    font-weight: 500;
                }
                .crm-timeline-remarks {
                    font-size: 0.9rem;
                    color: #475569;
                    line-height: 1.5;
                    margin: 0;
                    white-space: pre-wrap;
                }
            </style>

                <div class="crm-portal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <a href="<?php echo esc_url(home_url('/closing-manager/')); ?>" style="display: inline-flex; align-items: center; gap: 8px; color: #d4af37; text-decoration: none; font-weight: 600; font-size: 1rem; transition: color 0.2s;" onmouseover="this.style.color='#b5952f'" onmouseout="this.style.color='#d4af37'">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                             Back to Portfolio
                        </a>
                        <h1 style="font-size: 1.8rem; font-weight: 700; color: #1e293b; margin: 0.5rem 0 0.25rem 0;">Client Assessment Sheet</h1>
                        <p style="color: #64748b; font-size: 0.95rem; margin: 0;">Evaluating: <strong style="color: #1e293b;"><?php echo esc_html($single_client->name); ?></strong></p>
                    </div>
                    <div>
                        <a href="<?php echo wp_logout_url( home_url( '/closing-manager/' ) ); ?>" style="padding: 0.6rem 1.2rem; background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#1e293b';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569';">
                            Log Out Portal
                        </a>
                    </div>
                </div>

                <form id="crm-annotations-form">
                    <?php wp_nonce_field('crm_save_sheet_nonce', 'crm_sheet_nonce'); ?>
                    <input type="hidden" name="enquiry_id" id="form-enquiry-id" value="<?php echo esc_attr($single_client->id); ?>">
                    <input type="hidden" name="lead_status" id="form-lead-status" value="<?php echo esc_attr($single_client->lead_status); ?>">
                    <input type="hidden" name="visit_type" id="form-visit-type" value="<?php echo esc_attr($single_client->visit_type); ?>">
                    <input type="hidden" name="visit_attended_by" id="form-visit-attended-by" value="<?php echo esc_attr($single_client->visit_attended_by); ?>">
                    <input type="hidden" name="funding_source" id="form-funding-source" value="<?php echo esc_attr($single_client->funding_source); ?>">

                    <div id="crm-fullscreen-body">

    <?php
    $rating = $single_client->lead_status;
    $visit_type = $single_client->visit_type;
    $attended = $single_client->visit_attended_by;
    $funding = $single_client->funding_source;
    $is_saved = !empty($single_client->assessment_saved);

    global $wpdb;
    $followups_table = $wpdb->prefix . 'crm_followups';
    $followup_history = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $followups_table WHERE enquiry_id = %d ORDER BY created_at DESC",
        $single_client->id
    ));
    ?>

 

    <!-- 2. Accordions / Editable content below -->
    
    <!-- Accordion: Customer Visit Details -->
    <div class="crm-accordion">
        <div class="crm-accordion-header" onclick="toggleAccordion(this)">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="section-icon" style="margin-right:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>
                <div class="section-title" style="margin-bottom:0;">
                    <h3 style="margin:0; font-size:1.1rem;">Customer Visit Details</h3>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">Personal profile enquired during check-in.</p>
                </div>
            </div>
            <div class="crm-accordion-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
        </div>
        <div class="crm-accordion-content">
            <div class="crm-info-grid">
                <div class="crm-info-item">
                    <label>Date of Visit</label>
                    <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html(date('d M Y', strtotime($single_client->date_visit))); ?></span>
                </div>
                <div class="crm-info-item">
                    <label>Project</label>
                    <span style="font-weight: 600; color: #1e293b;"><?php echo esc_html(!empty($single_client->building_name) ? $single_client->building_name : 'Pearl Grace'); ?></span>
                </div>
                <div class="crm-info-item">
                    <label>Full Name</label>
                    <span style="font-weight: 700; color: #1e293b;"><?php echo esc_html($single_client->name); ?></span>
                </div>
                <div class="crm-info-item">
                    <label>Contact Info</label>
                    <a href="tel:<?php echo esc_attr($single_client->contact); ?>" style="color: var(--crm-gold-dark, #b5952f); text-decoration: none; font-weight: 700; font-size: 1.05rem; display: inline-flex; align-items: center; gap: 6px; transition: color 0.2s;" onmouseover="this.style.color='var(--crm-gold, #d4af37)';" onmouseout="this.style.color='var(--crm-gold-dark, #b5952f)';">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                        <?php echo esc_html($single_client->contact); ?>
                    </a>
                </div>
                <div class="crm-info-item">
                    <label>Residence Location</label>
                    <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->residence ? $single_client->residence : '-'); ?></span>
                </div>
                <div class="crm-info-item">
                    <label>Occupation Status</label>
                    <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->occupation ? $single_client->occupation : '-'); ?></span>
                </div>
                <div class="crm-info-item">
                    <label>Required Configuration</label>
                    <span style="font-weight: 500; color: #1e293b;">
                        <?php 
                        echo esc_html($single_client->configuration ? $single_client->configuration : '-');
                        if (isset($single_client->carpet_area) && !empty($single_client->carpet_area)) {
                            echo ' (Carpet Area: ' . esc_html($single_client->carpet_area) . ')';
                        }
                        ?>
                    </span>
                </div>
                <div class="crm-info-item">
                    <label>Purchase Budget</label>
                    <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->budget ? $single_client->budget : '-'); ?></span>
                </div>
                <div class="crm-info-item">
                    <label>Sourcing Manager</label>
                    <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->sourcing_manager ? $single_client->sourcing_manager : '-'); ?></span>
                </div>

                <div class="crm-info-item">
                    <label>Pre-sales</label>
                    <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html(isset($single_client->pre_sales) && $single_client->pre_sales ? $single_client->pre_sales : '-'); ?></span>
                </div>
                <div class="crm-info-item">
                    <label>How did you hear about us?</label>
                    <span style="font-weight: 500; color: #1e293b;">
                        <?php 
                        $source = $single_client->source;
                        $display_source = $source;
                        if ($source === 'Reference' && !empty($single_client->reference_name)) {
                            $display_source .= ' (Referral Name: ' . $single_client->reference_name . ')';
                        } elseif ($source === 'Channel Partner') {
                            $cp_details = array();
                            if (isset($single_client->cp_firm_name) && !empty($single_client->cp_firm_name)) {
                                $cp_details[] = 'Firm Name: ' . $single_client->cp_firm_name;
                            }
                            if (!empty($single_client->cp_name)) {
                                $cp_details[] = 'CP Name: ' . $single_client->cp_name;
                            }
                            if (!empty($single_client->cp_contact)) {
                                $cp_details[] = 'CP Contact: ' . $single_client->cp_contact;
                            }
                            if (!empty($cp_details)) {
                                $display_source .= ' (' . implode(', ', $cp_details) . ')';
                            }
                        }
                        echo esc_html($display_source);
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($is_saved): ?>
        <!-- Accordion: Saved Assessment -->
        <div class="crm-accordion">
            <div class="crm-accordion-header" onclick="toggleAccordion(this)">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="section-icon" style="margin-right:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="section-title" style="margin-bottom:0;">
                        <h3 style="margin:0; font-size:1.1rem;">Saved Assessment</h3>
                        <p style="margin:0; font-size:0.85rem; color:#64748b;">Manager evaluation and feedback.</p>
                    </div>
                </div>
                <div class="crm-accordion-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>
            </div>
            <div class="crm-accordion-content">
                <div class="crm-info-grid">
                    <div class="crm-info-item">
                        <label>Client Age</label>
                        <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->client_age) ?: '-'; ?></span>
                    </div>
                    <div class="crm-info-item">
                        <label>Visit Attended By</label>
                        <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->visit_attended_by) ?: '-'; ?></span>
                    </div>
                    <div class="crm-info-item">
                        <label>SOP Amount</label>
                        <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->sop_amount) ?: '-'; ?></span>
                    </div>
                    <div class="crm-info-item">
                        <label>Bank Loan Amount</label>
                        <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->ready_down_payment) ?: '-'; ?></span>
                    </div>
                    <div class="crm-info-item">
                        <label>Own Contribution</label>
                        <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->own_contribution) ?: '-'; ?></span>
                    </div>
                    <div class="crm-info-item">
                        <label>Unit Preference</label>
                        <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->unit_like) ?: '-'; ?></span>
                    </div>
                    <div class="crm-info-item">
                        <label>Target Floor</label>
                        <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->unit_floor) ?: '-'; ?></span>
                    </div>
                    <div class="crm-info-item">
                        <label>Target Budget</label>
                        <span style="font-weight: 500; color: #1e293b;"><?php echo esc_html($single_client->unit_budget) ?: '-'; ?></span>
                    </div>
                    <div class="crm-info-item" style="grid-column: 1 / -1;">
                        <label>Feedback Details</label>
                        <span style="font-weight: 500; color: #1e293b;"><?php echo nl2br(esc_html($single_client->feedback_details)) ?: '-'; ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Not Saved: Editable Forms -->
        <div class="form-section card">
            <div class="section-header">
                <div class="section-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="section-title">
                    <h3>Manager Evaluation</h3>
                    <p>Fill out the customer rating and visit metadata.</p>
                </div>
            </div>
            <div class="form-grid mt-3">
                <div class="input-group full-width">
                    <label>Client Age</label>
                    <input type="text" name="client_age" id="form-client-age" placeholder="e.g. 35" value="<?php echo esc_attr($single_client->client_age); ?>">
                </div>
            </div>
            <label class="section-label mt-3">Visit Attended By</label>
            <div class="pill-group" id="visit-attended-pills">
                <button type="button" class="pill <?php echo ($attended === 'Husband') ? 'active' : ''; ?>" data-value="Husband">Husband</button>
                <button type="button" class="pill <?php echo ($attended === 'Wife') ? 'active' : ''; ?>" data-value="Wife">Wife</button>
                <button type="button" class="pill <?php echo ($attended === 'Father') ? 'active' : ''; ?>" data-value="Father">Father</button>
                <button type="button" class="pill <?php echo ($attended === 'Mother') ? 'active' : ''; ?>" data-value="Mother">Mother</button>
                <button type="button" class="pill <?php echo ($attended === 'Brother') ? 'active' : ''; ?>" data-value="Brother">Brother</button>
            </div>
            <div class="form-grid mt-3">
                <div class="input-group">
                    <label>SOP Amount</label>
                    <input type="text" name="sop_amount" id="form-sop-amount" placeholder="e.g. 10 Lakhs" value="<?php echo esc_attr($single_client->sop_amount); ?>">
                </div>
                <div class="input-group">
                    <label>Bank Loan Amount</label>
                    <input type="text" name="ready_down_payment" id="form-ready-down" placeholder="e.g. 15 Lakhs" value="<?php echo esc_attr($single_client->ready_down_payment); ?>">
                </div>
                <div class="input-group full-width">
                    <label>Own Contribution</label>
                    <input type="text" name="own_contribution" id="form-own-contribution" placeholder="e.g. 20 Lakhs" value="<?php echo esc_attr($single_client->own_contribution); ?>">
                </div>
            </div>
        </div>

        <div class="form-section card">
            <div class="section-header">
                <div class="section-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                </div>
                <div class="section-title">
                    <h3>Property Preferences & Feedback</h3>
                    <p>Client choices and post-visit experience reviews.</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="input-group">
                    <label>Unit Preference</label>
                    <input type="text" name="unit_like" id="form-unit-like" placeholder="e.g. 302" value="<?php echo esc_attr($single_client->unit_like); ?>">
                </div>
                <div class="input-group">
                    <label>Target Floor</label>
                    <input type="text" name="unit_floor" id="form-unit-floor" placeholder="e.g. 3rd Floor" value="<?php echo esc_attr($single_client->unit_floor); ?>">
                </div>
                <div class="input-group full-width">
                    <label>Target Budget</label>
                    <input type="text" name="unit_budget" id="form-unit-budget" placeholder="e.g. 85 Lakhs" value="<?php echo esc_attr($single_client->unit_budget); ?>">
                </div>
                <div class="input-group full-width">
                    <label>Feedback Details</label>
                    <textarea name="feedback_details" id="form-feedback-details" rows="3" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem; background: var(--bg-color); color: var(--text-dark); box-sizing: border-box; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='white';" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-color)';"><?php echo esc_html($single_client->feedback_details); ?></textarea>
                </div>
            </div>
        </div>
    <?php endif; ?>
           <!-- 1. Schedule Next Follow-Up (Always on top) -->
    <div class="form-section card" style="margin-bottom: 0;">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="section-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                </div>
                <div class="section-title">
                    <h3>Schedule Next Follow-Up</h3>
                    <p>Set a follow-up action plan with a sales representative.</p>
                </div>
            </div>
            <button type="button" id="btn-add-action-plan" class="crm-btn crm-btn-secondary" style="font-size: 0.85rem; padding: 6px 14px; display: flex; align-items: center; gap: 6px; border: 1px solid var(--crm-gold, #d4af37); color: var(--crm-gold-dark, #b5952f); background: #fffbeb; cursor: pointer; border-radius: 8px; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='#fef3c7';" onmouseout="this.style.background='#fffbeb';">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add Action Details
            </button>
        </div>

        <div style="margin-bottom: 2rem;">
            <label class="section-label">Client Rating Status</label>
            <div class="pill-group" id="rating-pills">
                <button type="button" class="pill pill-hot <?php echo ($rating === 'Hot') ? 'active' : ''; ?>" data-value="Hot">Hot</button>
                <button type="button" class="pill pill-warm <?php echo ($rating === 'Warm') ? 'active' : ''; ?>" data-value="Warm">Warm</button>
                <button type="button" class="pill pill-cold <?php echo ($rating === 'Cold') ? 'active' : ''; ?>" data-value="Cold">Cold</button>
                <button type="button" class="pill pill-booked <?php echo ($rating === 'Booked') ? 'active' : ''; ?>" data-value="Booked">Booked</button>
                <button type="button" class="pill pill-lost <?php echo ($rating === 'Lost') ? 'active' : ''; ?>" data-value="Lost">Lost</button>
            </div>

            <label class="section-label mt-3">Visit Frequency</label>
            <div class="pill-group" id="visit-type-pills">
                <button type="button" class="pill <?php echo ($visit_type === 'First visit') ? 'active' : ''; ?>" data-value="First visit">First Visit</button>
                <button type="button" class="pill <?php echo ($visit_type === 'Revisit') ? 'active' : ''; ?>" data-value="Revisit">Revisit</button>
                <button type="button" class="pill <?php echo ($visit_type === 'Multi visit') ? 'active' : ''; ?>" data-value="Multi visit">Multi Visit</button>
            </div>
        </div>

        <div class="form-grid" id="lost-reason-container" <?php if ($rating !== 'Lost') echo 'style="display: none;"'; ?>>
            <div class="input-group full-width">
                <label>Reason for Lost</label>
                <textarea name="lost_reason" id="form-lost-reason" rows="3" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem; background: var(--bg-color); color: var(--text-dark); box-sizing: border-box; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='white';" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-color)';"><?php echo esc_html($single_client->lost_reason ?? ''); ?></textarea>
            </div>
        </div>

        <div class="form-grid" id="action-fields-container" <?php if ($rating === 'Lost') echo 'style="display: none;"'; ?>>
            <div class="input-group full-width">
                <label>Action Date</label>
                <input type="date" name="next_action_date" id="form-next-date" value="<?php echo esc_attr(($single_client->next_action_date && $single_client->next_action_date !== '0000-00-00') ? $single_client->next_action_date : ''); ?>">
            </div>
            <div class="input-group full-width">
                <label>Action Plan Details</label>
                <textarea name="next_action_remarks" id="form-next-remarks" rows="3" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 1rem; background: var(--bg-color); color: var(--text-dark); box-sizing: border-box; outline: none; transition: all 0.3s;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='white';" onblur="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-color)';"><?php echo esc_html($single_client->next_action_remarks); ?></textarea>
            </div>
        </div>
        <?php if (!empty($followup_history)) : ?>
            <div style="border: none; padding-top: 1.5rem; margin-top: 1.5rem; width: 100%;">
                <label class="section-label" style="margin-bottom: 0.8rem; display: block; font-weight: 700; color: var(--crm-gold-dark, #b5952f);">Follow-Up History & Timeline</label>
                <ul style="list-style-type: disc; padding-left: 1rem; margin: 0; color: #475569; font-size: 10px; line-height: 1.6;">
                    <?php foreach ($followup_history as $history) : ?>
                        <li style="margin-bottom: 8px;">
                            <strong style="color: #1e293b;"><?php echo esc_html(date('d M Y', strtotime($history->action_date))); ?></strong> - <?php echo esc_html($history->remarks); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
<div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 2rem; padding: 1.5rem 0; border-top: 1px solid #e2e8f0;">
                        <a href="<?php echo esc_url(home_url('/closing-manager/')); ?>" class="crm-btn crm-btn-secondary" style="text-decoration: none; text-align: center; line-height: 20px;">Cancel</a>
                        <button type="submit" class="crm-btn crm-btn-primary" id="crm-btn-save">
                            <span id="btn-save-text">Save Assessments</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="crm-toast" id="crm-toast"></div>

            <!-- Page Logic and Controller Scripts -->
            <script>
                const ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('crm-annotations-form');

                    // Accordion logic
                    window.toggleAccordion = function(headerEl) {
                        headerEl.classList.toggle('active');
                        const contentEl = headerEl.nextElementSibling;
                        if (contentEl) {
                            contentEl.classList.toggle('active');
                        }
                    };

                    // Responsive accordion start state
                    function initAccordions() {
                        const isMobile = window.innerWidth <= 768;
                        const accordions = document.querySelectorAll('.crm-accordion-header');
                        accordions.forEach(header => {
                            if (!isMobile) {
                                header.classList.add('active');
                                if (header.nextElementSibling) {
                                    header.nextElementSibling.classList.add('active');
                                }
                            }
                        });
                    }
                    initAccordions();

                    // Toast alerts function
                    function showToast(msg, type) {
                        const toast = document.getElementById('crm-toast');
                        if (!toast) return;
                        toast.textContent = msg;
                        toast.className = 'crm-toast ' + type + ' active';
                        setTimeout(() => {
                            toast.classList.remove('active');
                        }, 4000);
                    }

                    // Check URL parameter for toast on the listing view
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('updated') === '1') {
                        showToast('Client assessments saved successfully!', 'success');
                    }

                    if (form) {
                        // Handle Pill selectors
                        function setupPills(containerId, hiddenInputId) {
                            const container = document.getElementById(containerId);
                            const hiddenInput = document.getElementById(hiddenInputId);
                            if (!container || !hiddenInput) return;

                            const pills = container.querySelectorAll('.pill');
                            pills.forEach(pill => {
                                pill.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    pills.forEach(p => p.classList.remove('active'));
                                    this.classList.add('active');
                                    hiddenInput.value = this.dataset.value;
                                    
                                    if (containerId === 'rating-pills') {
                                        const actionContainer = document.getElementById('action-fields-container');
                                        const lostContainer = document.getElementById('lost-reason-container');
                                        if (actionContainer && lostContainer) {
                                            if (this.dataset.value === 'Lost') {
                                                actionContainer.style.display = 'none';
                                                lostContainer.style.display = 'grid';
                                            } else {
                                                actionContainer.style.display = 'grid';
                                                lostContainer.style.display = 'none';
                                            }
                                        }
                                    }
                                });
                            });
                        }

                        setupPills('rating-pills', 'form-lead-status');
                        setupPills('visit-type-pills', 'form-visit-type');
                        setupPills('visit-attended-pills', 'form-visit-attended-by');

                        // Add Action Plan Details listener
                        const btnAddActionPlan = document.getElementById('btn-add-action-plan');
                        if (btnAddActionPlan) {
                            btnAddActionPlan.addEventListener('click', function(e) {
                                e.preventDefault();
                                const dateInput = document.getElementById('form-next-date');
                                const remarksInput = document.getElementById('form-next-remarks');
                                if (dateInput && remarksInput) {
                                    dateInput.value = '';
                                    remarksInput.value = '';
                                    dateInput.focus();
                                    showToast('Previous plan scheduled for archiving. You can now schedule a new action plan.', 'success');
                                }
                            });
                        }

                        // Secure AJAX Form saving
                        form.addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            // Enforce online check
                            if (!navigator.onLine) {
                                showToast('You are currently offline. An active network connection is required to save assessments.', 'error');
                                return;
                            }

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
                                btnText.textContent = 'Save Assessments';
                                saveBtn.disabled = false;

                                if (data.success) {
                                    showToast(data.data.message, 'success');
                                    // Redirect back to list with golden success alert parameter after 1 sec
                                    setTimeout(() => {
                                        window.location.href = "<?php echo esc_url(home_url('/closing-manager/?updated=1')); ?>";
                                    }, 1000);
                                } else {
                                    showToast(data.data.message || 'An error occurred.', 'error');
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                btnText.textContent = 'Save Assessments';
                                saveBtn.disabled = false;
                                showToast('A network error occurred. Please verify your connection.', 'error');
                            });
                        });
                    }
                });
            </script>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>
