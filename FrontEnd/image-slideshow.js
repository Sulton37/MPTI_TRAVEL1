document.addEventListener('DOMContentLoaded', function() {
    console.log('🖼️ Image slideshow initialized');
    
    // Initialize with delay to ensure DOM is ready
    setTimeout(() => {
        initializeSlideshows();
    }, 300);
    
    // Watch for dynamically added slideshows
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.querySelector) {
                        const slideshows = node.querySelectorAll('.image-slideshow');
                        if (slideshows.length > 0 || node.classList.contains('image-slideshow')) {
                            setTimeout(() => {
                                initializeSlideshows(node);
                            }, 100);
                        }
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

function initializeSlideshows(container = document) {
    const slideshows = container.querySelectorAll('.image-slideshow:not([data-initialized])');
    
    console.log(`🎠 Found ${slideshows.length} slideshows to initialize`);
    
    slideshows.forEach((slideshow, slideshowIndex) => {
        try {
            slideshow.setAttribute('data-initialized', 'true');
            
            const images = slideshow.querySelectorAll('img');
            const dots = slideshow.querySelectorAll('.dot');
            const counter = slideshow.querySelector('.photo-counter');
            
            console.log(`🖼️ Slideshow ${slideshowIndex + 1}: ${images.length} images, ${dots.length} dots`);
            
            if (images.length <= 1) {
                console.log('👁️ Single image slideshow, skipping auto-slide');
                // Still show the single image
                if (images.length === 1) {
                    images[0].classList.add('active');
                }
                return;
            }
            
            let currentSlide = 0;
            let slideInterval;
            let isHovered = false;
            let isInitialized = false;
            
            function showImage(index) {
                // Enhanced validation
                if (!images || images.length === 0) {
                    console.warn('⚠️ No images found in slideshow');
                    return;
                }
                
                if (index < 0 || index >= images.length) {
                    console.warn('⚠️ Invalid image index:', index, 'Length:', images.length);
                    return;
                }
                
                // Remove active class from all images (hide them)
                images.forEach((img, idx) => {
                    if (img && img.classList) {
                        img.classList.remove('active');
                    }
                });
                
                // Remove active class from all dots
                dots.forEach((dot, idx) => {
                    if (dot && dot.classList) {
                        dot.classList.remove('active');
                    }
                });
                
                // Update current slide
                currentSlide = index;
                
                // Show new image by adding active class
                const newImage = images[currentSlide];
                if (newImage) {
                    newImage.classList.add('active');
                    console.log(`📸 Showing image ${currentSlide + 1}/${images.length} in slideshow ${slideshowIndex + 1}`);
                }
                
                // Add active class to new dot
                const newDot = dots[currentSlide];
                if (newDot && newDot.classList) {
                    newDot.classList.add('active');
                }
                
                // Update counter
                if (counter) {
                    const currentSpan = counter.querySelector('.current');
                    if (currentSpan) {
                        currentSpan.textContent = currentSlide + 1;
                    }
                }
            }
            
            function nextSlide() {
                const nextIndex = (currentSlide + 1) % images.length;
                showImage(nextIndex);
            }
            
            function startSlideshow() {
                if (slideInterval) clearInterval(slideInterval);
                
                // Only start auto-slideshow if more than 1 image
                if (images.length > 1) {
                    slideInterval = setInterval(() => {
                        if (!isHovered) {
                            nextSlide();
                        }
                    }, 4000); // 4 seconds per slide
                    console.log(`🎬 Auto-slideshow started for slideshow ${slideshowIndex + 1}`);
                }
            }
            
            // Dot click handlers
            dots.forEach((dot, index) => {
                if (dot && dot.addEventListener) {
                    dot.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        console.log(`🔘 Dot ${index + 1} clicked in slideshow ${slideshowIndex + 1}`);
                        showImage(index);
                    });
                }
            });
            
            // Hover pause functionality
            if (slideshow && slideshow.addEventListener) {
                slideshow.addEventListener('mouseenter', () => {
                    isHovered = true;
                    console.log(`⏸️ Slideshow ${slideshowIndex + 1} paused (hover)`);
                });
                
                slideshow.addEventListener('mouseleave', () => {
                    isHovered = false;
                    console.log(`▶️ Slideshow ${slideshowIndex + 1} resumed`);
                });
            }
            
            // Wait for images to load before initializing
            let loadedImages = 0;
            const totalImages = images.length;
            
            function checkImagesLoaded() {
                loadedImages++;
                if (loadedImages >= totalImages || loadedImages >= 1) { // Start when first image loads
                    if (!isInitialized) {
                        isInitialized = true;
                        console.log(`📸 Images loaded for slideshow ${slideshowIndex + 1}, initializing...`);
                        showImage(0); // Show first image
                        startSlideshow(); // Start auto-slideshow
                    }
                }
            }
            
            // Add load event listeners to all images
            images.forEach((img, idx) => {
                if (img.complete && img.naturalHeight > 0) {
                    // Image already loaded
                    checkImagesLoaded();
                } else {
                    img.addEventListener('load', checkImagesLoaded);
                    img.addEventListener('error', () => {
                        console.log(`❌ Image ${idx + 1} failed to load in slideshow ${slideshowIndex + 1}`);
                        checkImagesLoaded(); // Still count as "processed"
                    });
                }
            });
            
            // Fallback: initialize after timeout even if images haven't loaded
            setTimeout(() => {
                if (!isInitialized) {
                    console.log(`⏰ Timeout reached, force-initializing slideshow ${slideshowIndex + 1}`);
                    isInitialized = true;
                    showImage(0);
                    startSlideshow();
                }
            }, 2000);
            
            console.log(`✅ Slideshow ${slideshowIndex + 1} setup completed`);
            
        } catch (error) {
            console.error(`❌ Error initializing slideshow ${slideshowIndex + 1}:`, error);
        }
    });
}

// Global function for manual initialization
window.initializeImageSlideshows = initializeSlideshows;

// Global function for debugging
window.debugSlideshows = function() {
    const slideshows = document.querySelectorAll('.image-slideshow');
    console.log(`🔍 Found ${slideshows.length} slideshows on page`);
    
    slideshows.forEach((slideshow, index) => {
        const images = slideshow.querySelectorAll('img');
        const dots = slideshow.querySelectorAll('.dot');
        const activeImages = slideshow.querySelectorAll('img.active');
        const activeDots = slideshow.querySelectorAll('.dot.active');
        
        console.log(`📊 Slideshow ${index + 1}:`, {
            initialized: slideshow.hasAttribute('data-initialized'),
            totalImages: images.length,
            totalDots: dots.length,
            activeImages: activeImages.length,
            activeDots: activeDots.length,
            firstImageSrc: images[0]?.src,
            firstImageLoaded: images[0]?.complete && images[0]?.naturalHeight > 0
        });
        
        // Check each image status
        images.forEach((img, imgIndex) => {
            console.log(`  📸 Image ${imgIndex + 1}:`, {
                src: img.src,
                loaded: img.complete && img.naturalHeight > 0,
                hasActiveClass: img.classList.contains('active'),
                naturalSize: `${img.naturalWidth}x${img.naturalHeight}`
            });
        });
    });
};
