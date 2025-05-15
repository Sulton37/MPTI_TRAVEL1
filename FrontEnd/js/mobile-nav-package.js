// Mobile Navigation
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mobileSidebar = document.querySelector('.mobile-sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    mobileToggle.addEventListener('click', function() {
        document.body.classList.toggle('sidebar-open');
        mobileSidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        mobileToggle.classList.toggle('active');
    });
    
    sidebarOverlay.addEventListener('click', function() {
        document.body.classList.remove('sidebar-open');
        mobileSidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        mobileToggle.classList.remove('active');
    });
    
    // Header scroll effect
    const header = document.querySelector('header');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
    
    // Accordion functionality for itinerary days
    const dayHeaders = document.querySelectorAll('.day-header');
    
    dayHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const isExpanded = content.classList.contains('expanded');
            
            // Close all other open sections
            document.querySelectorAll('.day-content').forEach(item => {
                if (item !== content) {
                    item.classList.remove('expanded');
                    item.previousElementSibling.classList.remove('active');
                }
            });
            
            // Toggle current section
            this.classList.toggle('active');
            content.classList.toggle('expanded');
            
            // Smooth scroll to expanded section
            if (!isExpanded) {
                setTimeout(() => {
                    content.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 350);
            }
        });
    });
    
    // Open first day by default
    if (dayHeaders.length > 0) {
        dayHeaders[0].click();
    }
    
    // Add floating book now button
    const floatingBook = document.createElement('a');
    floatingBook.href = '#book-now';
    floatingBook.className = 'floating-book';
    floatingBook.innerHTML = '<i class="fas fa-calendar-check"></i> Book Now';
    document.body.appendChild(floatingBook);
    
    // Smooth scrolling for all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Logo click animation
    const logo = document.querySelector('.logo');
    if (logo) {
        logo.addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.add('logo-clicked');
            setTimeout(() => {
                this.classList.remove('logo-clicked');
            }, 1000);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Gallery hover effect
    const galleryItems = document.querySelectorAll('.gallery-item');
    galleryItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.querySelector('img').style.transform = 'scale(1.1)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.querySelector('img').style.transform = 'scale(1)';
        });
    });
    
    // Animation on scroll
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.package-section, .gallery-section');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.3;
            
            if (elementPosition < screenPosition) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    // Set initial state
    document.querySelectorAll('.package-section, .gallery-section').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
    });
    
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Run once on load
});