document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM loaded, starting to load packages...');
    loadPackages();
});

async function loadPackages() {
    const slider = document.querySelector('.slider');
    
    try {
        console.log('📡 Fetching packages from API...');
        
        const timestamp = new Date().getTime();
        const response = await fetch(`../../BackEnd/get_paket.php?t=${timestamp}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        
        console.log('📊 Response status:', response.status);
        console.log('📋 Response headers:', response.headers.get('content-type'));
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('📄 Raw response length:', text.length);
        
        if (!text.trim()) {
            throw new Error('Empty response from server');
        }
        
        const cleanText = text.trim().replace(/^\uFEFF/, '');
        
        // Validate JSON structure
        if (!cleanText.startsWith('[') && !cleanText.startsWith('{')) {
            console.error('Response does not start with valid JSON:', cleanText.substring(0, 100));
            throw new Error('Invalid JSON format - response does not start with [ or {');
        }
        
        // Find the end of JSON
        let jsonEndIndex = -1;
        if (cleanText.startsWith('[')) {
            jsonEndIndex = cleanText.lastIndexOf(']');
        } else if (cleanText.startsWith('{')) {
            jsonEndIndex = cleanText.lastIndexOf('}');
        }
        
        if (jsonEndIndex === -1) {
            throw new Error('Invalid JSON format - no closing bracket found');
        }
        
        const jsonString = cleanText.substring(0, jsonEndIndex + 1);
        console.log('🧹 Cleaned JSON length:', jsonString.length);
        
        let packages;
        try {
            packages = JSON.parse(jsonString);
        } catch (e) {
            console.error('❌ JSON parse error:', e);
            console.error('🔍 JSON string preview:', jsonString.substring(0, 200));
            console.error('🔍 JSON string end:', jsonString.substring(jsonString.length - 200));
            throw new Error('Failed to parse JSON response: ' + e.message);
        }
        
        console.log('✅ Packages loaded successfully:', packages.length, 'items');
        
        if (packages.error) {
            throw new Error(packages.error);
        }
        
        if (!Array.isArray(packages)) {
            console.error('Packages is not an array:', typeof packages);
            throw new Error('Invalid data format - expected array');
        }
        
        displayPackages(packages);
        
    } catch (error) {
        console.error('❌ Error fetching packages:', error);
        showError(slider, error.message);
    }
}

function displayPackages(packages) {
    const slider = document.querySelector('.slider');
    
    if (!slider) {
        console.error('❌ Slider element not found');
        return;
    }
    
    if (packages.length === 0) {
        showEmptyState(slider);
        return;
    }
    
    slider.innerHTML = '';
    
    packages.forEach((package, index) => {
        try {
            console.log(`🏗️ Creating card for package ${index + 1}:`, {
                id: package.id,
                nama: package.nama,
                foto_count: package.foto_count,
                fotos_exist: package.fotos_exist
            });
            
            const tourCard = createTourCard(package, index);
            slider.appendChild(tourCard);
        } catch (error) {
            console.error('❌ Error creating tour card for package:', package, error);
        }
    });
    
    console.log(`✅ ${packages.length} packages displayed successfully`);
    
    // Initialize slideshows after DOM is ready
    setTimeout(() => {
        console.log('🎬 Initializing slideshows...');
        if (window.initializeImageSlideshows) {
            window.initializeImageSlideshows();
        }
    }, 500);
}

function createTourCard(package, index) {
    const card = document.createElement('div');
    card.className = 'tour-card';
    card.setAttribute('data-package-id', package.id);
    
    const nama = (package.nama || 'Paket Wisata').toString();
    const deskripsi = (package.deskripsi || 'Deskripsi tidak tersedia').toString();
    
    const description = deskripsi.length > 120 
        ? deskripsi.substring(0, 120) + '...' 
        : deskripsi;
    
    const imageId = `package-slideshow-${package.id}`;
    
    let slideshowHTML = '';
    if (package.fotos && package.fotos.length > 0) {
        const hasRealPhotos = package.fotos_exist && package.fotos_exist.some(exists => exists === true);
        
        slideshowHTML = `
            <div class="image-slideshow loading" id="${imageId}">
                ${package.fotos.map((foto, idx) => {
                    const isRealPhoto = package.fotos_exist && package.fotos_exist[idx] === true;
                    
                    return `
                        <img src="${foto}" 
                             alt="${nama} - Foto ${idx + 1}" 
                             data-index="${idx}"
                             data-is-real="${isRealPhoto}"
                             data-original-src="${foto}"
                             class="${idx === 0 ? 'active' : ''}"
                             loading="eager"
                             onload="this.setAttribute('data-loaded', 'true'); this.closest('.image-slideshow').classList.remove('loading');"
                             onerror="handleImageError(this, ${idx});">
                    `;
                }).join('')}
                
                ${package.fotos.length > 1 ? `
                    <div class="slideshow-indicators">
                        ${package.fotos.map((_, idx) => `
                            <span class="dot ${idx === 0 ? 'active' : ''}" data-slide="${idx}"></span>
                        `).join('')}
                    </div>
                    <div class="photo-counter">
                        <span class="current">1</span>/<span class="total">${package.fotos.length}</span>
                    </div>
                ` : ''}
                
                <div class="image-badge ${hasRealPhotos ? 'original' : 'fallback'}">
                    ${hasRealPhotos ? '📷 Foto Asli' : '🖼️ Foto Default'}
                </div>
            </div>
        `;
    } else {
        slideshowHTML = `
            <div class="image-slideshow">
                <div class="no-image-placeholder">
                    <i class="fas fa-image"></i>
                    <p>Foto tidak tersedia</p>
                </div>
            </div>
        `;
    }
    
    card.innerHTML = `
        ${slideshowHTML}
        <div class="content">
            <h3>${nama}</h3>
            <p>${description}</p>
            <div class="package-meta">
                <small class="image-info">
                    <i class="fas fa-images"></i> ${package.foto_count} Foto
                    ${package.fotos_exist && package.fotos_exist.some(exists => exists) ? 
                        '(Ada foto asli)' : '(Semua default)'}
                </small>
            </div>
            <a href="package_detail.html?id=${package.id}" class="detail-button">
                <i class="fas fa-info-circle"></i> Lihat Detail
            </a>
        </div>
    `;
    
    return card;
}

// Enhanced image error handling
function handleImageError(img, imageIndex = 0) {
    console.log('❌ Image Error Details:', {
        src: img.src,
        index: imageIndex,
        naturalWidth: img.naturalWidth,
        naturalHeight: img.naturalHeight,
        complete: img.complete,
        originalSrc: img.getAttribute('data-original-src')
    });
    
    // Try to fix common URL issues
    const originalSrc = img.src;
    
    // If it's already an absolute URL and failed, try the relative version
    if (originalSrc.startsWith('http')) {
        const filename = originalSrc.split('/').pop();
        const relativeSrc = '../../BackEnd/uploads/' + filename;
        
        console.log('🔄 Trying relative URL:', relativeSrc);
        
        const testImg = new Image();
        testImg.onload = () => {
            console.log('✅ Relative URL worked:', relativeSrc);
            img.src = relativeSrc;
        };
        
        testImg.onerror = () => {
            console.log('❌ Relative URL also failed, using fallback');
            useFallbackImage(img, imageIndex);
        };
        
        testImg.src = relativeSrc;
        return;
    }
    
    useFallbackImage(img, imageIndex);
}

function useFallbackImage(img, imageIndex) {
    const fallbackImages = [
        '../../Asset/Package_Culture/borobudur.jpg',
        '../../Asset/Package_Culture/prambanan.jpg',
        '../../Asset/Package_Culture/kraton.jpg',
        '../../Asset/Package_Culture/malioboro.jpg',
        '../../Asset/Package_Culture/taman_sari.jpg'
    ];
    
    const fallbackIndex = imageIndex % fallbackImages.length;
    img.src = fallbackImages[fallbackIndex];
    img.setAttribute('data-status', 'fallback');
    
    // Update UI indicators
    const badge = img.closest('.image-slideshow')?.querySelector('.image-badge');
    if (badge) {
        badge.textContent = '⚠️ Foto Cadangan';
        badge.className = 'image-badge fallback';
    }
}

function showEmptyState(container) {
    container.innerHTML = `
        <div class="empty-state" style="text-align: center; padding: 60px 20px; color: #666;">
            <i class="fas fa-suitcase-rolling" style="font-size: 4rem; margin-bottom: 20px; color: #3498db;"></i>
            <h3 style="margin-bottom: 15px; color: #2c3e50;">Belum Ada Paket Tour</h3>
            <p style="margin-bottom: 25px;">Silakan tambah paket baru di admin panel.</p>
            <a href="../../BackEnd/admin.php" style="display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 8px;">
                <i class="fas fa-plus"></i> Tambah Paket
            </a>
        </div>
    `;
}

function showError(container, message) {
    container.innerHTML = `
        <div class="error-state" style="text-align: center; padding: 40px 20px; color: #e74c3c; background: #fee; border-radius: 15px; margin: 20px; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 20px;"></i>
            <h3>Terjadi Kesalahan</h3>
            <p style="margin-bottom: 25px;">${message}</p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <button onclick="loadPackages()" style="padding: 12px 24px; background: #3498db; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fas fa-sync-alt"></i> Coba Lagi
                </button>
                <button onclick="window.debugPackages()" style="padding: 12px 24px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    <i class="fas fa-bug"></i> Debug
                </button>
            </div>
        </div>
    `;
}

// Enhanced debug function
window.debugPackages = function() {
    console.log('🔍 === COMPREHENSIVE DEBUG ===');
    
    // Test backend debug page
    console.log('🧪 Testing backend debug page...');
    window.open('../../BackEnd/debug_upload.php', '_blank');
    
    // Test API with enhanced logging
    const timestamp = new Date().getTime();
    fetch(`../../BackEnd/get_paket.php?debug=1&t=${timestamp}`)
        .then(response => {
            console.log('📊 API Response Status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('📄 Response Length:', text.length);
            
            try {
                const data = JSON.parse(text);
                console.log('✅ JSON Valid - Packages:', data.length);
                
                data.forEach((pkg, index) => {
                    console.log(`📦 Package ${index + 1}:`, {
                        id: pkg.id,
                        nama: pkg.nama,
                        fotos_exist: pkg.fotos_exist,
                        foto_count: pkg.foto_count
                    });
                });
                
            } catch (e) {
                console.error('❌ JSON Parse Error:', e);
                console.log('🔍 Raw Response:', text.substring(0, 1000));
            }
        })
        .catch(error => {
            console.error('❌ Network Error:', error);
        });
};

// Global functions
window.loadPackages = loadPackages;
window.handleImageError = handleImageError;