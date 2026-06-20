<?php get_header(); ?>

<main class="site-main app-main">
   
    <div class="app-layout">
        


        <div class="app-container">
             
            <!-- Dynamic Project Data -->
            <script>
                window.crmProjects = <?php echo json_encode(crm_get_projects()); ?>;
            </script>

            <!-- Project Selection Screen -->
            <div id="project-selector-section" class="fade-in" style="max-width: 700px; margin: 2rem auto; padding: 3rem 2rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); text-align: center;">
                <h2 style="font-size: 1.8rem; color: #1e293b; font-weight: 700; margin-bottom: 0.5rem;">Select Property Portal</h2>
                <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 2.5rem;">Choose a project to begin client check-in registration.</p>
                
                <div class="project-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(185px, 1fr)); gap: 1.5rem; justify-content: center;">
                    <?php
                    $projects = crm_get_projects();
                    if (!empty($projects)) {
                        foreach ($projects as $project) {
                            $proj_name = esc_attr($project['name']);
                            $proj_logo = esc_url($project['logo']);
                            ?>
                            <!-- <?php echo esc_html($project['name']); ?> Card -->
                            <div class="project-card" data-project="<?php echo $proj_name; ?>" data-logo="<?php echo $proj_logo; ?>" style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: #ffffff; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 160px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
                                <img src="<?php echo $proj_logo; ?>" alt="<?php echo $proj_name; ?>" style="max-height: 55px; width: auto; margin-bottom: 1rem; border-radius: 6px;">
                                <span style="font-weight: 600; color: #1e293b; font-size: 0.95rem;"><?php echo esc_html($project['name']); ?></span>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>

            <style>
                .project-card:hover {
                    border-color: var(--primary, #d4af37) !important;
                    transform: translateY(-4px);
                    box-shadow: 0 8px 24px rgba(212, 175, 55, 0.08) !important;
                    background: #fffbeb !important;
                }
                .project-card:active {
                    transform: translateY(-1px);
                }
                .hidden {
                    display: none !important;
                }
                .fade-in {
                    animation: fadeIn 0.4s ease forwards;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            </style>

            <!-- Check-In Form Section (hidden by default) -->
            <div id="enquiry-form-section" class="hidden">
                <div class="change-project-wrapper" style="text-align: left; margin-bottom: 1.5rem;">
                    <a href="#" id="change-project-link" style="display: inline-flex; align-items: center; gap: 6px; color: var(--primary, #d4af37); text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-hover, #b5952f)'" onmouseout="this.style.color='var(--primary, #d4af37)'">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Change Project Portal
                    </a>
                </div>

                <div class="form-main-header">
                    <img src="<?php echo get_template_directory_uri(); ?>/pearl-grace-logo.png" alt="Project Logo" class="form-logo" id="form-logo-img">
                </div>

                <form id="enquiry-form" class="enquiry-form" method="POST">
                    <input type="hidden" name="building_name" id="building-name-input" required>
            


            <div class="form-section card" id="section-basic">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div class="section-title">
                        <h3>Basic Information</h3>
                        <p>Please provide your personal contact details.</p>
                    </div>
                    <div class="step-count">(1/5)</div>
                </div>
                <div class="form-grid">
                    <div class="input-group">
                        <label>Date of Visit</label>
                        <input type="date" name="date" placeholder="dd/mm/yyyy" required>
                    </div>
                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="John Doe" required>
                    </div>
                    <div class="input-group">
                        <label>Contact Number</label>
                        <input type="tel" name="contact" placeholder="+91 9999999999" required>
                    </div>
                    <div class="input-group">
                        <label>Email ID</label>
                        <input type="email" name="email" placeholder="john@example.com" required>
                    </div>
                    <div class="input-group full-width">
                        <label>Residence Address</label>
                        <input type="text" name="residence" placeholder="Your current location" required>
                    </div>
                </div>
            </div>

            <div class="form-section card" id="section-occupation">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                    </div>
                    <div class="section-title">
                        <h3>Occupation</h3>
                        <p>Tell us about your professional background.</p>
                    </div>
                    <div class="step-count">(2/5)</div>
                </div>
                <div class="pill-group" id="occupation-pills">
                    <button type="button" class="pill" data-value="Service">Service</button>
                    <button type="button" class="pill" data-value="Self Employed">Self Employed</button>
                    <button type="button" class="pill" data-value="Business">Business</button>
                    <button type="button" class="pill" data-value="Profession">Profession</button>
                </div>
                <input type="hidden" name="occupation" id="occupation-input" required>
                
                <div class="form-grid mt-4">
                    <div class="input-group">
                        <label>Company Name</label>
                        <input type="text" name="company_name" placeholder="Company Name">
                    </div>
                    <div class="input-group">
                        <label>Location</label>
                        <input type="text" name="company_location" placeholder="Work Location">
                    </div>
                </div>
            </div>

            <div class="form-section card" id="section-property">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <div class="section-title">
                        <h3>Property Requirements</h3>
                        <p>What kind of property are you looking for?</p>
                    </div>
                    <div class="step-count">(3/5)</div>
                </div>
                <label class="section-label">Configuration</label>
                <div class="pill-group" id="config-pills">
                    <button type="button" class="pill" data-value="1 BHK">1 BHK</button>
                    <button type="button" class="pill" data-value="2 BHK">2 BHK</button>
                    <button type="button" class="pill" data-value="Jodi">Jodi</button>
                    <button type="button" class="pill" data-value="Commercial/Shops">Commercial/Shops</button>
                </div>
                <input type="hidden" name="configuration" id="config-input" required>

                <div id="carpet-area-wrapper" class="input-group mt-3 hidden">
                    <label>Carpet Area</label>
                    <input type="text" name="carpet_area" id="carpet-area-input" placeholder="e.g. 500 sq.ft.">
                </div>

                <label class="section-label mt-3">Budget</label>
                <div class="pill-group" id="budget-pills">
                    <button type="button" class="pill" data-value="70 L to 85 L">70 L to 85 L</button>
                    <button type="button" class="pill" data-value="85 L to 1 CR">85 L to 1 CR</button>
                    <button type="button" class="pill" data-value="1 CR to 1.25 CR">1 CR to 1.25 CR</button>
                    <button type="button" class="pill" data-value="1.25 CR to 1.50 CR">1.25 CR to 1.50 CR</button>
                    <button type="button" class="pill" data-value="1.50 CR to 1.75 CR">1.50 CR to 1.75 CR</button>
                    <button type="button" class="pill" data-value="1.75 CR to 2.00 CR">1.75 CR to 2.00 CR</button>
                    <button type="button" class="pill" data-value="2.00 CR to 2.25 CR">2.00 CR to 2.25 CR</button>
                </div>
                <input type="hidden" name="budget" id="budget-input" required>
            </div>

                  <div class="form-section card" id="section-signature">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                    </div>
                    <div class="section-title">
                        <h3>Customer Signature</h3>
                        <p>Please sign to confirm your enquiry.</p>
                    </div>
                    <div class="step-count">(4/5)</div>
                </div>
                <div class="signature-wrapper">
                    <canvas id="signature-pad"></canvas>
                    <button type="button" class="clear-btn" id="clear-signature">Clear</button>
                </div>
                <input type="hidden" name="signature" id="signature-input">
            </div>

            <div class="form-section card" id="section-source">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" x2="4" y1="22" y2="15"></line></svg>
                    </div>
                    <div class="section-title">
                        <h3>How did you hear about us?</h3>
                        <p>Help us understand where you found us.</p>
                    </div>
                    <div class="step-count">(5/5)</div>
                </div>
                <div class="pill-group" id="source-pills">
                    <button type="button" class="pill" data-value="Newspaper">Newspaper</button>
                    <button type="button" class="pill" data-value="Hoarding">Hoarding</button>
                    <button type="button" class="pill" data-value="SMS">SMS</button>
                    <button type="button" class="pill" data-value="Website">Website</button>
                    <button type="button" class="pill" data-value="Reference">Reference</button>
                    <button type="button" class="pill" data-value="Channel Partner">Channel Partner</button>
                    <button type="button" class="pill" data-value="Direct">Direct</button>
                </div>
                <input type="hidden" name="source" id="source-input" required>
                
                <div id="reference-wrapper" class="input-group mt-3 hidden">
                    <label>Reference Name</label>
                    <input type="text" name="reference_name" id="reference-name-input" placeholder="Name of the person">
                </div>

                <div id="channel-partner-wrapper" class="form-grid mt-3 hidden">
                    <div class="input-group full-width">
                        <label>Channel Partner Firm Name</label>
                        <input type="text" name="cp_firm_name" id="cp-firm-name-input" placeholder="Firm Name">
                    </div>
                    <div class="input-group">
                        <label>Channel Partner Name</label>
                        <input type="text" name="cp_name" id="cp-name-input" placeholder="Partner Name">
                    </div>
                    <div class="input-group">
                        <label>Channel Partner Contact</label>
                        <input type="tel" name="cp_contact" id="cp-contact-input" placeholder="Partner Number">
                    </div>
                </div>
            </div>

       

            <?php
            $closing_managers = get_users(array(
                'role' => 'crm_closing_manager',
                'orderby' => 'display_name',
                'order' => 'ASC'
            ));
            ?>
            <div class="form-section card" id="section-manager">
                <div class="section-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="section-title">
                        <h3>Closing Manager</h3>
                        <p>Select the Closing Manager handling this client.</p>
                    </div>
                </div>
                <div class="input-group" style="margin-top: 1.5rem;">
                    <div class="select-wrapper">
                        <select name="closing_manager_id" id="closing-manager-select" required class="custom-select">
                            <option value="" disabled selected>Choose a Closing Manager...</option>
                            <?php if (!empty($closing_managers)) : ?>
                                <?php foreach ($closing_managers as $manager) : ?>
                                    <option value="<?php echo esc_attr($manager->ID); ?>">
                                        <?php echo esc_html($manager->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <option value="" disabled>No Closing Managers found</option>
                            <?php endif; ?>
                        </select>
                        <div class="select-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>
                </div>

                <div class="input-group" style="margin-top: 1.25rem;">
                    <label>Sourcing Manager</label>
                    <input type="text" name="sourcing_manager" placeholder="e.g. Rahul Sharma">
                </div>
            </div>

            <div class="form-section card" id="section-presales">
                <div class="section-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <div class="section-title">
                        <h3>Pre-sales</h3>
                        <p>Enter the Pre-sales Representative handling this lead.</p>
                    </div>
                </div>
                <div class="input-group" style="margin-top: 1.5rem;">
                    <label>Pre-sales Representative</label>
                    <input type="text" name="pre_sales" placeholder="Pre-sales Name">
                </div>
            </div>

           

            <div class="form-actions">
                <button type="submit" class="submit-btn" id="submit-btn">
                    <span class="btn-text">Submit Enquiry</span>
                    <span class="loader hidden"></span>
                </button>
            </div>
            
            <div id="form-message" class="form-message hidden"></div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
