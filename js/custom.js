function initCRMForm() {
    // Pill selection logic
    function setupPills(groupId, inputId, onChange) {
        const group = document.getElementById(groupId);
        if(!group) return;
        const pills = group.querySelectorAll('.pill');
        const input = document.getElementById(inputId);

        pills.forEach(pill => {
            pill.addEventListener('click', function(e) {
                e.preventDefault();
                pills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                input.value = this.dataset.value;
                if (onChange) {
                    onChange(this.dataset.value);
                }
            });
        });
    }

    setupPills('occupation-pills', 'occupation-input');
    setupPills('config-pills', 'config-input');
    setupPills('budget-pills', 'budget-input');
    setupPills('source-pills', 'source-input', function(value) {
        const refWrapper = document.getElementById('reference-wrapper');
        const refInput = document.getElementById('reference-name-input');
        if (value === 'Reference') {
            refWrapper.classList.remove('hidden');
            refInput.setAttribute('required', 'required');
        } else {
            refWrapper.classList.add('hidden');
            refInput.removeAttribute('required');
            refInput.value = '';
        }
    });

    // Scroll Tracker Logic
    const sections = document.querySelectorAll('.form-section');
    const navSteps = document.querySelectorAll('.nav-step');
    const tracker = document.querySelector('.nav-tracker');
    
    if (sections.length && navSteps.length && tracker) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const currentId = entry.target.id;
                    let activeIndex = 0;
                    
                    navSteps.forEach((step, index) => {
                        step.classList.remove('active', 'completed');
                        if (step.dataset.target === currentId) {
                            step.classList.add('active');
                            activeIndex = index;
                        }
                    });
                    
                    navSteps.forEach((step, index) => {
                        if (index < activeIndex) {
                            step.classList.add('completed');
                        }
                    });
                    
                    // Calculate line height/width
                    const p = activeIndex / (navSteps.length - 1);
                    tracker.style.setProperty('--tracker-progress', p);
                }
            });
        }, {
            rootMargin: '-20% 0px -60% 0px',
            threshold: 0
        });
        
        sections.forEach(sec => observer.observe(sec));

        // Fallback for reaching the absolute bottom of the page
        window.addEventListener('scroll', () => {
            if ((window.innerHeight + window.scrollY) >= document.documentElement.scrollHeight - 10) {
                navSteps.forEach(s => s.classList.remove('active', 'completed'));
                navSteps.forEach((step, index) => {
                    if (index === navSteps.length - 1) {
                        step.classList.add('active');
                    } else {
                        step.classList.add('completed');
                    }
                });
                tracker.style.setProperty('--tracker-progress', 1);
            }
        });

        // Allow clicking on sidebar to scroll to section
        navSteps.forEach(step => {
            step.addEventListener('click', () => {
                const targetId = step.dataset.target;
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    // Signature Pad logic
    const canvas = document.getElementById('signature-pad');
    let ctx;
    if(canvas) {
        ctx = canvas.getContext('2d');
        let isDrawing = false;
        
        // Resize canvas to match display size
        function resizeCanvas() {
            const rect = canvas.parentElement.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = 200;
        }
        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        function getPosition(e) {
            const rect = canvas.getBoundingClientRect();
            let clientX = e.clientX;
            let clientY = e.clientY;
            
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            }
            
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDrawing(e) {
            if (e.cancelable) e.preventDefault();
            isDrawing = true;
            const pos = getPosition(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
            if (!isDrawing) return;
            if (e.cancelable) e.preventDefault();
            const pos = getPosition(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.strokeStyle = '#222';
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.stroke();
        }

        function stopDrawing() {
            if(isDrawing) {
                ctx.closePath();
                isDrawing = false;
                document.getElementById('signature-input').value = canvas.toDataURL();
            }
        }

        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('mouseout', stopDrawing);

        canvas.addEventListener('touchstart', startDrawing, {passive: false});
        canvas.addEventListener('touchmove', draw, {passive: false});
        canvas.addEventListener('touchend', stopDrawing);

        document.getElementById('clear-signature').addEventListener('click', (e) => {
            e.preventDefault();
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            document.getElementById('signature-input').value = '';
        });
    }

    // Form Submission Logic
    const form = document.getElementById('enquiry-form');
    if(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validation
            const occupation = document.getElementById('occupation-input').value;
            const config = document.getElementById('config-input').value;
            const budget = document.getElementById('budget-input').value;
            const source = document.getElementById('source-input').value;
            
            let errors = [];
            if(!occupation) errors.push('Please select Occupation.');
            if(!config) errors.push('Please select Configuration.');
            if(!budget) errors.push('Please select Budget.');
            if(!source) errors.push('Please select Source.');

            function showMessage(msg, type) {
                const messageEl = document.getElementById('form-message');
                messageEl.textContent = msg;
                messageEl.className = 'form-message ' + type;
                messageEl.classList.remove('hidden');
                
                // Clear any existing timeout
                if (window.toastTimeout) clearTimeout(window.toastTimeout);
                
                // Auto hide after 4 seconds
                window.toastTimeout = setTimeout(() => {
                    messageEl.classList.add('hidden');
                }, 4000);
            }
            
            if(errors.length > 0) {
                showMessage(errors.join(' '), 'error');
                return;
            }

            const btnText = document.querySelector('.btn-text');
            const loader = document.querySelector('.loader');
            
            btnText.textContent = 'Submitting...';
            loader.classList.remove('hidden');
            document.getElementById('submit-btn').disabled = true;

            const formData = new FormData(form);
            formData.append('action', 'submit_enquiry');

            fetch(crmAjax.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnText.textContent = 'Submit Enquiry';
                loader.classList.add('hidden');
                document.getElementById('submit-btn').disabled = false;

                if(data.success) {
                    showMessage(data.data.message, 'success');
                    form.reset();
                    // Reset pills
                    document.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
                    document.getElementById('occupation-input').value = '';
                    document.getElementById('config-input').value = '';
                    document.getElementById('budget-input').value = '';
                    document.getElementById('source-input').value = '';

                    // Hide reference wrapper
                    const refWrapper = document.getElementById('reference-wrapper');
                    if (refWrapper) {
                        refWrapper.classList.add('hidden');
                        document.getElementById('reference-name-input').removeAttribute('required');
                    }

                    // Clear signature
                    if(canvas) {
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                        document.getElementById('signature-input').value = '';
                    }
                } else {
                    showMessage(data.data.message || 'An error occurred.', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                btnText.textContent = 'Submit Enquiry';
                loader.classList.add('hidden');
                document.getElementById('submit-btn').disabled = false;
                
                showMessage('A network error occurred.', 'error');
            });
        });
    }

    // PWA Pull-to-Refresh Logic
    let touchStartY = 0;
    let isPulling = false;
    const pwaRefreshThreshold = 80;
    
    const refreshIndicator = document.createElement('div');
    refreshIndicator.className = 'pwa-refresh-indicator';
    refreshIndicator.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.59-8.27l-5.67 5.67"/></svg>';
    document.body.prepend(refreshIndicator);

    document.addEventListener('touchstart', e => {
        if (window.scrollY <= 0) {
            touchStartY = e.touches[0].clientY;
            isPulling = true;
            refreshIndicator.style.transition = 'none';
        }
    }, { passive: true });

    document.addEventListener('touchmove', e => {
        if (!isPulling) return;
        const pullDistance = e.touches[0].clientY - touchStartY;
        
        if (pullDistance > 0 && window.scrollY <= 0) {
            const transformY = Math.min(pullDistance / 2.5, pwaRefreshThreshold + 20);
            refreshIndicator.style.transform = `translateY(${transformY}px) translateX(-50%) rotate(${pullDistance}deg)`;
            refreshIndicator.style.opacity = Math.min(pullDistance / 80, 1);
            
            if (transformY >= pwaRefreshThreshold) {
                refreshIndicator.classList.add('ready');
            } else {
                refreshIndicator.classList.remove('ready');
            }
        }
    }, { passive: true });

    document.addEventListener('touchend', e => {
        if (!isPulling) return;
        isPulling = false;
        
        refreshIndicator.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
        
        const pullDistance = e.changedTouches[0].clientY - touchStartY;
        const transformY = Math.min(pullDistance / 2.5, pwaRefreshThreshold + 20);
        
        if (transformY >= pwaRefreshThreshold && window.scrollY <= 0) {
            refreshIndicator.classList.add('refreshing');
            refreshIndicator.style.transform = `translateY(50px) translateX(-50%)`;
            refreshIndicator.style.opacity = 1;
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            refreshIndicator.style.transform = `translateY(-50px) translateX(-50%) rotate(0deg)`;
            refreshIndicator.style.opacity = 0;
            refreshIndicator.classList.remove('ready');
        }
    });

}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCRMForm);
} else {
    initCRMForm();
}
