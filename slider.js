// slider.js
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.slider');
    if (!slider) return;
    
    let isDown = false;
    let startX;
    let scrollLeft;
    
    // Touch and mouse events for slider
    slider.addEventListener('mousedown', (e) => {
        isDown = true;
        slider.classList.add('active');
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
    });
    
    slider.addEventListener('mouseleave', () => {
        isDown = false;
        slider.classList.remove('active');
    });
    
    slider.addEventListener('mouseup', () => {
        isDown = false;
        slider.classList.remove('active');
    });
    
    slider.addEventListener('mousemove', (e) => {
        if(!isDown) return;
        e.preventDefault();
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 2;
        slider.scrollLeft = scrollLeft - walk;
    });
    
    // Touch events for mobile
    slider.addEventListener('touchstart', (e) => {
        isDown = true;
        slider.classList.add('active');
        startX = e.touches[0].pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
    });
    
    slider.addEventListener('touchend', () => {
        isDown = false;
        slider.classList.remove('active');
    });
    
    slider.addEventListener('touchmove', (e) => {
        if(!isDown) return;
        const x = e.touches[0].pageX - slider.offsetLeft;
        const walk = (x - startX) * 2;
        slider.scrollLeft = scrollLeft - walk;
    });
    
    // Responsive card sizing
    function adjustCardSizes() {
        const cards = document.querySelectorAll('.tour-card');
        if (!cards.length) return;
        
        const screenWidth = window.innerWidth;
        let cardsPerView = 4; // Default for large screens
        
        if (screenWidth <= 768 && screenWidth > 480) {
            cardsPerView = 2;
        } else if (screenWidth <= 480) {
            cardsPerView = 1;
        }
        
        const cardWidth = `calc(${100 / cardsPerView}% - 20px)`;
        
        cards.forEach(card => {
            card.style.flex = `0 0 ${cardWidth}`;
            card.style.maxWidth = cardWidth;
        });
    }
    
    // Initialize and add resize listener
    adjustCardSizes();
    window.addEventListener('resize', adjustCardSizes);
});
