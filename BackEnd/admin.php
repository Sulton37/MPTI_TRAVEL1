<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: loginadmin.php?error=not_logged_in");
    exit;
}

// Database connection
$koneksi = new mysqli("localhost", "root", "", "paket_travel");
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Display messages
$message = '';
if (isset($_GET['success'])) {
    $message = '<div class="success">Paket berhasil ditambahkan!</div>';
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'input_too_long':
            $message = '<div class="error">Input terlalu panjang!</div>';
            break;
        case 'invalid_file_type':
            $message = '<div class="error">Tipe file tidak valid! Gunakan JPG, JPEG, atau PNG.</div>';
            break;
        case 'file_too_large':
            $message = '<div class="error">Ukuran file terlalu besar! Maksimal 5MB.</div>';
            break;
        case 'upload_failed':
            $message = '<div class="error">Upload file gagal!</div>';
            break;
        case 'database_error':
            $message = '<div class="error">Error database!</div>';
            break;
        case 'no_files':
            $message = '<div class="error">File foto harus diupload!</div>';
            break;
        case 'invalid_photo_count':
            $message = '<div class="error">Upload 3-6 foto!</div>';
            break;
        default:
            $message = '<div class="error">Terjadi kesalahan!</div>';
    }
} elseif (isset($_GET['deleted'])) {
    $message = '<div class="success">Paket berhasil dihapus!</div>';
}

