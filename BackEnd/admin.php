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
    <!-- Ganti meta viewport dengan yang lebih comprehensive -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="theme-color" content="#3498db">
    <title>Admin Panel | Vacationland</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css?v=<?= time() ?>">

    <!-- Tambahkan CSS inline di head untuk override semua CSS -->
    <style>
/* Force modal visibility */
#galleryManageModal {
    font-family: 'Roboto', sans-serif !important;
    box-sizing: border-box !important;
}

#galleryManageModal * {
    box-sizing: border-box !important;
}

#galleryManageModal .modal-content {
    position: relative !important;
    z-index: 1000000 !important;
}

/* Force grid visibility */
#existingPhotos {
    display: grid !important;
    visibility: visible !important;
}

#existingPhotos .photo-item {
    display: flex !important;
    visibility: visible !important;
}

/* Debug styling */
.photo-item {
    border: 2px solid red !important; /* Temporary debug border */
}
</style>
</head>
<body>
    <!-- Update bagian header untuk konsistensi -->
<header class="admin-header">
    <div class="header-content">
        <div class="logo-section">
            <img src="../Asset/logo/logompti.png" alt="Vacationland Logo">
            <h1>Vacationland Admin</h1>
        </div>
        <div class="admin-info">
            <span><i class="fas fa-user-shield"></i> Admin Panel</span>
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
                <i class="fas fa-plus-circle"></i>
                Tambah Paket Wisata Baru
            </h2>
            
            <form action="tambah.php" method="POST" enctype="multipart/form-data">
                <!-- Informasi Dasar -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-info-circle"></i>
                        Informasi Dasar Paket
                    </h3>
                    
                    <div class="form-grid-two">
                        <div class="form-group">
                            <label for="nama">
                                <i class="fas fa-tag"></i>
                                Nama Paket <span class="required">*</span>
                            </label>
                            <input type="text" id="nama" name="nama" required 
                                   placeholder="Contoh: 2D1N Yogyakarta Cultural Tour">
                            <small class="form-help">Nama paket yang menarik dan deskriptif</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">
                                <i class="fas fa-clock"></i>
                                Durasi
                            </label>
                            <select id="duration" name="duration">
                                <option value="1D">1 Hari (1D)</option>
                                <option value="2D1N" selected>2 Hari 1 Malam (2D1N)</option>
                                <option value="3D2N">3 Hari 2 Malam (3D2N)</option>
                                <option value="4D3N">4 Hari 3 Malam (4D3N)</option>
                                <option value="5D4N">5 Hari 4 Malam (5D4N)</option>
                                <option value="Custom">Custom</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">
                            <i class="fas fa-align-left"></i>
                            Deskripsi Paket <span class="required">*</span>
                        </label>
                        <textarea id="deskripsi" name="deskripsi" rows="4" required
                         placeholder="Deskripsikan paket wisata dengan menarik. Jelaskan apa yang membuat paket ini istimewa..."></textarea>
                        <small class="form-help">Deskripsi yang menarik akan meningkatkan minat customer</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">
                            <i class="fas fa-money-bill-wave"></i>
                            Harga per Orang
                        </label>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">Rp</span>
                            <input type="number" id="price" name="price" 
                                   placeholder="500000" min="0" step="1000">
                            <span class="price-suffix">per orang</span>
                        </div>
                        <small class="form-help">Harga sudah termasuk yang ada di inclusion</small>
                    </div>
                </div>
                
                <!-- Upload Foto Utama -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-camera"></i>
                        Foto Utama Paket
                    </h3>
                    
                    <div class="file-upload-area">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-content">
                            <h4>Upload Foto Paket (3-6 foto)</h4>
                            <p>Pilih foto terbaik untuk menarik perhatian customer</p>
                            <input type="file" id="fotos" name="fotos[]" multiple accept="image/*" required>
                            
                            <div class="upload-requirements">
                                <span class="req-item">
                                    <i class="fas fa-images"></i> 3-6 Foto
                                </span>
                                <span class="req-item">
                                    <i class="fas fa-file-image"></i> JPG/PNG
                                </span>
                                <span class="req-item">
                                    <i class="fas fa-weight"></i> Max 5MB
                                </span>
                                <span class="req-item">
                                    <i class="fas fa-expand-arrows-alt"></i> Min 800x600px
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form action buttons dengan style baru -->
                <div class="form-actions">
                    <button type="button" class="btn-preview" onclick="previewForm()">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Simpan Paket
                    </button>
                </div>
            </form>
        </div>

        <!-- Daftar Paket -->
        <div class="admin-section">
            <h2 class="section-title">
                <i class="fas fa-list"></i>
                Daftar Paket Wisata
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
                <i class="fas fa-eye"></i> Lihat Detail
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
                <button class="btn-primary" onclick="document.getElementById('nama').focus();">
                    <i class="fas fa-plus"></i> Tambah Paket Pertama
                </button>
            </div>
            <?php endif; 
            $stmt->close();
            ?>
        </div>
    </div>

    <!-- Gallery Management Modal - PERBAIKAN LENGKAP -->
    <div id="galleryManageModal" style="
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0,0,0,0.8);
        z-index: 999999;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(3px);
    ">
        <div class="modal-content" style="
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            position: relative;
            margin: auto;
        ">
            <div class="modal-header" style="
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                padding: 15px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 15px 15px 0 0;
                flex-shrink: 0;
            ">
                <h3 style="
                    margin: 0;
                    font-size: 1.2rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                ">
                    <i class="fas fa-images"></i> 
                    Gallery: <span id="galleryPackageName">-</span>
                </h3>
                <button class="close" onclick="closeGalleryModal()" style="
                    background: rgba(255,255,255,0.2);
                    border: none;
                    color: white;
                    width: 35px;
                    height: 35px;
                    border-radius: 50%;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.1rem;
                ">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body" style="
                padding: 20px;
                overflow-y: auto;
                flex: 1;
                background: #f8f9fa;
            ">
                <!-- Upload Section -->
                <div style="
                    background: white;
                    padding: 15px;
                    border-radius: 10px;
                    margin-bottom: 20px;
                    border: 1px solid #e9ecef;
                ">
                    <h4 style="
                        margin: 0 0 10px 0;
                        color: #2c3e50;
                        font-size: 0.95rem;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                    ">
                        <i class="fas fa-upload"></i> Upload Foto Baru
                    </h4>
                    
                    <form id="galleryUploadForm" enctype="multipart/form-data">
                        <input type="hidden" id="galleryPackageId" name="package_id">
                        
                        <div style="margin-bottom: 10px;">
                            <label style="
                                font-size: 0.8rem;
                                margin-bottom: 5px;
                                display: block;
                                color: #555;
                            ">Pilih Foto (Max 10 files, 5MB each):</label>
                            <input type="file" 
                                   id="galleryFiles" 
                                   name="photos[]" 
                                   multiple 
                                   accept="image/*" 
                                   required
                                   style="
                                       padding: 8px;
                                       border: 2px dashed #3498db;
                                       border-radius: 6px;
                                       background: white;
                                       width: 100%;
                                       box-sizing: border-box;
                                   ">
                        </div>
                        
                        <div id="galleryCaptions" style="margin-bottom: 10px;"></div>
                        
                        <button type="submit" style="
                            background: #3498db;
                            color: white;
                            padding: 8px 16px;
                            border: none;
                            border-radius: 6px;
                            font-weight: 600;
                            font-size: 0.9rem;
                            cursor: pointer;
                            transition: background 0.3s ease;
                        ">
                            <i class="fas fa-upload"></i> Upload Foto
                        </button>
                    </form>
                </div>
                
                <!-- Photos Grid Section -->
                <div style="
                    background: white;
                    padding: 15px;
                    border-radius: 10px;
                    border: 1px solid #e9ecef;
                ">
                    <h4 style="
                        margin: 0 0 15px 0;
                        color: #2c3e50;
                        font-size: 0.95rem;
                        display: flex;
                        align-items: center;
                        gap: 6px;
                    ">
                        <i class="fas fa-photo-video"></i> Foto yang Ada
                    </h4>
                    
                    <div id="existingPhotos" style="
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                        gap: 10px;
                        min-height: 150px;
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 8px;
                        border: 1px solid #e9ecef;
                    ">
                        <div style="
                            grid-column: 1 / -1;
                            text-align: center;
                            padding: 20px;
                            color: #666;
                        ">
                            <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                            <p style="margin: 0; font-size: 0.9rem;">Memuat foto gallery...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
