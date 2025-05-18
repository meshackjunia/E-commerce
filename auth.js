document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    const authForms = document.querySelectorAll('.auth-form');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tab = this.dataset.tab;
            
            // Update active tab
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Show corresponding form
            authForms.forEach(form => form.classList.remove('active'));
            document.getElementById(`${tab}-form`).classList.add('active');
        });
    });
    
    // Form submissions
    const loginForm = document.getElementById('login-form-submit');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Handle login
        });
    }
    
    const registerForm = document.getElementById('register-form-submit');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Handle registration
        });
    }
});