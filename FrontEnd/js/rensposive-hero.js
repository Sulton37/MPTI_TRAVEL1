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