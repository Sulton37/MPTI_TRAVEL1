class GalleryManager {
    constructor() {
        this.currentPackageId = null;
        this.isModalOpen = false;
        this.init();
    }

    init() {
        console.log('🎮 GalleryManager initialized');
        this.bindEvents();
    }

    bindEvents() {
        // Gallery upload form submission
        document.addEventListener('submit', (e) => {
            if (e.target && e.target.id === 'galleryUploadForm') {
                this.handleGalleryUpload(e);
            }
        });

        // Gallery file selection
        document.addEventListener('change', (e) => {
            if (e.target && e.target.id === 'galleryFiles') {
                this.handleFileSelection(e);
            }
        });

        // Modal close events
        document.addEventListener('click', (e) => {
            if (e.target && e.target.classList.contains('close')) {
                this.closeGalleryModal();
            }
            // Close when clicking outside modal
            if (e.target && e.target.id === 'galleryManageModal') {
                this.closeGalleryModal();
            }
        });

        // Escape key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isModalOpen) {
                this.closeGalleryModal();
            }
        });
    }

    openGalleryManage(packageId, packageName) {
        console.log('🖼️ Opening gallery management for package:', packageId, packageName);
        
        if (this.isModalOpen) {
            console.log('⚠️ Modal already open, closing first');
            this.closeGalleryModal();
            // Wait a bit before opening again
            setTimeout(() => {
                this.openGalleryManage(packageId, packageName);
            }, 300);
            return;
        }
        
        this.currentPackageId = packageId;
        this.isModalOpen = true;
        
        // Update modal content
        const packageIdInput = document.getElementById('galleryPackageId');
        const packageNameSpan = document.getElementById('galleryPackageName');
        const modal = document.getElementById('galleryManageModal');
        
        console.log('🔍 Modal elements:', {
            packageIdInput: !!packageIdInput,
            packageNameSpan: !!packageNameSpan,
            modal: !!modal
        });
        
        if (packageIdInput) {
            packageIdInput.value = packageId;
            console.log('✅ Package ID set:', packageIdInput.value);
        }
        
        if (packageNameSpan) {
            packageNameSpan.textContent = packageName;
            console.log('✅ Package name set:', packageNameSpan.textContent);
        }
        
        if (modal) {
            // Show modal with animation
            modal.style.display = 'flex';
            requestAnimationFrame(() => {
                modal.classList.add('show');
            });
            console.log('✅ Modal displayed');
        }
        
        // Load existing photos
        this.loadExistingPhotos(packageId);
        
        // Reset upload form
        this.resetUploadForm();
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        console.log('🎉 Gallery modal opened successfully');
    }

    closeGalleryModal() {
        console.log('❌ Closing gallery modal');
        
        const modal = document.getElementById('galleryManageModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                this.isModalOpen = false;
                console.log('✅ Modal closed');
            }, 300);
            document.body.style.overflow = '';
        }
        
        this.currentPackageId = null;
        this.resetUploadForm();
    }

    resetUploadForm() {
        const form = document.getElementById('galleryUploadForm');
        if (form) {
            form.reset();
            const captionsContainer = document.getElementById('galleryCaptions');
            if (captionsContainer) captionsContainer.innerHTML = '';
        }
    }

    async loadExistingPhotos(packageId) {
        console.log('📸 Loading photos for package:', packageId);
        
        const container = document.getElementById('existingPhotos');
        if (!container) {
            console.error('❌ Container existingPhotos not found!');
            return;
        }
        
        container.innerHTML = '<div class="loading" style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Memuat foto...</div>';
        
        try {
            const response = await fetch(`get_gallery_photos.php?package_id=${packageId}&_t=${Date.now()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });

            console.log('📡 Response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            console.log('📄 Raw response:', text);

            let photos;
            try {
                photos = JSON.parse(text);
                console.log('🎯 Parsed data:', photos, 'Type:', typeof photos, 'Is array:', Array.isArray(photos));
            } catch (e) {
                console.error('❌ JSON parse error:', e);
                throw new Error('Invalid JSON response');
            }

            if (photos.error) {
                throw new Error(photos.error);
            }

            // Process photos
            const processedPhotos = this.processPhotos(photos, packageId);
            this.displayExistingPhotos(processedPhotos, packageId);

        } catch (error) {
            console.error('❌ Error loading photos:', error);
            container.innerHTML = `
                <div class="error-state" style="text-align: center; padding: 20px; background: #fee; border-radius: 8px; color: #c33;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading photos: ${error.message}</p>
                    <button onclick="galleryManager.loadExistingPhotos(${packageId})" style="padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px;">
                        <i class="fas fa-sync-alt"></i> Retry
                    </button>
                </div>
            `;
        }
    }

    processPhotos(photos, packageId) {
        console.log('🔄 Processing photos:', photos);
        
        if (!Array.isArray(photos)) {
            console.warn('⚠️ Photos is not an array:', photos);
            return [];
        }

        const galleryPhotos = photos.map((photo, index) => {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const baseUrl = `${protocol}//${host}/MPTI_TRAVEL/BackEnd/uploads/gallery/`;
            const photoUrl = baseUrl + photo.photo_filename;
            
            console.log(`📸 Photo ${index + 1}: ${photo.photo_filename} -> ${photoUrl}`);
            
            return {
                id: photo.id,
                package_id: photo.package_id,
                photo_filename: photo.photo_filename,
                caption: photo.caption || '',
                photo_order: photo.photo_order || (index + 1),
                uploaded_at: photo.uploaded_at,
                url: photoUrl,
                type: 'gallery'
            };
        });

        console.log('✅ Processed gallery photos:', galleryPhotos);
        return galleryPhotos;
    }

    displayExistingPhotos(photos, packageId) {
        console.log('🎨 DISPLAY FUNCTION CALLED with photos:', photos);
        
        const container = document.getElementById('existingPhotos');
        if (!container) {
            console.error('❌ existingPhotos container not found in DOM!');
            return;
        }
        
        if (photos.length === 0) {
            container.innerHTML = `
                <div class="no-photos" style="text-align: center; padding: 30px; color: #666; background: #f8f9fa; border-radius: 12px; border: 2px dashed #dee2e6;">
                    <i class="fas fa-images" style="font-size: 2rem; color: #3498db; margin-bottom: 10px;"></i>
                    <p>Belum ada foto gallery untuk paket ini.</p>
                    <small>Upload foto melalui form di atas untuk menambahkan gallery.</small>
                </div>
            `;
            return;
        }
        
        console.log(`📸 Creating HTML for ${photos.length} photos...`);
        
        const photosHTML = photos.map((photo, index) => {
            console.log(`🖼️ Processing photo ${index + 1}:`, photo);
            
            return `
                <div class="photo-management-item" data-photo-id="${photo.id}">
                    <div class="photo-preview">
                        <img src="${photo.url}" 
                             alt="${this.escapeHtml(photo.caption)}" 
                             loading="lazy" 
                             style="cursor: pointer;"
                             onclick="galleryManager.previewPhoto('${photo.url}', '${this.escapeHtml(photo.caption)}')"
                             onerror="console.log('❌ Image failed to load:', this.src); this.src='../../Asset/Package_Culture/borobudur.jpg'; this.style.opacity='0.7';">
                        <div class="photo-overlay">
                            <button class="btn-edit-photo" 
                                    onclick="galleryManager.editPhotoCaption(${photo.id}, '${this.escapeHtml(photo.caption)}', this)" 
                                    title="Edit Caption">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-delete-photo" 
                                    onclick="galleryManager.deletePhoto(${photo.id}, ${packageId}, this)" 
                                    title="Hapus Foto">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="photo-info">
                        <div class="photo-caption" id="caption-${photo.id}" title="${this.escapeHtml(photo.caption)}">
                            ${this.truncateText(photo.caption || 'Tanpa caption', 20)}
                        </div>
                        <div class="photo-meta">
                            <small>
                                🖼️ Gallery | Order: ${photo.photo_order} <br>
                                ${this.formatDate(photo.uploaded_at)}
                            </small>
                        </div>
                        <div class="photo-actions">
                            <button onclick="galleryManager.movePhoto(${photo.id}, ${packageId}, 'up')" 
                                    class="btn-move-up" title="Pindah ke atas">
                                <i class="fas fa-arrow-up"></i> Up
                            </button>
                            <button onclick="galleryManager.movePhoto(${photo.id}, ${packageId}, 'down')" 
                                    class="btn-move-down" title="Pindah ke bawah">
                                <i class="fas fa-arrow-down"></i> Down
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = photosHTML;
        console.log('✅ Photos displayed successfully!');
        
        // Verify in DOM
        const photoItems = container.querySelectorAll('.photo-management-item');
        console.log('🔍 Photo items found in DOM:', photoItems.length);
    }

    // Helper methods
    truncateText(text, maxLength) {
        if (!text) return 'Tanpa caption';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML.replace(/'/g, '&#39;');
    }

    formatDate(dateString) {
        if (!dateString) return 'Tidak diketahui';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return 'Format tanggal tidak valid';
        }
    }

    previewPhoto(imageUrl, caption) {
        const lightbox = document.createElement('div');
        lightbox.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 20px;
            cursor: pointer;
        `;
        
        const img = document.createElement('img');
        img.src = imageUrl;
        img.style.cssText = `
            max-width: 90%;
            max-height: 80%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        `;
        
        const captionElement = document.createElement('div');
        captionElement.textContent = caption || 'Tanpa caption';
        captionElement.style.cssText = `
            color: white;
            text-align: center;
            margin-top: 15px;
            font-size: 1.1rem;
            max-width: 600px;
        `;
        
        const closeHint = document.createElement('div');
        closeHint.textContent = 'Klik untuk menutup';
        closeHint.style.cssText = `
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            margin-top: 10px;
            font-size: 0.9rem;
        `;
        
        lightbox.appendChild(img);
        lightbox.appendChild(captionElement);
        lightbox.appendChild(closeHint);
        
        lightbox.onclick = () => {
            lightbox.remove();
            document.body.style.overflow = '';
        };
        
        document.body.appendChild(lightbox);
        document.body.style.overflow = 'hidden';
    }

    handleFileSelection(event) {
        const files = event.target.files;
        const captionsContainer = document.getElementById('galleryCaptions');
        if (!captionsContainer) return;
        
        captionsContainer.innerHTML = '';
        
        if (files.length > 0) {
            const captionsHTML = Array.from(files).map((file, index) => `
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="caption_${index}" style="font-size: 0.9rem; margin-bottom: 5px;">Caption untuk "${file.name}":</label>
                    <input type="text" id="caption_${index}" name="captions[]" 
                           placeholder="Masukkan caption foto..." 
                           style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%;">
                </div>
            `).join('');
            
            captionsContainer.innerHTML = captionsHTML;
        }
    }

    async handleGalleryUpload(event) {
        event.preventDefault();
        
        const packageId = document.getElementById('galleryPackageId').value;
        const files = document.getElementById('galleryFiles').files;
        
        if (!packageId || files.length === 0) {
            alert('Package ID dan foto diperlukan');
            return;
        }
        
        const formData = new FormData(event.target);
        const submitBtn = event.target.querySelector('button[type="submit"]');
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        
        try {
            const response = await fetch('upload_additional_photos.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Foto berhasil diupload!', 'success');
                this.loadExistingPhotos(packageId);
                event.target.reset();
                document.getElementById('galleryCaptions').innerHTML = '';
            } else {
                throw new Error(result.error || 'Upload gagal');
            }
            
        } catch (error) {
            console.error('❌ Upload error:', error);
            this.showNotification('Error: ' + error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Foto';
        }
    }

    editPhotoCaption(photoId, currentCaption, buttonElement) {
        const captionElement = document.getElementById(`caption-${photoId}`);
        if (!captionElement) return;
        
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentCaption;
        input.style.cssText = `
            width: 100%;
            padding: 4px;
            border: 1px solid #3498db;
            border-radius: 4px;
            font-size: 0.8rem;
        `;
        
        const saveBtn = document.createElement('button');
        saveBtn.innerHTML = '<i class="fas fa-check"></i>';
        saveBtn.style.cssText = `
            margin-left: 3px;
            padding: 3px 6px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.7rem;
        `;
        
        const cancelBtn = document.createElement('button');
        cancelBtn.innerHTML = '<i class="fas fa-times"></i>';
        cancelBtn.style.cssText = `
            margin-left: 3px;
            padding: 3px 6px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.7rem;
        `;
        
        const originalContent = captionElement.innerHTML;
        
        captionElement.innerHTML = '';
        captionElement.appendChild(input);
        captionElement.appendChild(saveBtn);
        captionElement.appendChild(cancelBtn);
        
        input.focus();
        input.select();
        
        saveBtn.onclick = () => this.savePhotoCaption(photoId, input.value, captionElement, originalContent);
        cancelBtn.onclick = () => {
            captionElement.innerHTML = originalContent;
        };
        
        input.onkeypress = (e) => {
            if (e.key === 'Enter') {
                this.savePhotoCaption(photoId, input.value, captionElement, originalContent);
            } else if (e.key === 'Escape') {
                captionElement.innerHTML = originalContent;
            }
        };
    }

    async savePhotoCaption(photoId, newCaption, captionElement, originalContent) {
        try {
            const response = await fetch('update_photo_caption.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    photo_id: photoId,
                    caption: newCaption
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                captionElement.innerHTML = this.truncateText(newCaption || 'Tanpa caption', 20);
                captionElement.title = newCaption;
                this.showNotification('Caption berhasil diupdate!', 'success');
            } else {
                throw new Error(result.message || 'Gagal update caption');
            }
            
        } catch (error) {
            console.error('❌ Error updating caption:', error);
            captionElement.innerHTML = originalContent;
            this.showNotification('Error: ' + error.message, 'error');
        }
    }

    async deletePhoto(photoId, packageId, buttonElement) {
        if (!confirm('Apakah Anda yakin ingin menghapus foto ini?')) {
            return;
        }
        
        try {
            const response = await fetch('delete_gallery_photo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `photo_id=${photoId}&package_id=${packageId}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Foto berhasil dihapus!', 'success');
                this.loadExistingPhotos(packageId);
            } else {
                throw new Error(result.message || 'Gagal menghapus foto');
            }
            
        } catch (error) {
            console.error('❌ Error deleting photo:', error);
            this.showNotification('Error: ' + error.message, 'error');
        }
    }

    async movePhoto(photoId, packageId, direction) {
        try {
            const response = await fetch('move_photo_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `photo_id=${photoId}&package_id=${packageId}&direction=${direction}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(`Foto berhasil dipindah ${direction === 'up' ? 'ke atas' : 'ke bawah'}!`, 'success');
                this.loadExistingPhotos(packageId);
            } else {
                throw new Error(result.message || 'Gagal memindah foto');
            }
            
        } catch (error) {
            console.error('❌ Error moving photo:', error);
            this.showNotification('Error: ' + error.message, 'error');
        }
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            max-width: 300px;
            word-wrap: break-word;
            font-size: 0.9rem;
        `;
        
        if (type === 'success') {
            notification.style.background = '#27ae60';
        } else {
            notification.style.background = '#e74c3c';
        }
        
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // Debug function
    debugGalleryState() {
        console.log('🔍 === GALLERY DEBUG ===');
        
        const modal = document.getElementById('galleryManageModal');
        console.log('📱 Modal:', modal ? 'Found' : 'NOT FOUND');
        console.log('📱 Modal display:', modal ? modal.style.display : 'N/A');
        console.log('📱 Modal classes:', modal ? modal.className : 'N/A');
        
        const container = document.getElementById('existingPhotos');
        console.log('📦 Container:', container ? 'Found' : 'NOT FOUND');
        console.log('📦 Container innerHTML length:', container ? container.innerHTML.length : 'N/A');
        
        console.log('🎮 Gallery Manager:', this);
        console.log('🆔 Current Package ID:', this.currentPackageId);
        console.log('🚪 Modal Open:', this.isModalOpen);
    }
}

// Initialize gallery manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing GalleryManager...');
    window.galleryManager = new GalleryManager();
});

// Global functions for backward compatibility
function openGalleryManage(packageId, packageName) {
    console.log('🌍 Global openGalleryManage called:', packageId, packageName);
    
    if (window.galleryManager) {
        window.galleryManager.openGalleryManage(packageId, packageName);
    } else {
        console.error('❌ Gallery manager not initialized');
        
        // Try to initialize if not ready
        setTimeout(() => {
            if (window.galleryManager) {
                window.galleryManager.openGalleryManage(packageId, packageName);
            } else {
                alert('Gallery system belum siap. Silakan refresh halaman.');
            }
        }, 500);
    }
}

function closeGalleryModal() {
    if (window.galleryManager) {
        window.galleryManager.closeGalleryModal();
    }
}

// Debug function
window.debugGallery = function() {
    if (window.galleryManager) {
        window.galleryManager.debugGalleryState();
    } else {
        console.log('❌ Gallery manager not found');
    }
};