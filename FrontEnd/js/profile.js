const galleryImages = [
    "../../Asset/Package_Culture/borobudur.jpg",
    "../../Asset/Package_Culture/candimendut.jpg",
    "../../Asset/Package_Culture/keraton.jpg",
    "../../Asset/Package_Culture/prambanan1.jpg",
    "../../Asset/Package_Culture/restoranjogja.jpg",
    "../../Asset/Package_Culture/tamansari.jpg"
];

const mainPhoto = document.getElementById("main-photo");
const galleryBackground = document.querySelector(".gallery-background");
const galleryContainer = document.querySelector(".gallery");

let bgImagesElems = [];
let currentActive = null;

// Duplicate images for seamless looping
const duplicatedImages = [...galleryImages, ...galleryImages, ...galleryImages];

function createSeamlessBackground() {
    galleryBackground.innerHTML = '';
    bgImagesElems = [];
    
    const containerWidth = galleryContainer.offsetWidth;
    const containerHeight = galleryContainer.offsetHeight;
    const imgSize = 120;
    const cols = Math.ceil(containerWidth / imgSize) * 2; // Double columns
    const rows = Math.ceil(containerHeight / imgSize);
    
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const imgIndex = (row * cols + col) % duplicatedImages.length;
            const bgImg = document.createElement("img");
            bgImg.src = duplicatedImages[imgIndex];
            bgImg.alt = `Gallery image`;
            bgImg.classList.add("bg-img");
            
            // Position randomly with some overlap
            const left = col * imgSize * 0.9 + Math.random() * 20;
            const top = row * imgSize * 0.9 + Math.random() * 20;
            
            Object.assign(bgImg.style, {
                width: `${imgSize}px`,
                height: `${imgSize}px`,
                position: 'absolute',
                left: `${left}px`,
                top: `${top}px`,
                filter: "blur(6px)",
                transform: "scale(1)",
                transition: "all 1.5s cubic-bezier(0.4, 0, 0.2, 1)",
                objectFit: "cover",
                opacity: "0.8",
                borderRadius: "4px"
            });
            
            galleryBackground.appendChild(bgImg);
            bgImagesElems.push(bgImg);
        }
    }
}

function activateRandomImage() {
    // Reset previous active
    if (currentActive) {
        Object.assign(currentActive.style, {
            transform: "scale(1)",
            filter: "blur(6px)",
            zIndex: "1",
            opacity: "0.8",
            boxShadow: "none"
        });
    }
    
    // Select random image (not in center)
    const nonCenterImages = bgImagesElems.filter(img => {
        const rect = img.getBoundingClientRect();
        const centerX = galleryContainer.offsetWidth / 2;
        return Math.abs(rect.left + rect.width/2 - centerX) > 150;
    });
    
    const randomImg = nonCenterImages[Math.floor(Math.random() * nonCenterImages.length)];
    currentActive = randomImg;
    
    // Update main photo (centered)
    mainPhoto.src = randomImg.src;
    mainPhoto.style.opacity = "0";
    mainPhoto.style.transform = "scale(0.95)";
    
    setTimeout(() => {
        mainPhoto.style.opacity = "1";
        mainPhoto.style.transform = "scale(1)";
    }, 300);
    
    // Zoom the background image (random position)
    Object.assign(randomImg.style, {
        transform: "scale(2.2)",
        filter: "blur(0)",
        zIndex: "10",
        opacity: "1",
        boxShadow: "0 0 25px rgba(0,0,0,0.4)"
    });
    
    // Return to normal after delay
    setTimeout(() => {
        if (randomImg === currentActive) {
            Object.assign(randomImg.style, {
                transform: "scale(1)",
                filter: "blur(6px)",
                zIndex: "1",
                opacity: "0.8",
                boxShadow: "none"
            });
        }
    }, 5000);
}

// Initialize
const resizeObserver = new ResizeObserver(() => {
    createSeamlessBackground();
    if (bgImagesElems.length > 0) setTimeout(activateRandomImage, 1000);
});

window.addEventListener('load', () => {
    resizeObserver.observe(galleryContainer);
    createSeamlessBackground();
    
    // Start animation
    setTimeout(() => {
        activateRandomImage();
        setInterval(activateRandomImage, 7000);
    }, 1500);
});