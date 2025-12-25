// atlas.js - Session Management for BookEasy
// Handles user authentication, navigation bar updates, and checkout page access control

// Check if user is logged in (sessionStorage fallback to localStorage)
function isUserLoggedIn() {
    const sess = sessionStorage.getItem('currentUser');
    const local = localStorage.getItem('current_user');
    return !!(sess || local);
}

// Get current user data (prefers sessionStorage, falls back to localStorage)
function getCurrentUser() {
    const sess = sessionStorage.getItem('currentUser');
    if (sess) return JSON.parse(sess);
    const local = localStorage.getItem('current_user');
    return local ? JSON.parse(local) : null;
}

// Ensure sessionStorage has the user if only localStorage exists
function ensureSessionFromLocal() {
    const hasSess = !!sessionStorage.getItem('currentUser');
    const local = localStorage.getItem('current_user');
    if (!hasSess && local) {
        sessionStorage.setItem('currentUser', local);
    }
}

// Update navigation bar based on login status
function updateNavigationBar() {
    const navBars = document.querySelectorAll('nav');
    
    navBars.forEach(nav => {
        const loginLink = nav.querySelector('a[href="login.html"]');
        
        if (loginLink) {
            if (isUserLoggedIn()) {
                const user = getCurrentUser();
                const emailPart = (user && user.email) ? user.email.split('@')[0] : 'User';
                const userName = (user && (user.name || user.fullName)) ? (user.name || user.fullName) : emailPart;
                
                // Replace login link with welcome message and profile link
                loginLink.outerHTML = `
                    <a href="profile.html" style="font-weight: bold;">Welcome, ${userName}</a>
                    <a href="#" onclick="logout(); return false;">Logout</a>
                `;
            }
        }
    });
}

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        // Clear both session and local copies so nav refresh shows logged-out state
        sessionStorage.removeItem('currentUser');
        localStorage.removeItem('current_user');
        alert('You have been logged out successfully.');
        window.location.href = 'Project.html';
    }
}

// Check if user is logged in on checkout page
function checkLoginForCheckout() {
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('Checkout.html') || currentPage.endsWith('Checkout.html')) {
        if (!isUserLoggedIn()) {
            alert('Please login to proceed with checkout.');
            // Save the current booking room data to restore after login
            const bookingRoom = sessionStorage.getItem('bookingRoom');
            if (bookingRoom) {
                sessionStorage.setItem('pendingBooking', bookingRoom);
            }
            window.location.href = 'login.html';
            return false;
        }
        return true;
    }
    return true;
}

// Restore pending booking after login
function restorePendingBooking() {
    if (!isUserLoggedIn()) return; // only restore when logged in
    const pendingBooking = sessionStorage.getItem('pendingBooking');
    if (pendingBooking) {
        sessionStorage.setItem('bookingRoom', pendingBooking);
        sessionStorage.removeItem('pendingBooking');
        window.location.href = 'Checkout.html';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Sync session from local if needed
    ensureSessionFromLocal();
    // Update navigation bar on all pages
    updateNavigationBar();
    
    // Check login for checkout page
    checkLoginForCheckout();
    
    // Check for pending booking (after login)
    const currentPage = window.location.pathname;
    if (currentPage.includes('Project.html') || currentPage.endsWith('Project.html')) {
        restorePendingBooking();
    }
});

// Auto-fill guest information on checkout if logged in
function autoFillGuestInfo() {
    if (isUserLoggedIn()) {
        const user = getCurrentUser();
        
        const fullNameField = document.getElementById('fullName');
        const emailField = document.getElementById('email');
        const phoneField = document.getElementById('phone');
        
        if (fullNameField && user.name) {
            fullNameField.value = user.name || user.fullName || '';
        }
        
        if (emailField && user.email) {
            emailField.value = user.email || '';
        }
        
        if (phoneField && user.phone) {
            phoneField.value = user.phone || '';
        }
    }
}
