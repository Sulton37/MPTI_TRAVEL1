// mobile-nav.js
document.addEventListener('DOMContentLoaded', function() {
    // Select DOM elements
    const toggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.mobile-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    // Function to toggle sidebar
    function toggleSidebar() {
        document.body.classList.toggle('sidebar-open');
    }
    
    // Function to close sidebar
    function closeSidebar() {
        document.body.classList.remove('sidebar-open');
    }
    
    // Event listeners
    if (toggle) {
        toggle.addEventListener('click', toggleSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar when clicking on navigation links
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', closeSidebar);
    });
    
    // Responsive check for mobile menu
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            if (toggle) toggle.style.display = 'flex';
        } else {
            if (toggle) toggle.style.display = 'none';
            closeSidebar();
        }
    }
    
    // Initial check and window resize listener
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
});