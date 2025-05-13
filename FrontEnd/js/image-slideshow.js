document.addEventListener('DOMContentLoaded', function () {
    const slideshows = document.querySelectorAll('.image-slideshow');

    slideshows.forEach(slideshow => {
        const images = slideshow.querySelectorAll('img');
        if (images.length <= 1) return;

        let currentIndex = 0;

        // Styling awal
        images.forEach((img, i) => {
            img.style.position = 'absolute';
            img.style.top = 0;
            img.style.left = 0;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.transition = 'opacity 1s ease, transform 1s ease';
            img.style.opacity = i === 0 ? '1' : '0';
            img.style.transform = i === 0 ? 'scale(1)' : 'scale(1.05)';
        });

        // Cycle logic
        function cycleImages() {
            images[currentIndex].style.opacity = '0';
            images[currentIndex].style.transform = 'scale(1.05)';

            currentIndex = (currentIndex + 1) % images.length;

            images[currentIndex].style.opacity = '1';
            images[currentIndex].style.transform = 'scale(1)';
        }

        // Autoplay
        const interval = window.innerWidth <= 768 ? 4000 : 3000;
        setInterval(cycleImages, interval);
    });
});
