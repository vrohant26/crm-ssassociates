<?php get_header(); ?>

<main class="site-main app-main">
    <div class="app-layout">
        <aside class="sidebar-nav">
            <ul class="nav-tracker">
                <li class="nav-step active" data-target="section-basic">
                    <div class="step-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                    <span class="step-label">Basic Info</span>
                </li>
                <li class="nav-step" data-target="section-occupation">
                    <div class="step-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg></div>
                    <span class="step-label">Occupation</span>
                </li>
                <li class="nav-step" data-target="section-property">
                    <div class="step-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div>
                    <span class="step-label">Property</span>
                </li>
                <li class="nav-step" data-target="section-source">
                    <div class="step-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" x2="4" y1="22" y2="15"></line></svg></div>
                    <span class="step-label">Source</span>
                </li>
                <li class="nav-step" data-target="section-partner">
                    <div class="step-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                    <span class="step-label">Partner</span>
                </li>
                <li class="nav-step" data-target="section-signature">
                    <div class="step-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg></div>
                    <span class="step-label">Signature</span>
                </li>
            </ul>
        </aside>

        <div class="app-container">
            <form id="enquiry-form" class="enquiry-form" method="POST">
            
            <div class="form-section card" id="section-basic">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                    <div class="section-title">
                        <h3>Basic Information</h3>
                        <p>Please provide your personal contact details.</p>
                    </div>
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
                </div>
                <label class="section-label">Configuration</label>
                <div class="pill-group" id="config-pills">
                    <button type="button" class="pill" data-value="1 BHK">1 BHK</button>
                    <button type="button" class="pill" data-value="2 BHK">2 BHK</button>
                </div>
                <input type="hidden" name="configuration" id="config-input" required>

                <label class="section-label mt-3">Budget</label>
                <div class="pill-group" id="budget-pills">
                    <button type="button" class="pill" data-value="70 L to 85 L">70 L to 85 L</button>
                    <button type="button" class="pill" data-value="85 L to 1 CR">85 L to 1 CR</button>
                    <button type="button" class="pill" data-value="1 CR to 1.25 CR">1 CR to 1.25 CR</button>
                </div>
                <input type="hidden" name="budget" id="budget-input" required>
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
                </div>
                <div class="pill-group" id="source-pills">
                    <button type="button" class="pill" data-value="Newspaper">Newspaper</button>
                    <button type="button" class="pill" data-value="Hoarding">Hoarding</button>
                    <button type="button" class="pill" data-value="SMS">SMS</button>
                    <button type="button" class="pill" data-value="Website">Website</button>
                    <button type="button" class="pill" data-value="Reference">Reference</button>
                </div>
                <input type="hidden" name="source" id="source-input" required>
                
                <div id="reference-wrapper" class="input-group mt-3 hidden">
                    <label>Reference Name</label>
                    <input type="text" name="reference_name" id="reference-name-input" placeholder="Name of the person">
                </div>
            </div>

            <div class="form-section card" id="section-partner">
                <div class="section-header">
                    <div class="section-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <div class="section-title">
                        <h3>Channel Partner <span style="font-weight: normal; font-size: 0.9rem; color: #94a3b8;">(Optional)</span></h3>
                        <p>Details of the channel partner assisting you.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="input-group">
                        <label>Partner Name</label>
                        <input type="text" name="cp_name" placeholder="Name">
                    </div>
                    <div class="input-group">
                        <label>Partner Contact</label>
                        <input type="tel" name="cp_contact" placeholder="Number">
                    </div>
                </div>
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
                </div>
                <div class="signature-wrapper">
                    <canvas id="signature-pad"></canvas>
                    <button type="button" class="clear-btn" id="clear-signature">Clear</button>
                </div>
                <input type="hidden" name="signature" id="signature-input">
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
</main>

<?php get_footer(); ?>