console.log('🚀 Admin script loaded');

// Simple gallery manager - NO CLASSES
window.galleryState = {
    isOpen: false,
    currentPackageId: null,
    currentPackageName: ''
};

// Simple modal functions
function openGalleryManage(packageId, packageName) {
    console.log('🖼️ Opening gallery for package:', packageId, packageName);
    
    // Update state
    window.galleryState.isOpen = true;
    window.galleryState.currentPackageId = packageId;
    window.galleryState.currentPackageName = packageName;
    
    // Get modal elements
    const modal = document.getElementById('galleryManageModal');
    const packageIdInput = document.getElementById('galleryPackageId');
    const packageNameSpan = document.getElementById('galleryPackageName');
    
    console.log('🔍 Modal elements found:', {
        modal: !!modal,
        packageIdInput: !!packageIdInput,
        packageNameSpan: !!packageNameSpan
    });
    
    // Update modal content
    if (packageIdInput) {
        packageIdInput.value = packageId;
        console.log('✅ Package ID set to:', packageIdInput.value);
    }
    
    if (packageNameSpan) {
        packageNameSpan.textContent = packageName;
        console.log('✅ Package name set to:', packageNameSpan.textContent);
    }
    
    // Show modal
    if (modal) {
        modal.style.display = 'flex';
        modal.style.opacity = '0';
        
        // Force reflow
        modal.offsetHeight;
        
        modal.style.transition = 'opacity 0.3s ease';
        modal.style.opacity = '1';
        
        document.body.style.overflow = 'hidden';
        console.log('✅ Modal displayed');
    } else {
        console.error('❌ Modal not found!');
        return;
    }
    
    // Load photos
    loadGalleryPhotos(packageId);
}

