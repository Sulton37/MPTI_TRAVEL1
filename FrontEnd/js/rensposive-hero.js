// responsive-hero.js
document.addEventListener('DOMContentLoaded', function() {
    const hero = document.querySelector('.hero');
    if (!hero) return;
    
    function adjustHeroHeight() {
        if (window.innerWidth <= 768) {
            hero.style.height = '60vh';
        } else {
            hero.style.height = '100vh';
        }
    }
    
    // Adjust video sizing for different aspect ratios
    function adjustVideoSize() {
        const video = hero.querySelector('video');
        if (!video) return;
        
        const aspectRatio = window.innerWidth / window.innerHeight;
        
        if (aspectRatio < 16/9) {
            video.style.width = 'auto';
            video.style.height = '100%';
        } else {
            video.style.width = '100%';
            video.style.height = 'auto';
        }
    }
    
    adjustHeroHeight();
    adjustVideoSize();
    
    window.addEventListener('resize', () => {
        adjustHeroHeight();
        adjustVideoSize();
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const logo = document.querySelector('header .logo');
    
    if (logo) {
        // Add mouse move parallax effect
        logo.addEventListener('mousemove', (e) => {
            const xAxis = (e.offsetX - logo.offsetWidth / 2) / 20;
            const yAxis = (e.offsetY - logo.offsetHeight / 2) / 20;
            logo.style.transform = `perspective(500px) rotateY(${xAxis}deg) rotateX(${-yAxis}deg)`;
        });
        
        // Reset on mouse leave
        logo.addEventListener('mouseleave', () => {
            logo.style.transform = 'perspective(500px) rotateY(0) rotateX(0)';
        });
        
        // Add click effect
        logo.addEventListener('click', () => {
            logo.classList.add('logo-clicked');
            setTimeout(() => {
                logo.classList.remove('logo-clicked');
            }, 1000);
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    console.log('🎥 Responsive hero initialized');
    
    // Hero video responsive handling
    const heroVideo = document.querySelector('.hero video');
    const hero = document.querySelector('.hero');
    
    if (heroVideo && hero) {
        // Ensure video maintains aspect ratio
        function adjustVideoSize() {
            const heroHeight = hero.offsetHeight;
            const heroWidth = hero.offsetWidth;
            
            heroVideo.style.minWidth = heroWidth + 'px';
            heroVideo.style.minHeight = heroHeight + 'px';
        }
        
        // Adjust on load and resize
        adjustVideoSize();
        window.addEventListener('resize', adjustVideoSize);
        
        // Fallback for video loading issues
        heroVideo.addEventListener('error', function() {
            console.warn('⚠️ Hero video failed to load');
            hero.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        });
        
        // Pause video when not visible (performance optimization)
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    heroVideo.play().catch(e => console.log('Video autoplay prevented'));
                } else {
                    heroVideo.pause();
                }
            });
        });
        
        observer.observe(hero);
    }
    
    // Hero content animations
    const heroContent = document.querySelectorAll('.hero h1, .hero p, .hero .cta-button');
    heroContent.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.8s ease-out';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, 500 + (index * 200));
    });
});