// Handle delete functionality
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    
    // Get photos before deleting
    $stmt = $koneksi->prepare("SELECT fotos FROM paket WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Decode JSON array of photos
        $fotosArray = json_decode($row['fotos'], true);
        if ($fotosArray && is_array($fotosArray)) {
            // Delete all photos
            foreach ($fotosArray as $foto) {
                $fotoPath = "uploads/" . $foto;
                if (file_exists($fotoPath)) {
                    unlink($fotoPath);
                }
            }
        }
        
        // Delete gallery photos
        $galleryStmt = $koneksi->prepare("SELECT photo_filename FROM package_gallery WHERE package_id = ?");
        $galleryStmt->bind_param("i", $id);
        $galleryStmt->execute();
        $galleryResult = $galleryStmt->get_result();
        
        while ($galleryRow = $galleryResult->fetch_assoc()) {
            $galleryPath = "uploads/gallery/" . $galleryRow['photo_filename'];
            if (file_exists($galleryPath)) {
                unlink($galleryPath);
            }
        }
        $galleryStmt->close();
        
        // Delete from database
        $deleteStmt = $koneksi->prepare("DELETE FROM paket WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        $deleteStmt->close();
    }
    
    $stmt->close();
    header("Location: admin.php?deleted=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Vacationland</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css?v=<?= time() ?>">
</head>
<body>
    <header class="admin-header">
        <div class="header-content">
            <div class="logo-section">
                <img src="../Asset/logo/logompti.png" alt="Vacationland Logo">
                <h1>Admin Panel</h1>
            </div>
            <div class="admin-info">
                <span>👤 <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator') ?></span>
                <a href="loginadmin.php?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <?= $message ?>

        <!-- Form Tambah Paket -->
        <div class="admin-section">
            <h2 class="section-title">
                <i class="fas fa-plus-circle"></i> Tambah Paket Wisata Baru
            </h2>
            
            <form action="tambah.php" method="post" enctype="multipart/form-data" class="admin-form">
                <!-- Informasi Dasar Paket -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-info-circle"></i> Informasi Dasar Paket
                    </h3>
                    
                    <div class="form-grid-two">
                        <div class="form-group">
                            <label for="nama">
                                <i class="fas fa-tag"></i> Nama Paket <span class="required">*</span>
                            </label>
                            <input type="text" id="nama" name="nama" required maxlength="255" 
                                   placeholder="Contoh: 2D1N Paket Wisata Yogyakarta">
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">
                                <i class="fas fa-clock"></i> Durasi Paket <span class="required">*</span>
                            </label>
                            <select id="duration" name="duration" required onchange="updateItineraryDays()">
                                <option value="1D0N">1 Hari (Tanpa Menginap)</option>
                                <option value="2D1N" selected>2 Hari 1 Malam</option>
                                <option value="3D2N">3 Hari 2 Malam</option>
                                <option value="4D3N">4 Hari 3 Malam</option>
                                <option value="5D4N">5 Hari 4 Malam</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">
                            <i class="fas fa-align-left"></i> Deskripsi Paket <span class="required">*</span>
                        </label>
                        <textarea id="deskripsi" name="deskripsi" rows="4" required maxlength="1000" 
                                  placeholder="Deskripsikan paket wisata ini secara menarik dan informatif"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">
                            <i class="fas fa-money-bill-wave"></i> Harga Paket
                        </label>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">Rp</span>
                            <input type="number" id="price" name="price" min="0" step="1000" 
                                   placeholder="0">
                            <span class="price-suffix">per orang</span>
                        </div>
                        <small class="form-help">Kosongkan jika harga akan ditentukan kemudian</small>
                    </div>
                </div>

                <!-- Upload Foto -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-camera"></i> Upload Foto Paket
                    </h3>
                    
                    <div class="file-upload-area">
                        <input type="file" name="fotos[]" multiple accept="image/*" required>
                        <div class="upload-content">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h4>Drag & Drop Foto atau Klik untuk Pilih</h4>
                            <p>Upload 3-6 foto untuk paket ini</p>
                            <div class="upload-requirements">
                                <div class="req-item">
                                    <i class="fas fa-check"></i> Format: JPG, JPEG, PNG
                                </div>
                                <div class="req-item">
                                    <i class="fas fa-check"></i> Ukuran maksimal: 5MB per foto
                                </div>
                                <div class="req-item">
                                    <i class="fas fa-check"></i> Minimal 3 foto, maksimal 6 foto
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jadwal Perjalanan -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-calendar-alt"></i> Jadwal Perjalanan (Itinerary)
                    </h3>
                    
                    <div class="itinerary-builder">
                        <div class="itinerary-controls">
                            <button type="button" class="btn-add-day" onclick="addItineraryDay()">
                                <i class="fas fa-plus"></i> Tambah Hari
                            </button>
                            <button type="button" class="btn-reset" onclick="resetItinerary()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                            <div class="day-counter">0 Hari</div>
                        </div>
                        
                        <div class="itinerary-days" id="itinerary-days">
                            <!-- Days will be added dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Daya Tarik Utama -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-star"></i> Daya Tarik Utama
                    </h3>
                    
                    <div class="form-group">
                        <label for="highlights">
                            <i class="fas fa-bullhorn"></i> Highlight Paket
                        </label>
                        <textarea id="highlights" name="highlights" rows="4" 
                                  placeholder="Tuliskan daya tarik utama paket ini, pisahkan dengan tanda |

Contoh:
Candi Borobudur - Warisan Dunia UNESCO | Keraton Yogyakarta - Istana Sultan yang masih aktif | Malioboro Street - Jantung kota Yogyakarta | Kuliner Khas Jogja - Gudeg dan jajanan tradisional"></textarea>
                        <small class="form-help">
                            Setiap daya tarik dipisah dengan tanda | (Shift + \)
                            <br>Contoh: Candi Borobudur | Keraton Yogyakarta | Malioboro Street
                        </small>
                    </div>
                </div>

                <!-- Yang Termasuk dan Tidak Termasuk -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-list-check"></i> Detail Paket
                    </h3>
                    
                    <div class="form-grid-two">
                        <div class="form-group">
                            <label for="inclusions">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                                Yang Termasuk
                            </label>
                            <textarea id="inclusions" 
                                      name="inclusions" 
                                      rows="6" 
                                      placeholder="Tuliskan apa yang termasuk dalam paket, pisahkan dengan tanda |

Contoh:
Hotel bintang 3 selama 1 malam | Transportasi mobil ber-AC | Tiket masuk semua tempat wisata | Pemandu wisata berpengalaman | Makan siang 2x | Sarapan 1x | Air mineral selama perjalanan | Asuransi perjalanan"></textarea>
                            <small class="form-help">Pisahkan setiap item dengan tanda | (Shift + \)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="exclusions">
                                <i class="fas fa-times-circle" style="color: #e74c3c;"></i> 
                                Yang Tidak Termasuk
                            </label>
                            <textarea id="exclusions" 
                                      name="exclusions" 
                                      rows="6" 
                                      placeholder="Tuliskan apa yang tidak termasuk dalam paket, pisahkan dengan tanda |

Contoh:
Tiket pesawat ke Yogyakarta | Makan malam | Belanja pribadi | Tips untuk pemandu | Keperluan pribadi lainnya"></textarea>
                            <small class="form-help">Pisahkan setiap item dengan tanda | (Shift + \)</small>
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="form-actions">
                    <button type="button" class="btn-preview" onclick="previewPackage()">
                        <i class="fas fa-eye"></i> Preview Paket
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Simpan Paket Wisata
                    </button>
                </div>
            </form>
        </div>

        <!-- Daftar Paket -->
        <div class="admin-section">
            <h2 class="section-title">
                <i class="fas fa-list"></i> Daftar Paket Wisata
            </h2>

            <?php
            $stmt = $koneksi->prepare("SELECT id, nama, deskripsi, fotos FROM paket ORDER BY id DESC");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0):
            ?>
            <div class="packages-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
<div class="package-card">
    <div class="package-image">
        <?php
        $fotosArray = json_decode($row['fotos'], true);
        $firstPhoto = '';
        $photoCount = 0;
        
        if (is_array($fotosArray) && !empty($fotosArray)) {
            $photoCount = count($fotosArray);
            foreach ($fotosArray as $foto) {
                if (!empty($foto)) {
                    $fotoPath = "uploads/" . htmlspecialchars($foto);
                    if (file_exists($fotoPath)) {
                        $firstPhoto = $fotoPath;
                        break;
                    }
                }
            }
        }
        
        if (empty($firstPhoto)) {
            $firstPhoto = "../Asset/Package_Culture/borobudur.jpg";
        }
        ?>
        
        <img src="<?= $firstPhoto ?>" 
             alt="<?= htmlspecialchars($row['nama']) ?>"
             onerror="this.src='../Asset/Package_Culture/borobudur.jpg'">
        
        <div class="photo-badge">
            <i class="fas fa-images"></i> <?= $photoCount ?> Foto
        </div>
    </div>
    
    <div class="package-content">
        <h3 class="package-title"><?= htmlspecialchars($row['nama']) ?></h3>
        <p class="package-description">
            <?= htmlspecialchars(substr($row['deskripsi'], 0, 100)) ?>...
        </p>
        
        <div class="package-actions">
            <a href="../FrontEnd/html/package_detail.html?id=<?= $row['id'] ?>" 
               class="view-btn" target="_blank">
                <i class="fas fa-eye"></i> Lihat
            </a>
            <button type="button" 
                    onclick="openGalleryManage(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>')" 
                    class="btn-gallery">
                <i class="fas fa-images"></i> Gallery
            </button>
            <a href="?hapus=<?= $row['id'] ?>" 
               class="delete-btn" 
               onclick="return confirm('Yakin ingin menghapus paket ini?')">
                <i class="fas fa-trash"></i> Hapus
            </a>
        </div>
    </div>
</div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="no-packages">
                <i class="fas fa-suitcase-rolling"></i>
                <h3>Belum Ada Paket Tour</h3>
                <p>Mulai dengan menambahkan paket wisata pertama Anda.</p>
            </div>
            <?php endif; 
            $stmt->close();
            ?>
        </div>
    </div>

    <!-- Gallery Management Modal -->
    <div id="galleryManageModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3><i class="fas fa-images"></i> Kelola Gallery: <span id="galleryPackageName"></span></h3>
                <span class="close" onclick="closeGalleryModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Upload New Photos Section -->
                <div class="gallery-section">
                    <h4><i class="fas fa-plus"></i> Tambah Foto Baru</h4>
                    <form id="galleryUploadForm" enctype="multipart/form-data">
                        <input type="hidden" id="galleryPackageId" name="package_id">
                        
                        <div class="form-group">
                            <label for="galleryFiles">Pilih Foto untuk ditambahkan:</label>
                            <input type="file" id="galleryFiles" name="photos[]" accept="image/*" multiple>
                            <small>Maksimal 10 foto, ukuran maksimal 5MB per foto</small>
                        </div>
                        
                        <div id="galleryCaptions" class="photo-captions"></div>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-upload"></i> Upload Foto
                        </button>
                    </form>
                </div>
                
                <hr style="margin: 30px 0;">
                
                <!-- Existing Photos Management -->
                <div class="gallery-section">
                    <h4><i class="fas fa-edit"></i> Foto yang Ada</h4>
                    <div id="existingPhotos" class="existing-photos-grid">
                        <!-- Photos will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="admin-gallery.js"></script>
    <script>
// Gallery management functions - Fixed version
let galleryManager = {
    currentPackageId: null,
    currentPackageName: '',
    
    openGalleryManage: function(packageId, packageName) {
        this.currentPackageId = packageId;
        this.currentPackageName = packageName;
        
        // Set modal content
        document.getElementById('galleryPackageId').value = packageId;
        document.getElementById('galleryPackageName').textContent = packageName;
        
        // Load existing photos
        this.loadExistingPhotos(packageId);
        
        // Show modal
        document.getElementById('galleryManageModal').style.display = 'block';
    },
    
    closeGalleryModal: function() {
        document.getElementById('galleryManageModal').style.display = 'none';
        this.currentPackageId = null;
        this.currentPackageName = '';
        
        // Clear file input
        document.getElementById('galleryFiles').value = '';
        document.getElementById('galleryCaptions').innerHTML = '';
    },
    
    loadExistingPhotos: function(packageId) {
        const container = document.getElementById('existingPhotos');
        container.innerHTML = '<p class="loading">Loading photos...</p>';
        
        fetch(`get_gallery_photos.php?package_id=${encodeURIComponent(packageId)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Get as text first for debugging
            })
            .then(text => {
                console.log('Raw response:', text); // Debug log
                
                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON response: ' + text);
                }
                
                console.log('Parsed data:', data, 'Type:', typeof data, 'Is array:', Array.isArray(data)); // Debug log
                
                // Handle error response
                if (data && data.error) {
                    container.innerHTML = `<p class="error">Error loading photos: ${data.error}</p>`;
                    return;
                }
                
                // Ensure we have an array
                if (!Array.isArray(data)) {
                    console.error('Expected array, got:', typeof data, data);
                    
                    // If it's an object with photos property, use that
                    if (data && typeof data === 'object' && Array.isArray(data.photos)) {
                        data = data.photos;
                    } else {
                        container.innerHTML = '<p class="error">Invalid data format received</p>';
                        return;
                    }
                }
                
                // Handle empty array
                if (data.length === 0) {
                    container.innerHTML = '<p class="no-photos">Belum ada foto gallery untuk paket ini.</p>';
                    return;
                }
                
                // Render photos
                container.innerHTML = data.map(photo => {
                    // Ensure all required properties exist
                    const id = photo.id || 0;
                    const filename = photo.photo_filename || '';
                    const caption = photo.caption || '';
                    const order = photo.photo_order || 0;
                    
                    return `
                        <div class="photo-item" data-photo-id="${id}">
                            <div class="photo-wrapper">
                                <img src="uploads/gallery/${filename}" 
                                     alt="${caption}"
                                     onerror="this.src='../Asset/Package_Culture/borobudur.jpg'">
                                <div class="photo-overlay">
                                    <button class="btn-edit-caption" onclick="galleryManager.editCaption(${id}, '${caption.replace(/'/g, '\\\'')}')" title="Edit Caption">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-move-up" onclick="galleryManager.movePhoto(${id}, 'up')" title="Move Up">
                                        <i class="fas fa-arrow-up"></i>
                                    </button>
                                    <button class="btn-move-down" onclick="galleryManager.movePhoto(${id}, 'down')" title="Move Down">
                                        <i class="fas fa-arrow-down"></i>
                                    </button>
                                    <button class="btn-delete-photo" onclick="galleryManager.deletePhoto(${id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="photo-caption">
                                <span class="caption-text">${caption || 'No caption'}</span>
                                <span class="photo-order">Order: ${order}</span>
                            </div>
                        </div>
                    `;
                }).join('');
            })
            .catch(error => {
                console.error('Error loading photos:', error);
                container.innerHTML = `<p class="error">Error loading photos: ${error.message}</p>`;
            });
    },
    
    editCaption: function(photoId, currentCaption) {
        const newCaption = prompt('Edit caption:', currentCaption);
        if (newCaption !== null && newCaption !== currentCaption) {
            fetch('update_photo_caption.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    photo_id: photoId,
                    caption: newCaption
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.loadExistingPhotos(this.currentPackageId);
                } else {
                    alert('Error updating caption: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error updating caption:', error);
                alert('Error updating caption');
            });
        }
    },
    
    movePhoto: function(photoId, direction) {
        fetch('move_photo_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                photo_id: photoId,
                direction: direction
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.loadExistingPhotos(this.currentPackageId);
            } else {
                alert('Error moving photo: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error moving photo:', error);
            alert('Error moving photo');
        });
    },
    
    deletePhoto: function(photoId) {
        if (confirm('Are you sure you want to delete this photo?')) {
            fetch('delete_gallery_photo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    photo_id: photoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.loadExistingPhotos(this.currentPackageId);
                } else {
                    alert('Error deleting photo: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting photo:', error);
                alert('Error deleting photo');
            });
        }
    }
};

// Gallery upload form handler
document.addEventListener('DOMContentLoaded', function() {
    const galleryUploadForm = document.getElementById('galleryUploadForm');
    const galleryFiles = document.getElementById('galleryFiles');
    const galleryCaptions = document.getElementById('galleryCaptions');
    
    // Handle file selection for captions
    galleryFiles.addEventListener('change', function() {
        const files = this.files;
        galleryCaptions.innerHTML = '';
        
        for (let i = 0; i < files.length; i++) {
            const captionDiv = document.createElement('div');
            captionDiv.className = 'caption-input-group';
            captionDiv.innerHTML = `
                <label>Caption for ${files[i].name}:</label>
                <input type="text" name="captions[]" placeholder="Enter caption for this photo">
            `;
            galleryCaptions.appendChild(captionDiv);
        }
    });
    
    // Handle gallery upload form submission
    galleryUploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const files = galleryFiles.files;
        
        if (files.length === 0) {
            alert('Please select at least one photo to upload');
            return;
        }
        
        if (files.length > 10) {
            alert('Maximum 10 photos allowed');
            return;
        }
        
        // Check file sizes
        for (let file of files) {
            if (file.size > 5 * 1024 * 1024) { // 5MB
                alert(`File ${file.name} is too large. Maximum size is 5MB`);
                return;
            }
        }
        
        fetch('upload_additional_photos.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Photos uploaded successfully!');
                galleryFiles.value = '';
                galleryCaptions.innerHTML = '';
                galleryManager.loadExistingPhotos(galleryManager.currentPackageId);
            } else {
                alert('Error uploading photos: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error uploading photos:', error);
            alert('Error uploading photos');
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('galleryManageModal');
        if (event.target === modal) {
            galleryManager.closeGalleryModal();
        }
    });
});

// Make galleryManager available globally
window.galleryManager = galleryManager;

// Fix the global functions to use galleryManager
function openGalleryManage(packageId, packageName) {
    galleryManager.openGalleryManage(packageId, packageName);
}

function closeGalleryModal() {
    galleryManager.closeGalleryModal();
}

// Itinerary Management Functions
let dayCount = 0;

function updateItineraryDays() {
    const durationSelect = document.getElementById('duration');
    const selectedDuration = durationSelect.value;
    
    // Extract number of days from duration (e.g., "2D1N" -> 2)
    const days = parseInt(selectedDuration.match(/(\d+)D/)?.[1] || 2);
    
    // Reset and rebuild itinerary
    const container = document.getElementById('itinerary-days');
    container.innerHTML = '';
    dayCount = 0;
    
    // Add days based on selected duration
    for (let i = 0; i < days; i++) {
        addItineraryDay();
    }
    
    updateDayCounter();
}

function addItineraryDay() {
    dayCount++;
    const container = document.getElementById('itinerary-days');
    
    const dayDiv = document.createElement('div');
    dayDiv.className = 'day-itinerary';
    dayDiv.id = `day-${dayCount}`;
    
    dayDiv.innerHTML = `
        <div class="day-header" onclick="toggleDayContent(${dayCount})">
            <span>Hari ${dayCount}</span>
        </div>
        <div class="day-content" id="day-content-${dayCount}">
            <div class="form-group">
                <label for="day-title-${dayCount}">Judul Hari ${dayCount}</label>
                <input type="text" 
                       id="day-title-${dayCount}" 
                       name="day_titles[]" 
                       placeholder="Contoh: Hari 1 - Tiba di Yogyakarta"
                       value="Hari ${dayCount}">
            </div>
            
            <div class="activities-container" id="activities-${dayCount}">
                <label>Aktivitas Hari ${dayCount}</label>
                <div class="activity-item">
                    <div class="form-grid-two">
                        <input type="time" name="day_${dayCount}_times[]" placeholder="Waktu">
                        <input type="text" name="day_${dayCount}_activities[]" placeholder="Deskripsi aktivitas">
                    </div>
                </div>
            </div>
            
            <div class="activity-controls">
                <button type="button" class="btn-add-activity" onclick="addActivity(${dayCount})">
                    <i class="fas fa-plus"></i> Tambah Aktivitas
                </button>
                <button type="button" class="btn-remove-day" onclick="removeDay(${dayCount})">
                    <i class="fas fa-trash"></i> Hapus Hari
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(dayDiv);
    updateDayCounter();
    
    // Expand the newly added day
    setTimeout(() => {
        toggleDayContent(dayCount, true);
    }, 100);
}

function toggleDayContent(dayNum, forceOpen = false) {
    const header = document.querySelector(`#day-${dayNum} .day-header`);
    const content = document.getElementById(`day-content-${dayNum}`);
    
    if (!header || !content) return;
    
    if (forceOpen || !content.classList.contains('expanded')) {
        // Close all other days
        document.querySelectorAll('.day-content').forEach(dayContent => {
            if (dayContent.id !== `day-content-${dayNum}`) {
                dayContent.classList.remove('expanded');
                dayContent.parentElement.querySelector('.day-header').classList.remove('active');
            }
        });
        
        // Open this day
        header.classList.add('active');
        content.classList.add('expanded');
    } else {
        // Close this day
        header.classList.remove('active');
        content.classList.remove('expanded');
    }
}

function addActivity(dayNum) {
    const container = document.getElementById(`activities-${dayNum}`);
    const activityDiv = document.createElement('div');
    activityDiv.className = 'activity-item';
    
    activityDiv.innerHTML = `
        <div class="form-grid-two">
            <input type="time" name="day_${dayNum}_times[]" placeholder="Waktu">
            <input type="text" name="day_${dayNum}_activities[]" placeholder="Deskripsi aktivitas">
        </div>
        <button type="button" class="btn-remove-activity" onclick="removeActivity(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(activityDiv);
}

function removeActivity(button) {
    button.parentElement.remove();
}

function removeDay(dayNum) {
    if (dayCount <= 1) {
        alert('Minimal harus ada 1 hari dalam itinerary');
        return;
    }
    
    if (confirm(`Hapus Hari ${dayNum}?`)) {
        document.getElementById(`day-${dayNum}`).remove();
        dayCount--;
        updateDayCounter();
        renumberDays();
    }
}

function renumberDays() {
    const days = document.querySelectorAll('.day-itinerary');
    days.forEach((day, index) => {
        const newDayNum = index + 1;
        const oldId = day.id;
        
        // Update day container
        day.id = `day-${newDayNum}`;
        
        // Update header
        const header = day.querySelector('.day-header span');
        if (header) header.textContent = `Hari ${newDayNum}`;
        
        // Update header onclick
        const headerDiv = day.querySelector('.day-header');
        if (headerDiv) headerDiv.setAttribute('onclick', `toggleDayContent(${newDayNum})`);
        
        // Update content id
        const content = day.querySelector('.day-content');
        if (content) content.id = `day-content-${newDayNum}`;
        
        // Update form elements
        updateDayFormElements(day, newDayNum);
    });
    
    dayCount = days.length;
}

function updateDayFormElements(dayElement, dayNum) {
    // Update day title
    const titleInput = dayElement.querySelector('input[name="day_titles[]"]');
    if (titleInput) {
        titleInput.id = `day-title-${dayNum}`;
        titleInput.placeholder = `Contoh: Hari ${dayNum} - Tiba di Yogyakarta`;
        if (titleInput.value.startsWith('Hari ')) {
            titleInput.value = `Hari ${dayNum}`;
        }
    }
    
    // Update activities container
    const activitiesContainer = dayElement.querySelector('.activities-container');
    if (activitiesContainer) {
        activitiesContainer.id = `activities-${dayNum}`;
        
        const label = activitiesContainer.querySelector('label');
        if (label) label.textContent = `Aktivitas Hari ${dayNum}`;
    }
    
    // Update time and activity inputs
    const timeInputs = dayElement.querySelectorAll('input[type="time"]');
    const activityInputs = dayElement.querySelectorAll('input[type="text"]:not([name="day_titles[]"])');
    
    timeInputs.forEach(input => {
        input.name = `day_${dayNum}_times[]`;
    });
    
    activityInputs.forEach(input => {
        if (input.name.includes('_activities[]')) {
            input.name = `day_${dayNum}_activities[]`;
        }
    });
    
    // Update button onclick
    const addActivityBtn = dayElement.querySelector('.btn-add-activity');
    if (addActivityBtn) {
        addActivityBtn.setAttribute('onclick', `addActivity(${dayNum})`);
    }
    
    const removeDayBtn = dayElement.querySelector('.btn-remove-day');
    if (removeDayBtn) {
        removeDayBtn.setAttribute('onclick', `removeDay(${dayNum})`);
    }
}

function resetItinerary() {
    if (confirm('Reset semua itinerary? Data yang sudah diisi akan hilang.')) {
        const container = document.getElementById('itinerary-days');
        container.innerHTML = '';
        dayCount = 0;
        
        // Add one default day
        addItineraryDay();
        updateDayCounter();
    }
}

function updateDayCounter() {
    const counter = document.querySelector('.day-counter');
    if (counter) {
        counter.textContent = `${dayCount} Hari`;
    }
}

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    // Initialize with default duration
    updateItineraryDays();
    
    // Handle form submission to collect itinerary data
    const form = document.querySelector('.admin-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const itineraryData = collectItineraryData();
            
            // Add itinerary data to form
            const itineraryInput = document.createElement('input');
            itineraryInput.type = 'hidden';
            itineraryInput.name = 'itinerary';
            itineraryInput.value = JSON.stringify(itineraryData);
            
            form.appendChild(itineraryInput);
        });
    }
});

function collectItineraryData() {
    const itineraryData = {};
    
    document.querySelectorAll('.day-itinerary').forEach((dayElement, index) => {
        const dayNum = index + 1;
        const dayId = `day_${dayNum}`;
        
        const titleInput = dayElement.querySelector('input[name="day_titles[]"]');
        const timeInputs = dayElement.querySelectorAll(`input[name="day_${dayNum}_times[]"]`);
        const activityInputs = dayElement.querySelectorAll(`input[name="day_${dayNum}_activities[]"]`);
        
        const activities = [];
        for (let i = 0; i < Math.max(timeInputs.length, activityInputs.length); i++) {
            const time = timeInputs[i]?.value || '';
            const description = activityInputs[i]?.value || '';
            
            if (description.trim()) {
                activities.push({
                    time: time,
                    description: description.trim()
                });
            }
        }
        
        if (activities.length > 0) {
            itineraryData[dayId] = {
                title: titleInput?.value || `Hari ${dayNum}`,
                activities: activities
            };
        }
    });
    
    return itineraryData;
}

// Preview function
function previewPackage() {
    const formData = new FormData(document.querySelector('.admin-form'));
    const itineraryData = collectItineraryData();
    
    // Create preview window
    const previewWindow = window.open('', 'preview', 'width=800,height=600,scrollbars=yes');
    
    previewWindow.document.write(`
        <html>
        <head>
            <title>Preview Paket</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .preview-section { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
                .preview-title { color: #2c3e50; font-size: 1.5em; margin-bottom: 10px; }
                .day-preview { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; }
            </style>
        </head>
        <body>
            <h1>Preview: ${formData.get('nama') || 'Paket Wisata'}</h1>
            
            <div class="preview-section">
                <div class="preview-title">Deskripsi</div>
                <p>${formData.get('deskripsi') || 'Tidak ada deskripsi'}</p>
            </div>
            
            <div class="preview-section">
                <div class="preview-title">Detail Paket</div>
                <p><strong>Durasi:</strong> ${formData.get('duration') || '2D1N'}</p>
                <p><strong>Harga:</strong> Rp ${parseInt(formData.get('price') || 0).toLocaleString('id-ID')}</p>
            </div>
            
            <div class="preview-section">
                <div class="preview-title">Itinerary</div>
                ${Object.entries(itineraryData).map(([dayId, dayData]) => `
                    <div class="day-preview">
                        <h3>${dayData.title}</h3>
                        <ul>
                            ${dayData.activities.map(activity => `
                                <li>${activity.time ? activity.time + ' - ' : ''}${activity.description}</li>
                            `).join('')}
                        </ul>
                    </div>
                `).join('')}
            </div>
            
            <button onclick="window.close()">Tutup Preview</button>
        </body>
        </html>
    `);
}
</script>
</body>
</html>
