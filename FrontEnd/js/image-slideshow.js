// image-slideshow.js
document.addEventListener('DOMContentLoaded', function() {
    const imageSlideshows = document.querySelectorAll('.image-slideshow');
    if (!imageSlideshows.length) return;
    
    // Initialize each slideshow
    imageSlideshows.forEach(slideshow => {
        const images = slideshow.querySelectorAll('img');
        if (images.length <= 1) return;
        
        let currentIndex = 0;
        
        // Function to cycle images
        function cycleImages() {
            images.forEach((img, index) => {
                img.style.opacity = index === currentIndex ? '1' : '0';
                img.style.transform = index === currentIndex ? 'scale(1)' : 'scale(1.05)';
            });
            
            currentIndex = (currentIndex + 1) % images.length;
        }
        
        // Start cycling on hover (desktop) or always on mobile
        if (window.innerWidth > 768) {
            slideshow.addEventListener('mouseenter', () => {
                slideshow.interval = setInterval(cycleImages, 3000);
            });
            
            slideshow.addEventListener('mouseleave', () => {
                clearInterval(slideshow.interval);
                images.forEach(img => {
                    img.style.opacity = '0';
                    img.style.transform = 'scale(1.05)';
                });
                images[0].style.opacity = '1';
                images[0].style.transform = 'scale(1)';
                currentIndex = 0;
            });
        } else {
            // On mobile, auto-cycle with longer interval
            slideshow.interval = setInterval(cycleImages, 5000);
        }
        
        // Adjust image height based on aspect ratio
        function adjustImageHeight() {
            if (window.innerWidth <= 768) {
                slideshow.style.height = '200px';
            } else {
                slideshow.style.height = '300px';
            }
        }
        
        adjustImageHeight();
        window.addEventListener('resize', adjustImageHeight);
    });
});