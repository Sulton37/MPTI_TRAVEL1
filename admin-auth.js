class AdminAuth {
    constructor() {
        this.isLoggedIn = false;
        this.adminData = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkAdminStatus();
    }

    bindEvents() {
        // Modal controls
        const adminLoginBtn = document.getElementById('admin-login-btn');
        const adminDashboardBtn = document.getElementById('admin-dashboard-btn');
        const loginModal = document.getElementById('login-modal');
        const loginClose = document.getElementById('login-close');
        const modalOverlay = document.querySelector('.modal-overlay');
        const loginForm = document.getElementById('admin-login-form');

        // Show login modal
        if (adminLoginBtn) {
            adminLoginBtn.addEventListener('click', () => this.showLoginModal());
        }

        // Go to dashboard when logged in
        if (adminDashboardBtn) {
            adminDashboardBtn.addEventListener('click', () => this.goToAdminDashboard());
        }

        // Close modal events
        if (loginClose) {
            loginClose.addEventListener('click', () => this.hideLoginModal());
        }

        if (modalOverlay) {
            modalOverlay.addEventListener('click', () => this.hideLoginModal());
        }

        // Form submission
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Keyboard events
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideLoginModal();
            }
        });
    }

    showLoginModal() {
        const modal = document.getElementById('login-modal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Focus on email input
            setTimeout(() => {
                const emailInput = document.getElementById('admin-email');
                if (emailInput) emailInput.focus();
            }, 100);
        }
    }

    hideLoginModal() {
        const modal = document.getElementById('login-modal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            this.clearForm();
            this.hideMessage();
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const email = formData.get('email');
        const password = formData.get('password');
        const remember = formData.get('remember') ? 1 : 0;

        if (!email || !password) {
            this.showMessage('Please fill in all fields', 'error');
            return;
        }

        this.showLoading(true);
        this.hideMessage();

        try {
            const response = await fetch('../../BackEnd/process_admin_login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                credentials: 'include',
                body: new URLSearchParams({
                    email: email,
                    password: password,
                    remember: remember
                })
            });

            const result = await response.text();
            
            // Check if response is redirect (successful login)
            if (response.redirected || response.url.includes('admin.php')) {
                this.handleLoginSuccess();
                return;
            }

            // Check for error in response
            if (result.includes('error=')) {
                const urlParams = new URLSearchParams(result.split('?')[1] || '');
                const error = urlParams.get('error');
                this.handleLoginError(error);
            } else {
                // Try to parse as JSON for API response
                try {
                    const jsonResult = JSON.parse(result);
                    if (jsonResult.success) {
                        this.handleLoginSuccess(jsonResult.admin);
                    } else {
                        this.handleLoginError(jsonResult.message);
                    }
                } catch {
                    this.handleLoginError('unexpected_response');
                }
            }

        } catch (error) {
            console.error('Login error:', error);
            this.handleLoginError('network_error');
        } finally {
            this.showLoading(false);
        }
    }

    handleLoginSuccess(adminData = null) {
        this.isLoggedIn = true;
        this.adminData = adminData;
        
        this.showMessage('Login successful! Redirecting to dashboard...', 'success');
        
        // Update UI
        this.updateUIForLoggedInAdmin(adminData);
        
        // Redirect to admin dashboard after short delay
        setTimeout(() => {
            window.location.href = '../../BackEnd/admin.php';
        }, 1500);
    }

    handleLoginError(errorCode) {
        let errorMessage = 'Login failed. Please try again.';
        
        switch (errorCode) {
            case 'invalid_credentials':
                errorMessage = 'Invalid email or password. Please check your credentials.';
                break;
            case 'empty_fields':
                errorMessage = 'Please fill in all required fields.';
                break;
            case 'database_error':
                errorMessage = 'Database connection error. Please try again later.';
                break;
            case 'network_error':
                errorMessage = 'Network error. Please check your connection.';
                break;
            case 'unexpected_response':
                errorMessage = 'Unexpected server response. Please contact administrator.';
                break;
        }
        
        this.showMessage(errorMessage, 'error');
    }

    updateUIForLoggedInAdmin(adminData) {
        const adminLoginBtn = document.getElementById('admin-login-btn');
        const adminDashboardBtn = document.getElementById('admin-dashboard-btn');
        const adminStatusLinks = document.querySelectorAll('.admin-status');
        
        // Hide login button, show dashboard button
        if (adminLoginBtn) adminLoginBtn.classList.add('hidden');
        if (adminDashboardBtn) adminDashboardBtn.classList.remove('hidden');
        
        // Update admin status links
        adminStatusLinks.forEach(link => {
            link.classList.remove('hidden');
            link.textContent = `👤 ${adminData?.name || 'Admin'}`;
            link.href = '../../BackEnd/admin.php';
        });
    }

    async checkAdminStatus() {
        try {
            const response = await fetch('../../BackEnd/check_admin_session.php', {
                method: 'GET',
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.success && result.admin) {
                this.isLoggedIn = true;
                this.adminData = result.admin;
                this.updateUIForLoggedInAdmin(result.admin);
            }
        } catch (error) {
            console.error('Error checking admin status:', error);
        }
    }

    showLoading(show) {
        const loading = document.getElementById('login-loading');
        const form = document.getElementById('admin-login-form');
        
        if (loading && form) {
            if (show) {
                loading.classList.remove('hidden');
                form.classList.add('hidden');
            } else {
                loading.classList.add('hidden');
                form.classList.remove('hidden');
            }
        }
    }

    showMessage(message, type) {
        const messageEl = document.getElementById('login-message');
        if (messageEl) {
            messageEl.textContent = message;
            messageEl.className = `login-message ${type}`;
            messageEl.classList.remove('hidden');
        }
    }

    hideMessage() {
        const messageEl = document.getElementById('login-message');
        if (messageEl) {
            messageEl.classList.add('hidden');
        }
    }

    clearForm() {
        const form = document.getElementById('admin-login-form');
        if (form) {
            form.reset();
        }
    }

    // Action functions for logged in admin
    goToAdminDashboard() {
        window.location.href = '../../BackEnd/admin.php';
    }

    managePackages() {
        window.location.href = '../../BackEnd/admin.php#packages';
    }

    async logoutAdmin() {
        if (!confirm('Are you sure you want to logout?')) {
            return;
        }

        try {
            const response = await fetch('../../BackEnd/admin_logout.php', {
                method: 'POST',
                credentials: 'include'
            });

            const result = await response.json();

            if (result.success) {
                this.isLoggedIn = false;
                this.adminData = null;
                this.hideLoginModal();
                
                // Reset UI
                const adminLoginBtn = document.getElementById('admin-login-btn');
                const adminDashboardBtn = document.getElementById('admin-dashboard-btn');
                const adminStatusLinks = document.querySelectorAll('.admin-status');
                
                if (adminLoginBtn) adminLoginBtn.classList.remove('hidden');
                if (adminDashboardBtn) adminDashboardBtn.classList.add('hidden');
                
                adminStatusLinks.forEach(link => {
                    link.classList.add('hidden');
                });
                
                alert('Logged out successfully!');
            }
        } catch (error) {
            console.error('Logout error:', error);
            alert('Error logging out. Please try again.');
        }
    }
}

// Global functions for modal actions
function toggleAdminPassword() {
    const passwordInput = document.getElementById('admin-password');
    const toggleIcon = document.querySelector('.password-toggle i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function goToAdminDashboard() {
    window.location.href = '../../BackEnd/admin.php';
}

function managePackages() {
    window.location.href = '../../BackEnd/admin.php#packages';
}

function logoutAdmin() {
    if (window.adminAuth) {
        window.adminAuth.logoutAdmin();
    }
}

// Initialize admin authentication
document.addEventListener('DOMContentLoaded', function() {
    window.adminAuth = new AdminAuth();
});
