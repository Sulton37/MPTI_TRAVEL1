class GalleryManager {
    constructor() {
        this.currentPackageId = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.createModal();
    }

    createModal() {
        // Create modal if it doesn't exist
        if (!document.getElementById('galleryManageModal')) {
            const modalHTML = `
                <div id="galleryManageModal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>🖼️ Kelola Gallery - <span id="galleryPackageName"></span></h3>
                            <button class="close" onclick="closeGalleryModal()">&times;</button>
                        </div>
                        
                        <div class="modal-body">
                            <!-- Upload Section -->
                            <div class="gallery-section">
                                <h4><i class="fas fa-cloud-upload-alt"></i> Upload Foto Baru</h4>
                                <form id="galleryUploadForm" enctype="multipart/form-data">
                                    <input type="hidden" id="galleryPackageId" name="package_id">
                                    
                                    <div class="form-group">
                                        <label for="galleryFiles">Pilih Foto (Max 10)</label>
                                        <input type="file" id="galleryFiles" name="photos[]" multiple accept="image/*" required>
                                        <small class="form-help">Format: JPG, PNG, WebP | Max 5MB per file</small>
                                    </div>
                                    
                                    <div id="galleryCaptions"></div>
                                    
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-upload"></i> Upload Foto
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Existing Photos Section -->
                            <div class="gallery-section">
                                <h4><i class="fas fa-images"></i> Foto Yang Ada</h4>
                                <div id="existingPhotos" class="existing-photos-grid">
                                    <div class="loading">
                                        <i class="fas fa-spinner fa-spin"></i> Memuat foto...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
    }

    bindEvents() {
        // Gallery upload form submission
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'galleryUploadForm') {
                e.preventDefault();
                this.handleGalleryUpload(e);
            }
        });

        // Gallery file selection
        document.addEventListener('change', (e) => {
            if (e.target.id === 'galleryFiles') {
                this.handleFileSelection(e);
            }
        });

        // Modal close events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal') || e.target.classList.contains('close')) {
                this.closeGalleryModal();
            }
        });

        // Escape key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeGalleryModal();
            }
        });
    }

    openGalleryManage(packageId, packageName) {
        console.log('🖼️ Opening gallery management for package:', packageId, packageName);
        
        this.currentPackageId = packageId;
        
        // Update modal content
        const packageIdInput = document.getElementById('galleryPackageId');
        const packageNameSpan = document.getElementById('galleryPackageName');
        const modal = document.getElementById('galleryManageModal');
        
        if (packageIdInput) packageIdInput.value = packageId;
        if (packageNameSpan) packageNameSpan.textContent = packageName;
        if (modal) modal.style.display = 'flex';
        
        // Load existing photos
        this.loadExistingPhotos(packageId);
        
        // Reset upload form
        const form = document.getElementById('galleryUploadForm');
        if (form) {
            form.reset();
            document.getElementById('galleryCaptions').innerHTML = '';
        }
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    closeGalleryModal() {
        const modal = document.getElementById('galleryManageModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            this.currentPackageId = null;
        }
    }

    async loadExistingPhotos(packageId) {
        const container = document.getElementById('existingPhotos');
        if (!container) return;
        
        container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Memuat foto...</div>';
        
        try {
            const response = await fetch(`get_gallery_photos.php?package_id=${packageId}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();
            
            if (result.success) {
                this.displayExistingPhotos(result.photos, packageId);
            } else {
                throw new Error(result.message || 'Failed to load photos');
            }
        } catch (error) {
            console.error('❌ Error loading photos:', error);
            container.innerHTML = `
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error memuat foto: ${error.message}
                    <br><button onclick="galleryManager.loadExistingPhotos(${packageId})" class="btn-primary" style="margin-top: 10px;">
                        <i class="fas fa-sync-alt"></i> Coba Lagi
                    </button>
                </div>
            `;
        }
    }

    displayExistingPhotos(photos, packageId) {
        const container = document.getElementById('existingPhotos');
        if (!container) return;
        
        if (photos.length === 0) {
            container.innerHTML = `
                <div class="no-photos">
                    <i class="fas fa-images"></i>
                    <p>Belum ada foto gallery untuk paket ini.</p>
                    <small>Upload foto pertama menggunakan form di atas.</small>
                </div>
            `;
            return;
        }
        
        const photosHTML = photos.map(photo => `
            <div class="photo-management-item" data-photo-id="${photo.id}">
                <div class="photo-preview">
                    <img src="${photo.url}" alt="${photo.caption}" loading="lazy" 
                         onerror="this.src='../../Asset/Package_Culture/borobudur.jpg'">
                    <div class="photo-overlay">
                        <button class="btn-edit-photo" onclick="galleryManager.editPhotoCaption(${photo.id}, '${this.escapeHtml(photo.caption)}', this)" title="Edit Caption">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${photo.type === 'gallery' ? `
                            <button class="btn-delete-photo" onclick="galleryManager.deletePhoto(${photo.id}, ${packageId}, this)" title="Hapus Foto">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : `
                            <span class="main-photo-badge">Foto Utama</span>
                        `}
                    </div>
                </div>
                <div class="photo-info">
                    <div class="photo-caption" id="caption-${photo.id}">${photo.caption || 'Tanpa caption'}</div>
                    <div class="photo-meta">
                        <small>
                            ${photo.type === 'main' ? '📸 Foto Utama' : '🖼️ Gallery'} | 
                            Order: ${photo.photo_order} | 
                            ${this.formatDate(photo.uploaded_at)}
                        </small>
                    </div>
                    ${photo.type === 'gallery' ? `
                        <div class="photo-actions">
                            <button onclick="galleryManager.movePhoto(${photo.id}, ${packageId}, 'up')" 
                                    class="btn-move-up" title="Pindah ke atas">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button onclick="galleryManager.movePhoto(${photo.id}, ${packageId}, 'down')" 
                                    class="btn-move-down" title="Pindah ke bawah">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
        
        container.innerHTML = photosHTML;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML.replace(/'/g, '&#39;');
    }

    handleFileSelection(event) {
        const files = event.target.files;
        const captionsContainer = document.getElementById('galleryCaptions');
        if (!captionsContainer) return;
        
        captionsContainer.innerHTML = '';
        
        if (files.length > 0) {
            if (files.length > 10) {
                alert('⚠️ Maksimal 10 foto yang dapat diupload sekaligus');
                event.target.value = '';
                return;
            }
            
            captionsContainer.innerHTML = '<h5><i class="fas fa-pen"></i> Caption untuk setiap foto:</h5>';
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const captionDiv = document.createElement('div');
                captionDiv.className = 'form-group';
                captionDiv.innerHTML = `
                    <label for="caption-${i}">Caption untuk "${file.name}"</label>
                    <input type="text" id="caption-${i}" name="captions[]" 
                           placeholder="Deskripsi foto ini..." 
                           maxlength="255">
                `;
                captionsContainer.appendChild(captionDiv);
            }
        }
    }

    async handleGalleryUpload(event) {
        event.preventDefault();
        
        const packageId = document.getElementById('galleryPackageId').value;
        const files = document.getElementById('galleryFiles').files;
        
        if (!packageId || files.length === 0) {
            this.showNotification('❌ Harap pilih foto untuk diupload', 'error');
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
                this.showNotification('✅ ' + result.message, 'success');
                
                // Reset form
                event.target.reset();
                document.getElementById('galleryCaptions').innerHTML = '';
                
                // Reload photos
                this.loadExistingPhotos(packageId);
            } else {
                throw new Error(result.message || 'Upload gagal');
            }
        } catch (error) {
            console.error('❌ Upload error:', error);
            this.showNotification('❌ ' + error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload Foto';
        }
    }

    editPhotoCaption(photoId, currentCaption, buttonElement) {
        const captionElement = document.getElementById(`caption-${photoId}`);
        if (!captionElement) return;
        
        const currentText = currentCaption || '';
        
        const wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '5px';
        
        const input = document.createElement('input');
        input.type = 'text';
        input.value = currentText;
        input.style.flex = '1';
        input.style.padding = '5px';
        input.style.border = '1px solid #ddd';
        input.style.borderRadius = '4px';
        
        const saveBtn = document.createElement('button');
        saveBtn.innerHTML = '<i class="fas fa-save"></i>';
        saveBtn.className = 'btn-primary';
        saveBtn.style.padding = '5px 8px';
        saveBtn.style.minWidth = 'auto';
        
        const cancelBtn = document.createElement('button');
        cancelBtn.innerHTML = '<i class="fas fa-times"></i>';
        cancelBtn.className = 'btn-secondary';
        cancelBtn.style.padding = '5px 8px';
        cancelBtn.style.minWidth = 'auto';
        
        wrapper.appendChild(input);
        wrapper.appendChild(saveBtn);
        wrapper.appendChild(cancelBtn);
        
        captionElement.innerHTML = '';
        captionElement.appendChild(wrapper);
        input.focus();
        
        saveBtn.onclick = () => this.savePhotoCaption(photoId, input.value, captionElement);
        cancelBtn.onclick = () => {
            captionElement.textContent = currentText || 'Tanpa caption';
        };
        
        input.onkeypress = (e) => {
            if (e.key === 'Enter') {
                this.savePhotoCaption(photoId, input.value, captionElement);
            } else if (e.key === 'Escape') {
                captionElement.textContent = currentText || 'Tanpa caption';
            }
        };
    }

    async savePhotoCaption(photoId, newCaption, captionElement) {
        try {
            const formData = new FormData();
            formData.append('photo_id', photoId);
            formData.append('caption', newCaption);
            
            const response = await fetch('update_photo_caption.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                captionElement.textContent = newCaption || 'Tanpa caption';
                this.showNotification('✅ Caption berhasil diupdate', 'success');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('❌ Error updating caption:', error);
            this.showNotification('❌ Gagal update caption: ' + error.message, 'error');
            captionElement.textContent = 'Error updating caption';
        }
    }

    async deletePhoto(photoId, packageId, buttonElement) {
        if (!confirm('❓ Apakah Anda yakin ingin menghapus foto ini?')) {
            return;
        }
        
        const photoItem = buttonElement.closest('.photo-management-item');
        photoItem.style.opacity = '0.5';
        
        try {
            const formData = new FormData();
            formData.append('photo_id', photoId);
            formData.append('package_id', packageId);
            
            const response = await fetch('delete_gallery_photo.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                photoItem.remove();
                this.showNotification('✅ ' + result.message, 'success');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('❌ Error deleting photo:', error);
            photoItem.style.opacity = '1';
            this.showNotification('❌ Gagal hapus foto: ' + error.message, 'error');
        }
    }

    async movePhoto(photoId, packageId, direction) {
        try {
            const formData = new FormData();
            formData.append('photo_id', photoId);
            formData.append('package_id', packageId);
            formData.append('direction', direction);
            
            const response = await fetch('move_photo_order.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification(`✅ Foto berhasil dipindah ke ${direction === 'up' ? 'atas' : 'bawah'}`, 'success');
                this.loadExistingPhotos(packageId); // Reload to show new order
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('❌ Error moving photo:', error);
            this.showNotification('❌ Gagal pindah foto: ' + error.message, 'error');
        }
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    showNotification(message, type) {
        // Remove existing notification
        const existing = document.querySelector('.gallery-notification');
        if (existing) existing.remove();
        
        const notification = document.createElement('div');
        notification.className = `gallery-notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#28a745' : '#dc3545'};
            color: white;
            border-radius: 8px;
            z-index: 10001;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            max-width: 350px;
            word-wrap: break-word;
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        requestAnimationFrame(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        });
        
        // Animate out
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }
}

// Initialize gallery manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.galleryManager = new GalleryManager();
});

// Global functions for backward compatibility
function openGalleryManage(packageId, packageName) {
    if (window.galleryManager) {
        window.galleryManager.openGalleryManage(packageId, packageName);
    } else {
        console.error('❌ Gallery manager not initialized');
    }
}

function closeGalleryModal() {
    if (window.galleryManager) {
        window.galleryManager.closeGalleryModal();
    }
}