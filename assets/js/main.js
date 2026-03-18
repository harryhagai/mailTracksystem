// MailTrack System - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle (desktop collapse + mobile drawer)
    const body = document.body;
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const overlay = document.getElementById('appOverlay');
    const appShell = document.getElementById('appShell');

    if (sidebarToggle && appShell) {
        const storedCollapsed = localStorage.getItem('sidebar-collapsed');
        if (storedCollapsed === 'true') {
            body.classList.add('sidebar-collapsed');
        }

        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth >= 992) {
                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', body.classList.contains('sidebar-collapsed'));
            } else {
                body.classList.add('sidebar-open');
            }
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
            body.classList.remove('sidebar-open');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            body.classList.remove('sidebar-open');
        });
    }

    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            body.classList.remove('sidebar-open');
        }
    });
    
    // Add spinner to buttons on submit
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.classList.contains('btn-loading')) {
                // Store original text
                const originalText = submitBtn.innerHTML;
                submitBtn.setAttribute('data-original-text', originalText);
                
                // Add spinner class
                submitBtn.classList.add('btn-loading');
                submitBtn.disabled = true;
                
                // Optional: Add a timeout to prevent stuck spinners
                setTimeout(function() {
                    if (submitBtn.classList.contains('btn-loading')) {
                        submitBtn.classList.remove('btn-loading');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }, 10000);
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert.parentNode) {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }
        }, 5000);
    });
    
    // Confirm delete
    const deleteLinks = document.querySelectorAll('a[href*="delete_email"]');
    deleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this email?')) {
                e.preventDefault();
            }
        });
    });
    
    // Set minimum date to today for date inputs
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(function(input) {
        input.setAttribute('min', today);
    });
    
    // Form validation
    const validationForms = document.querySelectorAll('.needs-validation');
    validationForms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Table row hover effect
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(function(row) {
        row.addEventListener('click', function() {
            // Optional: Add click functionality
        });
    });
    
    // Search functionality (if search input exists)
    const searchInput = document.getElementById('searchEmail');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.querySelector('table');
            if (!table) return;
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                const email = row.textContent.toLowerCase();
                if (email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Refresh page indicator
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            location.reload();
        });
    }
    
    // Due date change handler
    const dueDateInputs = document.querySelectorAll('input[name="due_date"]');
    dueDateInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                console.log('Warning: Selected date is in the past');
            }
        });
    });

    // Emails page: custom modal handling
    const modalTriggers = document.querySelectorAll('[data-modal-target]');
    const modals = document.querySelectorAll('.mt-modal');

    function openModal(modalEl) {
        if (!modalEl) return;
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
        modalEl.classList.add('is-open');
        modalEl.style.display = 'block';
        modalEl.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.remove('is-open');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    modalTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function(e) {
            const target = trigger.getAttribute('data-modal-target');
            if (!target) return;
            const modalEl = document.querySelector(target);
            if (!modalEl) return;

            if (target === '#editEmailModal') {
                const id = trigger.getAttribute('data-id') || '';
                const email = trigger.getAttribute('data-email') || '';
                const date = trigger.getAttribute('data-date') || '';
                const idInput = modalEl.querySelector('#edit-email-id');
                const emailInput = modalEl.querySelector('#edit-email');
                const dateInput = modalEl.querySelector('#edit-date');
                if (idInput) idInput.value = id;
                if (emailInput) emailInput.value = email;
                if (dateInput) dateInput.value = date;
            }

            e.preventDefault();
            openModal(modalEl);
        });
    });

    document.addEventListener('click', function(e) {
        const closeTrigger = e.target.closest('[data-modal-close]');
        if (!closeTrigger) return;
        const modalEl = closeTrigger.closest('.mt-modal');
        closeModal(modalEl);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        const openModalEl = document.querySelector('.mt-modal.is-open');
        closeModal(openModalEl);
    });

    if (window.MailTrackEditPrefill) {
        const modalEl = document.getElementById('editEmailModal');
        if (modalEl) {
            const data = window.MailTrackEditPrefill;
            const idInput = modalEl.querySelector('#edit-email-id');
            const emailInput = modalEl.querySelector('#edit-email');
            const dateInput = modalEl.querySelector('#edit-date');
            if (idInput) idInput.value = data.id || '';
            if (emailInput) emailInput.value = data.email || '';
            if (dateInput) dateInput.value = data.due_date || '';
            openModal(modalEl);
            window.MailTrackEditPrefill = null;
        }
    }
    
    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(function(btn) {
        if (btn.classList.contains('btn-no-ripple')) {
            return;
        }
        btn.style.position = 'relative';
        btn.style.overflow = 'hidden';
    });
});

// Utility function to format date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Utility function to get days until due
function getDaysUntilDue(dateString) {
    const dueDate = new Date(dateString);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    dueDate.setHours(0, 0, 0, 0);
    
    const diffTime = dueDate - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays < 0) {
        return Math.abs(diffDays) + ' days overdue';
    } else if (diffDays === 0) {
        return 'Due today';
    } else {
        return diffDays + ' days left';
    }
}

// Loading spinner function (can be called manually)
function showLoading(btn) {
    const originalText = btn.innerHTML;
    btn.setAttribute('data-original-text', originalText);
    btn.classList.add('btn-loading');
    btn.disabled = true;
}

function hideLoading(btn) {
    btn.classList.remove('btn-loading');
    btn.disabled = false;
    const originalText = btn.getAttribute('data-original-text');
    if (originalText) {
        btn.innerHTML = originalText;
    }
}
