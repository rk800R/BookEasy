// Admin Login JavaScript - Frontend Logic
// Handles admin authentication with backend

/**
 * Validates admin login form
 * @param {string} email - Admin email
 * @param {string} password - Admin password
 * @returns {Object} Validation result
 */
function validateAdminLogin(email, password) {
    const errors = [];
    
    if (!email || email.trim() === '') {
        errors.push('Email is required');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.push('Please enter a valid email address');
    }
    
    if (!password || password === '') {
        errors.push('Password is required');
    } else if (password.length < 6) {
        errors.push('Password must be at least 6 characters');
    }
    
    return {
        valid: errors.length === 0,
        errors: errors
    };
}

/**
 * Authenticates admin with backend
 * @param {string} email - Admin email
 * @param {string} password - Admin password
 * @returns {Promise} Authentication result
 */
async function authenticateAdmin(email, password) {
    try {
        console.log('Sending auth request to admin_login.php', {email, action: 'login'});
        const response = await fetch('admin_login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                password: password,
                action: 'login'
            })
        });
        
        console.log('Response status:', response.status);
        
        // Always parse JSON regardless of HTTP status code
        const data = await response.json();
        console.log('Response data:', data);
        return data;
        
    } catch (error) {
        console.error('Authentication error:', error);
        console.error('Error message:', error.message);
        console.error('Stack:', error.stack);
        return {
            success: false,
            message: 'Connection error. Please check the browser console for details.'
        };
    }
}

/**
 * Handles admin login form submission
 */
async function handleAdminLogin() {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const msgEl = document.getElementById('msg');
    
    if (!emailInput || !passwordInput || !msgEl) {
        console.error('Required form elements not found');
        return;
    }
    
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    
    // Clear previous messages
    msgEl.textContent = '';
    msgEl.style.color = '';
    
    // Validate input
    const validation = validateAdminLogin(email, password);
    if (!validation.valid) {
        msgEl.style.color = 'red';
        msgEl.textContent = validation.errors.join('. ');
        return;
    }
    
    // Show loading message
    msgEl.style.color = 'blue';
    msgEl.textContent = 'Authenticating...';
    
    // Authenticate with backend
    const result = await authenticateAdmin(email, password);
    
    if (result.success) {
        // Store admin session
        sessionStorage.setItem('adminLoggedIn', 'true');
        sessionStorage.setItem('adminEmail', email);
        sessionStorage.setItem('adminName', result.adminName || 'Admin');
        sessionStorage.setItem('adminId', result.adminId || '');
        
        msgEl.style.color = 'green';
        msgEl.textContent = result.message || 'Login successful. Redirecting...';
        
        // Redirect to admin panel
        setTimeout(() => {
            window.location.href = 'AdminControl.html';
        }, 1000);
    } else {
        msgEl.style.color = 'red';
        msgEl.textContent = result.message || 'Login failed. Please check your credentials.';
    }
}

/**
 * Checks if admin is already logged in
 */
function checkAdminSession() {
    const isLoggedIn = sessionStorage.getItem('adminLoggedIn');
    if (isLoggedIn === 'true') {
        // Optionally redirect to admin panel if already logged in
        // window.location.href = 'AdminControl.html';
    }
}

/**
 * Logout admin
 */
function logoutAdmin() {
    sessionStorage.removeItem('adminLoggedIn');
    sessionStorage.removeItem('adminEmail');
    sessionStorage.removeItem('adminName');
    sessionStorage.removeItem('adminId');
    window.location.href = 'adminlogin.html';
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    checkAdminSession();
    
    const loginBtn = document.getElementById('btnLogin');
    if (loginBtn) {
        loginBtn.addEventListener('click', handleAdminLogin);
    }
    
    // Allow Enter key to submit
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleAdminLogin();
            }
        });
    }
});
