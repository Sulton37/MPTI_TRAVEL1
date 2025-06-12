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
let currentImageIndex = 0;

// Duplicate images for seamless looping
const duplicatedImages = [...galleryImages, ...galleryImages, ...galleryImages];

function createSeamlessBackground() {
    galleryBackground.innerHTML = '';
    bgImagesElems = [];
    
    const containerWidth = galleryContainer.offsetWidth;
    const containerHeight = galleryContainer.offsetHeight;
    const imgSize = 120;
    const cols = Math.ceil(containerWidth / imgSize) * 2;
    const rows = Math.ceil(containerHeight / imgSize);
    
    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const imgIndex = (row * cols + col) % duplicatedImages.length;
            const bgImg = document.createElement("img");
            bgImg.src = duplicatedImages[imgIndex];
            bgImg.alt = `Gallery image`;
            bgImg.classList.add("bg-img");
            
            const left = col * imgSize * 0.9 + Math.random() * 20;
            const top = row * imgSize * 0.9 + Math.random() * 20;
            
            Object.assign(bgImg.style, {
                width: `${imgSize}px`,
                height: `${imgSize}px`,
                position: 'absolute',
                left: `${left}px`,
                top: `${top}px`,
                filter: "blur(6px)",
                objectFit: "cover",
                opacity: "0.8",
                borderRadius: "4px"
            });
            
            galleryBackground.appendChild(bgImg);
            bgImagesElems.push(bgImg);
        }
    }
}

function changeMainImage() {
    // Update main photo dengan transisi smooth
    mainPhoto.style.opacity = "0";
    mainPhoto.style.transform = "scale(0.95)";
    
    setTimeout(() => {
        mainPhoto.src = galleryImages[currentImageIndex];
        mainPhoto.style.opacity = "1";
        mainPhoto.style.transform = "scale(1)";
        
        // Move to next image
        currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
    }, 300);
}

// Initialize
const resizeObserver = new ResizeObserver(() => {
    createSeamlessBackground();
});

window.addEventListener('load', () => {
    resizeObserver.observe(galleryContainer);
    createSeamlessBackground();
    
    // Set initial main image
    if (galleryImages.length > 0) {
        mainPhoto.src = galleryImages[0];
        currentImageIndex = 1;
    }
    
    // Start image rotation for main photo only
    setTimeout(() => {
        setInterval(changeMainImage, 5000); // Change every 5 seconds
    }, 1500);
});

document.getElementById('admin-login-btn').addEventListener('click', function() {
    // Redirect langsung ke halaman login admin backend
    window.location.href = '../../BackEnd/loginadmin.php';
});