function closeGalleryModal() {
    console.log('❌ Closing gallery modal');
    
    const modal = document.getElementById('galleryManageModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            window.galleryState.isOpen = false;
            window.galleryState.currentPackageId = null;
            window.galleryState.currentPackageName = '';
            document.body.style.overflow = '';
        }, 300);
    }
    
    // Reset form
    const form = document.getElementById('galleryUploadForm');
    if (form) {
        form.reset();
        const captionsContainer = document.getElementById('galleryCaptions');
        if (captionsContainer) captionsContainer.innerHTML = '';
    }
}

// Load photos function
async function loadGalleryPhotos(packageId) {
    console.log('📸 Loading photos for package:', packageId);
    
    const container = document.getElementById('existingPhotos');
    if (!container) {
        console.error('❌ Container not found!');
        return;
    }
    
    // Show loading
    container.innerHTML = `
        <div style="text-align: center; padding: 20px; color: #666;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
            <p>Memuat foto gallery...</p>
        </div>
    `;
    
    try {
        const timestamp = Date.now();
        const response = await fetch(`get_gallery_photos.php?package_id=${packageId}&_t=${timestamp}`);
        
        console.log('📡 Response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const text = await response.text();
        console.log('📄 Raw response:', text);
        
        let photos;
        try {
            photos = JSON.parse(text);
            console.log('🎯 Parsed photos:', photos);
        } catch (e) {
            console.error('❌ JSON parse error:', e);
            throw new Error('Invalid JSON response');
        }
        
        if (photos.error) {
            throw new Error(photos.error);
        }
        
        displayGalleryPhotos(photos, packageId);
        
    } catch (error) {
        console.error('❌ Error loading photos:', error);
        container.innerHTML = `
            <div style="text-align: center; padding: 20px; background: #fee; border-radius: 8px; color: #c33;">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <h4>Error Loading Photos</h4>
                <p>${error.message}</p>
                <button onclick="loadGalleryPhotos(${packageId})" style="padding: 8px 16px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px;">
                    <i class="fas fa-sync-alt"></i> Retry
                </button>
            </div>
        `;
    }
}

// Display photos function
function displayGalleryPhotos(photos, packageId) {
    console.log('🎨 Displaying photos:', photos);
    
    const container = document.getElementById('existingPhotos');
    if (!container) {
        console.error('❌ Container not found!');
        return;
    }
    
    if (!Array.isArray(photos) || photos.length === 0) {
        container.innerHTML = `
            <div style="
                grid-column: 1 / -1;
                text-align: center;
                padding: 30px;
                color: #666;
                background: white;
                border-radius: 8px;
                border: 2px dashed #dee2e6;
            ">
                <i class="fas fa-images" style="
                    font-size: 2.5rem;
                    color: #3498db;
                    margin-bottom: 10px;
                    display: block;
                "></i>
                <h4 style="margin: 10px 0; color: #2c3e50;">Belum Ada Foto Gallery</h4>
                <p style="margin: 0; font-size: 0.9rem;">Upload foto melalui form di atas untuk menambahkan gallery.</p>
            </div>
        `;
        return;
    }
    
    console.log(`📸 Creating HTML for ${photos.length} photos`);
    
    const photosHTML = photos.map((photo, index) => {
        const protocol = window.location.protocol;
        const host = window.location.host;
        const baseUrl = `${protocol}//${host}/MPTI_TRAVEL/BackEnd/uploads/gallery/`;
        const photoUrl = baseUrl + photo.photo_filename;
        
        const caption = photo.caption || 'Tanpa caption';
        const truncatedCaption = caption.length > 15 ? caption.substring(0, 15) + '...' : caption;
        const escapedCaption = caption.replace(/'/g, '&#39;').replace(/"/g, '&quot;');
        
        console.log(`📷 Photo ${index + 1}: ${photoUrl}`);
        
        return `
            <div class="photo-item" data-photo-id="${photo.id}" style="
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                transition: all 0.3s ease;
                border: 1px solid #e9ecef;
                position: relative;
                min-height: 140px;
                display: flex;
                flex-direction: column;
            ">
                <div class="photo-preview" style="
                    position: relative;
                    width: 100%;
                    height: 80px;
                    overflow: hidden;
                    background: #f8f9fa;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                ">
                    <img src="${photoUrl}" 
                         alt="${escapedCaption}" 
                         loading="lazy" 
                         style="
                             width: 100%;
                             height: 100%;
                             object-fit: cover;
                             cursor: pointer;
                             transition: transform 0.3s ease;
                         "
                         onclick="previewPhoto('${photoUrl}', '${escapedCaption}')"
                         onload="console.log('✅ Image loaded:', this.src)"
                         onerror="console.log('❌ Image error:', this.src); this.src='../Asset/Package_Culture/borobudur.jpg'; this.style.opacity='0.7';">
                    
                    <div class="photo-overlay" style="
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0,0,0,0.7);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 4px;
                        opacity: 0;
                        transition: opacity 0.3s ease;
                    ">
                        <button onclick="editPhotoCaption(${photo.id}, '${escapedCaption}')" 
                                style="
                                    background: #3498db;
                                    color: white;
                                    border: none;
                                    border-radius: 50%;
                                    width: 24px;
                                    height: 24px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    cursor: pointer;
                                    font-size: 0.7rem;
                                    transition: all 0.3s ease;
                                "
                                title="Edit Caption"
                                onmouseover="this.style.background='#2980b9'; this.style.transform='scale(1.1)'"
                                onmouseout="this.style.background='#3498db'; this.style.transform='scale(1)'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deletePhoto(${photo.id}, ${packageId})" 
                                style="
                                    background: #e74c3c;
                                    color: white;
                                    border: none;
                                    border-radius: 50%;
                                    width: 24px;
                                    height: 24px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    cursor: pointer;
                                    font-size: 0.7rem;
                                    transition: all 0.3s ease;
                                "
                                title="Hapus Foto"
                                onmouseover="this.style.background='#c0392b'; this.style.transform='scale(1.1)'"
                                onmouseout="this.style.background='#e74c3c'; this.style.transform='scale(1)'">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="photo-info" style="
                    padding: 6px;
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                ">
                    <div class="photo-caption" id="caption-${photo.id}" style="
                        font-weight: 600;
                        color: #2c3e50;
                        margin-bottom: 3px;
                        font-size: 0.7rem;
                        line-height: 1.2;
                        min-height: 16px;
                        flex: 1;
                    " title="${escapedCaption}">
                        ${truncatedCaption}
                    </div>
                    <div class="photo-meta" style="
                        color: #6c757d;
                        font-size: 0.55rem;
                        margin-bottom: 4px;
                        line-height: 1.2;
                    ">
                        Order: ${photo.photo_order} | ${formatDate(photo.uploaded_at)}
                    </div>
                    <div class="photo-actions" style="
                        display: flex;
                        gap: 2px;
                        justify-content: flex-start;
                    ">
                        <button onclick="movePhoto(${photo.id}, ${packageId}, 'up')" 
                                style="
                                    background: #6c757d;
                                    color: white;
                                    border: none;
                                    border-radius: 3px;
                                    padding: 2px 4px;
                                    font-size: 0.5rem;
                                    cursor: pointer;
                                    transition: background 0.3s ease;
                                    display: flex;
                                    align-items: center;
                                    gap: 1px;
                                "
                                title="Pindah ke atas"
                                onmouseover="this.style.background='#5a6268'"
                                onmouseout="this.style.background='#6c757d'">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button onclick="movePhoto(${photo.id}, ${packageId}, 'down')" 
                                style="
                                    background: #6c757d;
                                    color: white;
                                    border: none;
                                    border-radius: 3px;
                                    padding: 2px 4px;
                                    font-size: 0.5rem;
                                    cursor: pointer;
                                    transition: background 0.3s ease;
                                    display: flex;
                                    align-items: center;
                                    gap: 1px;
                                "
                                title="Pindah ke bawah"
                                onmouseover="this.style.background='#5a6268'"
                                onmouseout="this.style.background='#6c757d'">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    container.innerHTML = photosHTML;
    console.log('✅ Photos HTML set, length:', photosHTML.length);
    
    // Force browser reflow
    container.offsetHeight;
    
    // Add hover effects programmatically
    setTimeout(() => {
        const photoItems = container.querySelectorAll('.photo-item');
        console.log('🎯 Adding hover effects to', photoItems.length, 'items');
        
        photoItems.forEach((item, index) => {
            console.log(`📱 Setting up hover for item ${index + 1}`);
            
            item.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.2)';
                this.style.transform = 'translateY(-2px)';
                this.style.borderColor = '#3498db';
                
                const overlay = this.querySelector('.photo-overlay');
                if (overlay) {
                    overlay.style.opacity = '1';
                }
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
                this.style.transform = 'translateY(0)';
                this.style.borderColor = '#e9ecef';
                
                const overlay = this.querySelector('.photo-overlay');
                if (overlay) {
                    overlay.style.opacity = '0';
                }
            });
        });
    }, 100);
    
    // Verify elements are actually in DOM
    setTimeout(() => {
        const finalCheck = container.querySelectorAll('.photo-item');
        console.log('🔍 Final check - photo items in DOM:', finalCheck.length);
        
        if (finalCheck.length === 0) {
            console.error('❌ No photo items found after insertion!');
            console.log('Container HTML:', container.innerHTML);
        } else {
            console.log('✅ Photos successfully displayed and verified!');
        }
    }, 200);
}

// Helper functions
function formatDate(dateString) {
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

function previewPhoto(imageUrl, caption) {
    const lightbox = document.createElement('div');
    lightbox.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.9); z-index: 10001; display: flex;
        align-items: center; justify-content: center; flex-direction: column;
        padding: 20px; cursor: pointer;
    `;
    
    const img = document.createElement('img');
    img.src = imageUrl;
    img.style.cssText = `
        max-width: 90%; max-height: 80%; object-fit: contain;
        border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    `;
    
    const captionEl = document.createElement('div');
    captionEl.textContent = caption || 'Tanpa caption';
    captionEl.style.cssText = `
        color: white; text-align: center; margin-top: 15px;
        font-size: 1.1rem; max-width: 600px;
    `;
    
    const closeHint = document.createElement('div');
    closeHint.textContent = 'Klik untuk menutup';
    closeHint.style.cssText = `
        color: rgba(255,255,255,0.7); text-align: center;
        margin-top: 10px; font-size: 0.9rem;
    `;
    
    lightbox.appendChild(img);
    lightbox.appendChild(captionEl);
    lightbox.appendChild(closeHint);
    
    lightbox.onclick = () => {
        lightbox.remove();
        document.body.style.overflow = '';
    };
    
    document.body.appendChild(lightbox);
    document.body.style.overflow = 'hidden';
}

function editPhotoCaption(photoId, currentCaption) {
    const newCaption = prompt('Edit caption foto:', currentCaption);
    if (newCaption === null) return; // User cancelled
    
    // Update caption via AJAX
    fetch('update_photo_caption.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            photo_id: photoId,
            caption: newCaption
        })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Caption berhasil diupdate!', 'success');
            loadGalleryPhotos(window.galleryState.currentPackageId);
        } else {
            throw new Error(result.message || 'Gagal update caption');
        }
    })
    .catch(error => {
        console.error('Error updating caption:', error);
        showNotification('Error: ' + error.message, 'error');
    });
}

