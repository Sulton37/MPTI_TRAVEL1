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