function deletePhoto(photoId, packageId) {
    if (!confirm('Apakah Anda yakin ingin menghapus foto ini?')) return;
    
    fetch('delete_gallery_photo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `photo_id=${photoId}&package_id=${packageId}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Foto berhasil dihapus!', 'success');
            loadGalleryPhotos(packageId);
        } else {
            throw new Error(result.message || 'Gagal menghapus foto');
        }
    })
    .catch(error => {
        console.error('Error deleting photo:', error);
        showNotification('Error: ' + error.message, 'error');
    });
}

function movePhoto(photoId, packageId, direction) {
    fetch('move_photo_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `photo_id=${photoId}&package_id=${packageId}&direction=${direction}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification(`Foto berhasil dipindah ${direction === 'up' ? 'ke atas' : 'ke bawah'}!`, 'success');
            loadGalleryPhotos(packageId);
        } else {
            throw new Error(result.message || 'Gagal memindah foto');
        }
    })
    .catch(error => {
        console.error('Error moving photo:', error);
        showNotification('Error: ' + error.message, 'error');
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 12px 20px;
        border-radius: 8px; color: white; font-weight: 600; z-index: 10000;
        opacity: 0; transform: translateX(100%); transition: all 0.3s ease;
        max-width: 300px; word-wrap: break-word; font-size: 0.9rem;
        background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
    `;
    
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
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// File selection handler
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM loaded, setting up events...');
    
    // Header scroll effect
    const header = document.querySelector('.admin-header');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
    
    // File upload preview
    const fileInput = document.getElementById('fotos');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            showFilePreview(files);
        });
    }
    
    // Form validation enhancement
    const form = document.querySelector('form[action="tambah.php"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    }
});

function showFilePreview(files) {
    const existingPreview = document.querySelector('.file-preview');
    if (existingPreview) {
        existingPreview.remove();
    }
    
    if (files.length === 0) return;
    
    const previewContainer = document.createElement('div');
    previewContainer.className = 'file-preview';
    previewContainer.style.cssText = `
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 15px;
        margin-top: 20px;
        padding: 20px;
        background: rgba(52, 152, 219, 0.1);
        border-radius: 15px;
        border: 2px dashed #3498db;
    `;
    
    files.forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.style.cssText = `
                    position: relative;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                `;
                
                previewItem.innerHTML = `
                    <img src="${e.target.result}" style="
                        width: 100%;
                        height: 80px;
                        object-fit: cover;
                        display: block;
                    ">
                    <div style="
                        padding: 8px;
                        background: white;
                        font-size: 0.8rem;
                        text-align: center;
                        color: #2c3e50;
                        font-weight: 500;
                    ">
                        ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                    </div>
                    <div style="
                        position: absolute;
                        top: 5px;
                        right: 5px;
                        background: rgba(52, 152, 219, 0.9);
                        color: white;
                        border-radius: 50%;
                        width: 20px;
                        height: 20px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 0.7rem;
                        font-weight: bold;
                    ">
                        ${index + 1}
                    </div>
                `;
                
                previewContainer.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        }
    });
    
    const uploadArea = document.querySelector('.file-upload-area');
    uploadArea.parentNode.insertBefore(previewContainer, uploadArea.nextSibling);
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Field ini wajib diisi');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    // Validate file count
    const fileInput = document.getElementById('fotos');
    if (fileInput && fileInput.files.length < 3) {
        showFieldError(fileInput, 'Minimal 3 foto diperlukan');
        isValid = false;
    }
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = `
        color: #e74c3c;
        font-size: 0.85rem;
        margin-top: 5px;
        padding: 5px 10px;
        background: rgba(231, 76, 60, 0.1);
        border-radius: 5px;
        border-left: 3px solid #e74c3c;
    `;
    errorDiv.textContent = message;
    
    field.style.borderColor = '#e74c3c';
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '';
}

function previewForm() {
    const formData = new FormData(document.querySelector('form[action="tambah.php"]'));
    
    // Show preview modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 10001;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #2c3e50; font-family: 'Lora', serif;">Preview Paket</h3>
                <button onclick="this.closest('.modal').remove()" style="
                    background: #e74c3c;
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 30px;
                    height: 30px;
                    cursor: pointer;
                ">×</button>
            </div>
            <div style="space-y: 15px;">
                <p><strong>Nama:</strong> ${formData.get('nama') || 'Belum diisi'}</p>
                <p><strong>Durasi:</strong> ${formData.get('duration') || 'Belum dipilih'}</p>
                <p><strong>Harga:</strong> Rp ${formData.get('price') ? Number(formData.get('price')).toLocaleString('id-ID') : 'Belum diisi'}</p>
                <p><strong>Deskripsi:</strong> ${formData.get('deskripsi') || 'Belum diisi'}</p>
                <p><strong>Jumlah Foto:</strong> ${document.getElementById('fotos').files.length} file</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

console.log('🎨 Enhanced admin UI loaded');
</script>

<!-- Tambahkan tombol debug di bawah header -->
<div style="position: fixed; top: 100px; right: 20px; z-index: 999;">
    <button onclick="window.debugGallery()" style="background: #e74c3c; color: white; padding: 8px 12px; border: none; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">
        🔍 Debug Gallery
    </button>
</div>
</body>
</html>
