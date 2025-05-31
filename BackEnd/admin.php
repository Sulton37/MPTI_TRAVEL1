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
        case 'invalid_price':
            $message = '<div class="error">Harga tidak valid!</div>';
            break;
        case 'price_too_low':
            $message = '<div class="error">Harga minimal Rp 100.000!</div>';
            break;
        case 'price_too_high':
            $message = '<div class="error">Harga maksimal Rp 50.000.000!</div>';
            break;
        case 'empty_fields':
            $message = '<div class="error">Semua field harus diisi!</div>';
            break;
        case 'no_files':
            $message = '<div class="error">Minimal harus upload 1 foto!</div>';
            break;
        case 'invalid_photo_count':
            $message = '<div class="error">Upload 3-6 foto saja!</div>';
            break;
        case 'invalid_file_type':
            $message = '<div class="error">Hanya file JPG, JPEG, PNG yang diperbolehkan!</div>';
            break;
        case 'file_too_large':
            $message = '<div class="error">Ukuran file maksimal 5MB!</div>';
            break;
        case 'upload_failed':
            $message = '<div class="error">Gagal upload file!</div>';
            break;
        case 'database_error':
            $message = '<div class="error">Terjadi kesalahan database!</div>';
            break;
        case 'input_too_long':
            $message = '<div class="error">Input terlalu panjang!</div>';
            break;
        default:
            $message = '<div class="error">Terjadi kesalahan tidak dikenal!</div>';
            break;
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
    <!-- Enhanced responsive meta tags -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#3498db">
    <meta name="msapplication-navbutton-color" content="#3498db">
    <meta name="apple-mobile-web-app-title" content="Admin Panel">
    
    <title>Admin Panel | Vacationland</title>
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="admin-styles.css?v=<?= time() ?>">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="admin-styles.css" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" as="style">
</head>
<body>
    <!-- Update header section -->
<header class="admin-header">
    <div class="header-content">
        <div class="logo-section">
            <img src="../Asset/logo/logompti.png" alt="Vacationland Logo">
            <h1>Admin Panel</h1>
        </div>
        
        <!-- Mobile Menu Toggle -->
        <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <div class="admin-info">
            <span>Selamat datang, Admin</span>
            <a href="loginadmin.php?logout=1" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> 
                <span class="logout-text">Logout</span>
            </a>
        </div>
    </div>
</header>

<!-- Mobile Sidebar -->
<div class="mobile-sidebar" id="mobileSidebar">
    <div class="mobile-sidebar-header">
        <div class="mobile-logo">
            <img src="../Asset/logo/logompti.png" alt="Logo">
            <span>Admin Panel</span>
        </div>
        <button class="mobile-close" onclick="toggleMobileMenu()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="mobile-nav">
        <a href="#add-package" onclick="scrollToSection('add-package'); toggleMobileMenu();">
            <i class="fas fa-plus-circle"></i>
            <span>Tambah Paket</span>
        </a>
        <a href="#packages-list" onclick="scrollToSection('packages-list'); toggleMobileMenu();">
            <i class="fas fa-list"></i>
            <span>Daftar Paket</span>
        </a>
        <a href="#gallery-management" onclick="scrollToSection('gallery-management'); toggleMobileMenu();">
            <i class="fas fa-images"></i>
            <span>Kelola Gallery</span>
        </a>
        <a href="loginadmin.php?logout=1" class="mobile-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileMenu()"></div>

    <div class="container">
        <?= $message ?>

        <!-- Form Tambah Paket -->
        <div class="admin-section" id="add-package">
            <h2 class="section-title">
                <i class="fas fa-plus-circle"></i>
                Tambah Paket Wisata Baru
            </h2>
            
            <?= $message ?>
            
            <form action="tambah.php" method="POST" enctype="multipart/form-data" id="package-form">
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-info-circle"></i> Informasi Dasar
                    </h3>
                    
                    <div class="form-group">
                        <label for="nama">
                            <i class="fas fa-tag"></i> Nama Paket <span class="required">*</span>
                        </label>
                        <input type="text" id="nama" name="nama" placeholder="Contoh: 3D2N Yogyakarta Heritage Tour" required>
                        <small class="form-help">Nama paket yang menarik dan deskriptif</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">
                            <i class="fas fa-clock"></i> Durasi <span class="required">*</span>
                        </label>
                        <select id="duration" name="duration" required>
                            <option value="1D">1 Hari (Day Trip)</option>
                            <option value="2D1N" selected>2D1N</option>
                            <option value="3D2N">3D2N</option>
                            <option value="4D3N">4D3N</option>
                            <option value="5D4N">5D4N</option>
                            <option value="custom">Custom</option>
                        </select>
                        <small class="form-help">Pilih durasi paket tour</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">
                            <i class="fas fa-align-left"></i> Deskripsi <span class="required">*</span>
                        </label>
                        <textarea id="deskripsi" name="deskripsi" rows="4" placeholder="Deskripsi lengkap tentang paket tour ini..." required></textarea>
                        <small class="form-help">Jelaskan detail paket, destinasi, dan pengalaman yang akan didapat</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">
                            <i class="fas fa-money-bill-wave"></i> Harga (Rupiah) <span class="required">*</span>
                        </label>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">Rp</span>
                            <input type="text" 
                                   id="price" 
                                   name="price" 
                                   placeholder="2.750.000" 
                                   pattern="[0-9.,]*"
                                   required
                                   oninput="formatPriceInput(this)"
                                   onblur="validatePriceInput(this)">
                            <span class="price-suffix">/orang</span>
                        </div>
                        <small class="form-help">Masukkan harga dalam rupiah per orang</small>
                    </div>
                </div>
                
                <!-- File Upload -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-images"></i> Foto Paket
                    </h3>
                    
                    <div class="form-group">
                        <label for="fotos">
                            <i class="fas fa-camera"></i> Upload Foto (3-6 foto) <span class="required">*</span>
                        </label>
                        <div class="file-upload-area">
                            <input type="file" id="fotos" name="fotos[]" multiple accept="image/jpeg,image/jpg,image/png" required>
                            <div class="upload-content">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h4>Pilih atau Drag & Drop Foto</h4>
                                <p>Upload 3-6 foto berkualitas tinggi</p>
                            </div>
                        </div>
                        <div class="upload-requirements">
                            <div class="req-item"><i class="fas fa-check"></i> Format: JPG, JPEG, PNG</div>
                            <div class="req-item"><i class="fas fa-check"></i> Ukuran maksimal: 5MB per file</div>
                            <div class="req-item"><i class="fas fa-check"></i> Minimal 3 foto, maksimal 6 foto</div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Info -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-list"></i> Informasi Tambahan
                    </h3>
                    
                    <div class="form-group">
                        <label for="highlights">
                            <i class="fas fa-star"></i> Highlights/Keunggulan
                        </label>
                        <textarea id="highlights" name="highlights" rows="5" placeholder="• Mengunjungi Candi Borobudur&#10;• Wisata Keraton Yogyakarta&#10;• Kuliner khas Gudeg Yu Djum"></textarea>
                        <small class="form-help">Daftar keunggulan paket (gunakan bullet point dengan •)</small>
                    </div>
                    
                    <div class="form-grid-two">
                        <div class="form-group">
                            <label for="inclusions">
                                <i class="fas fa-check-circle"></i> Yang Termasuk
                            </label>
                            <textarea id="inclusions" name="inclusions" rows="6" placeholder="• Transportasi AC&#10;• Tiket masuk wisata&#10;• Makan sesuai program&#10;• Hotel bintang 3"></textarea>
                            <small class="form-help">Apa saja yang termasuk dalam paket</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="exclusions">
                                <i class="fas fa-times-circle"></i> Yang Tidak Termasuk
                            </label>
                            <textarea id="exclusions" name="exclusions" rows="6" placeholder="• Tiket pesawat&#10;• Pengeluaran pribadi&#10;• Tips guide&#10;• Asuransi perjalanan"></textarea>
                            <small class="form-help">Apa saja yang tidak termasuk</small>
                        </div>
                    </div>
                </div>
                
                <!-- Itinerary Builder -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-calendar-day"></i> Itinerary Builder
                    </h3>
                    
                    <div class="itinerary-builder">
                        <div id="itinerary-days">
                            <!-- Days akan di-generate oleh JavaScript -->
                        </div>
                        
                        <button type="button" class="btn-add-day" onclick="addNewDay()">
                            <i class="fas fa-plus"></i> Tambah Hari Baru
                        </button>
                        
                        <div class="form-group" style="margin-top: 20px;">
                            <label for="itinerary-preview">
                                <i class="fas fa-eye"></i> Preview Itinerary:
                            </label>
                            <div id="itinerary-preview" class="itinerary-preview"></div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="itinerary" id="itinerary-data">
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" onclick="previewForm()" class="btn-preview">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button type="reset" class="btn-primary" style="background: #6c757d;">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Simpan Paket
                    </button>
                </div>
            </form>
        </div>

        <!-- Daftar Paket -->
        <div class="admin-section" id="packages-list">
            <h2 class="section-title">
                <i class="fas fa-list"></i>
                Daftar Paket Wisata
            </h2>
            
            <?php
            $stmt = $koneksi->prepare("SELECT id, nama, deskripsi, fotos, price, duration FROM paket ORDER BY id DESC");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0): ?>
                <div class="packages-grid">
                    <?php while ($paket = $result->fetch_assoc()): ?>
                        <div class="package-card">
                            <div class="package-image">
                                <?php 
                                $fotos = json_decode($paket['fotos'], true);
                                $firstPhoto = !empty($fotos) ? $fotos[0] : 'default.jpg';
                                $photoPath = 'uploads/' . $firstPhoto;
                                
                                if (file_exists($photoPath)): ?>
                                    <img src="<?= $photoPath ?>" alt="<?= htmlspecialchars($paket['nama']) ?>">
                                <?php else: ?>
                                    <img src="../Asset/Package_Culture/borobudur.jpg" alt="Default Image">
                                <?php endif; ?>
                            </div>
                            
                            <div class="package-content">
                                <h3 class="package-title"><?= htmlspecialchars($paket['nama']) ?></h3>
                                <p class="package-description">
                                    <?= htmlspecialchars(substr($paket['deskripsi'], 0, 100)) ?>...
                                </p>
                                
                                <div class="package-meta">
                                    <span class="duration-badge">
                                        <i class="fas fa-clock"></i> <?= htmlspecialchars($paket['duration'] ?? '2D1N') ?>
                                    </span>
                                    <span class="price-badge">
                                        <i class="fas fa-money-bill"></i> 
                                        <?php if ($paket['price'] && $paket['price'] > 0): ?>
                                            Rp <?= number_format($paket['price'], 0, ',', '.') ?>
                                        <?php else: ?>
                                            Hubungi untuk harga
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="package-actions">
                                    <a href="../FrontEnd/html/package_detail.html?id=<?= $paket['id'] ?>" class="view-btn" target="_blank">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                    <button onclick="openGalleryManage(<?= $paket['id'] ?>, '<?= htmlspecialchars($paket['nama'], ENT_QUOTES) ?>')" class="btn-gallery">
                                        <i class="fas fa-images"></i> Gallery
                                    </button>
                                    <a href="?hapus=<?= $paket['id'] ?>" class="delete-btn" onclick="return confirm('Yakin ingin menghapus paket ini?')">
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
                    <p>Silakan tambah paket baru menggunakan form di atas.</p>
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

// Preview Form Function - PERBAIKAN LENGKAP
function previewForm() {
    console.log('👁️ Preview form called');
    
    const form = document.querySelector('form[action="tambah.php"]');
    if (!form) {
        alert('Form tidak ditemukan');
        return;
    }
    
    // Validasi form terlebih dahulu
    if (!validateForm(form)) {
        return;
    }
    
    const formData = new FormData(form);
    
    // Create modal
    const modal = document.createElement('div');
    modal.id = 'preview-modal';
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
        box-sizing: border-box;
    `;
    
    // Build preview content
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white;
        border-radius: 20px;
        padding: 30px;
        max-width: 700px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        box-shadow: 0 25px 50px rgba(0,0,0,0.3);
    `;
    
    // Get form values
    const nama = formData.get('nama') || 'Tidak ada nama';
    const deskripsi = formData.get('deskripsi') || 'Tidak ada deskripsi';
    const duration = formData.get('duration') || '2D1N';
    const price = formData.get('price') || '0';
    const highlights = formData.get('highlights') || 'Tidak ada highlight';
    const inclusions = formData.get('inclusions') || 'Tidak ada inclusions';
    const exclusions = formData.get('exclusions') || 'Tidak ada exclusions';
    const itinerary = formData.get('itinerary') || '{}';
    
    // Process itinerary for display
    let itineraryDisplay = 'Belum ada itinerary';
    try {
        const itineraryData = JSON.parse(itinerary);
        if (itineraryData && typeof itineraryData === 'object') {
            itineraryDisplay = '';
            Object.keys(itineraryData).forEach(dayId => {
                const day = itineraryData[dayId];
                if (day.title && day.activities) {
                    itineraryDisplay += `<strong>${day.title}:</strong><br>`;
                    day.activities.forEach(activity => {
                        if (activity.description) {
                            const time = activity.time || '--:--';
                            itineraryDisplay += `&nbsp;&nbsp;${time} - ${activity.description}<br>`;
                        }
                    });
                    itineraryDisplay += '<br>';
                }
            });
        }
    } catch (e) {
        console.warn('Error parsing itinerary:', e);
    }
    
    // Get selected files info
    const fileInput = document.getElementById('fotos');
    let filesInfo = 'Tidak ada file dipilih';
    if (fileInput && fileInput.files.length > 0) {
        filesInfo = `${fileInput.files.length} file dipilih:<br>`;
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];
            const sizeKB = Math.round(file.size / 1024);
            filesInfo += `• ${file.name} (${sizeKB} KB)<br>`;
        }
    }
    
    modalContent.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f0f0f0; padding-bottom: 15px;">
            <h3 style="margin: 0; color: #2c3e50; font-family: 'Lora', serif; font-size: 1.8rem;">
                <i class="fas fa-eye" style="color: #3498db; margin-right: 10px;"></i>
                Preview Paket
            </h3>
            <button onclick="closePreviewModal()" style="
                background: #e74c3c;
                color: white;
                border: none;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                cursor: pointer;
                font-size: 1.2rem;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            " onmouseover="this.style.background='#c0392b'" onmouseout="this.style.background='#e74c3c'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div style="max-height: 60vh; overflow-y: auto; padding-right: 10px;">
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">
                    <i class="fas fa-tag" style="color: #3498db; margin-right: 8px;"></i>Nama Paket:
                </label>
                <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; border-left: 4px solid #3498db;">
                    ${nama}
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">
                    <i class="fas fa-align-left" style="color: #3498db; margin-right: 8px;"></i>Deskripsi:
                </label>
                <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; border-left: 4px solid #27ae60;">
                    ${deskripsi}
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">
                        <i class="fas fa-clock" style="color: #f39c12; margin-right: 8px;"></i>Durasi:
                    </label>
                    <div style="background: #fff3cd; padding: 12px; border-radius: 8px; border-left: 4px solid #f39c12;">
                        ${duration}
                    </div>
                </div>
                <div>
                    <label style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">
                        <i class="fas fa-money-bill-wave" style="color: #28a745; margin-right: 8px;"></i>Harga:
                    </label>
                    <div style="background: #d4edda; padding: 12px; border-radius: 8px; border-left: 4px solid #28a745;">
                        Rp ${parseInt(price).toLocaleString('id-ID')}
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">
                    <i class="fas fa-star" style="color: #e74c3c; margin-right: 8px;"></i>Highlights:
                </label>
                <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; border-left: 4px solid #e74c3c; white-space: pre-wrap;">
                    ${highlights}
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">
                    <i class="fas fa-calendar-day" style="color: #17a2b8; margin-right: 8px;"></i>Itinerary:
                </label>
                <div style="background: #d1ecf1; padding: 12px; border-radius: 8px; border-left: 4px solid #17a2b8;">
                    ${itineraryDisplay}
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">
                    <i class="fas fa-check-circle" style="color: #28a745; margin-right: 8px;"></i>Included:
                </label>
                <div style="background: #d4edda; padding: 12px; border-radius: 8px; border-left: 4px solid #28a745; white-space: pre-wrap;">
                    ${inclusions}
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">
                    <i class="fas fa-times-circle" style="color: #dc3545; margin-right: 8px;"></i>Excluded:
                </label>
                <div style="background: #f8d7da; padding: 12px; border-radius: 8px; border-left: 4px solid #dc3545; white-space: pre-wrap;">
                    ${exclusions}
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="font-weight: bold; color: #2c3e50; display: block; margin-bottom: 8px;">
                    <i class="fas fa-images" style="color: #6f42c1; margin-right: 8px;"></i>File Foto:
                </label>
                <div style="background: #e2e3f0; padding: 12px; border-radius: 8px; border-left: 4px solid #6f42c1;">
                    ${filesInfo}
                </div>
            </div>
        </div>
        
        <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #f0f0f0; display: flex; gap: 15px; justify-content: center;">
            <button onclick="closePreviewModal()" style="
                background: #6c757d;
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 25px;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
            " onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
                <i class="fas fa-arrow-left"></i>
                Kembali Edit
            </button>
            <button onclick="submitFormFromPreview()" style="
                background: #28a745;
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 25px;
                cursor: pointer;
                font-weight: 600;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
            " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                <i class="fas fa-save"></i>
                Simpan Paket
            </button>
        </div>
    `;
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Click outside to close
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closePreviewModal();
        }
    });
    
    console.log('✅ Preview modal created and displayed');
}

// Close Preview Modal Function
function closePreviewModal() {
    console.log('❌ Closing preview modal');
    
    const modal = document.getElementById('preview-modal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
        console.log('✅ Preview modal closed');
    } else {
        console.warn('⚠️ Preview modal not found');
    }
}

// Submit form from preview
function submitFormFromPreview() {
    console.log('💾 Submitting form from preview');
    
    closePreviewModal();
    
    const form = document.querySelector('form[action="tambah.php"]');
    if (form) {
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            submitBtn.disabled = true;
        }
        
        form.submit();
    } else {
        alert('Form tidak ditemukan!');
    }
}

// Enhanced Form Validation
function validateForm(form) {
    console.log('🔍 Validating form...');
    
    // Clear previous errors
    document.querySelectorAll('.field-error').forEach(error => error.remove());
    document.querySelectorAll('input, textarea, select').forEach(field => {
        field.style.borderColor = '';
    });
    
    let isValid = true;
    
    // Validate nama
    const nama = form.querySelector('[name="nama"]');
    if (!nama || !nama.value.trim()) {
        showFieldError(nama, 'Nama paket harus diisi');
        isValid = false;
    } else if (nama.value.trim().length < 3) {
        showFieldError(nama, 'Nama paket minimal 3 karakter');
        isValid = false;
    }
    
    // Validate deskripsi
    const deskripsi = form.querySelector('[name="deskripsi"]');
    if (!deskripsi || !deskripsi.value.trim()) {
        showFieldError(deskripsi, 'Deskripsi harus diisi');
        isValid = false;
    } else if (deskripsi.value.trim().length < 10) {
        showFieldError(deskripsi, 'Deskripsi minimal 10 karakter');
        isValid = false;
    }
    
    // Validate price - PERBAIKAN TOTAL
    const price = form.querySelector('[name="price"]');
    if (!price || !price.value.trim()) {
        showFieldError(price, 'Harga harus diisi');
        isValid = false;
    } else {
        // Convert formatted price to raw number
        const rawPrice = price.value.replace(/[^0-9]/g, '');
        const priceValue = parseInt(rawPrice);
        
        console.log('💰 Price validation:', {
            input: price.value,
            raw: rawPrice,
            numeric: priceValue
        });
        
        if (isNaN(priceValue) || priceValue <= 0) {
            showFieldError(price, 'Harga harus berupa angka yang valid dan lebih dari 0');
            isValid = false;
        } else if (priceValue < 100000) {
            showFieldError(price, 'Harga minimal Rp 100.000');
            isValid = false;
        } else if (priceValue > 50000000) {
            showFieldError(price, 'Harga maksimal Rp 50.000.000');
            isValid = false;
        }
    }
    
    // Validate file upload
    const fotos = form.querySelector('[name="fotos[]"]');
    if (!fotos || !fotos.files || fotos.files.length === 0) {
        showFieldError(fotos, 'Minimal harus upload 1 foto');
        isValid = false;
    } else if (fotos.files.length < 3) {
        showFieldError(fotos, 'Minimal harus upload 3 foto');
        isValid = false;
    } else if (fotos.files.length > 6) {
        showFieldError(fotos, 'Maksimal 6 foto');
        isValid = false;
    } else {
        // Validate each file
        for (let i = 0; i < fotos.files.length; i++) {
            const file = fotos.files[i];
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            
            if (!allowedTypes.includes(file.type)) {
                showFieldError(fotos, `File ${file.name} bukan format gambar yang valid (JPG/PNG)`);
                isValid = false;
                break;
            }
            
            if (file.size > maxSize) {
                showFieldError(fotos, `File ${file.name} terlalu besar (maksimal 5MB)`);
                isValid = false;
                break;
            }
        }
    }
    
    console.log('🔍 Form validation result:', isValid);
    return isValid;
}

// Show Field Error Function
function showFieldError(field, message) {
    if (!field) return;
    
    // Remove existing error
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Create error element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.cssText = `
        color: #e74c3c;
        font-size: 0.9rem;
        margin-top: 5px;
        padding: 8px 12px;
        background: rgba(231, 76, 60, 0.1);
        border-radius: 5px;
        border-left: 3px solid #e74c3c;
        animation: slideInDown 0.3s ease;
    `;
    errorDiv.textContent = message;
    
    field.style.borderColor = '#e74c3c';
    field.parentNode.appendChild(errorDiv);
}

// Clear Field Error Function
function clearFieldError(field) {
    if (!field) return;
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    field.style.borderColor = '';
}

// Add event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM loaded, setting up event listeners...');
    
    // Clear errors on input
    document.querySelectorAll('input, textarea, select').forEach(field => {
        field.addEventListener('input', function() {
            clearFieldError(this);
        });
        
        field.addEventListener('focus', function() {
            clearFieldError(this);
        });
    });
    
    // Initialize itinerary builder with delay
    setTimeout(() => {
        const itineraryContainer = document.getElementById('itinerary-days');
        if (itineraryContainer) {
            console.log('🗓️ Itinerary container found, initializing...');
            initializeItinerary();
        } else {
            console.warn('⚠️ Itinerary container not found');
        }
    }, 800);
    
    const additionalFields = ['highlights', 'inclusions', 'exclusions'];
    
    additionalFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', validateAdditionalInfo);
        }
    });
    
    console.log('✅ Admin panel initialized');
});

// === ITINERARY MANAGEMENT SYSTEM ===
let itineraryData = {};
let dayCounter = 0;

function initializeItinerary() {
    console.log('🗓️ Initializing itinerary builder...');
    
    // Clear existing data
    itineraryData = {};
    dayCounter = 0;
    
    // Clear container
    const container = document.getElementById('itinerary-days');
    if (container) {
        container.innerHTML = '';
    }
    
    // Add default first day
    addNewDay();
    updateItineraryPreview();
    
    console.log('✅ Itinerary builder initialized');
}

function addNewDay() {
    dayCounter++;
    const dayId = `day-${dayCounter}`;
    
    console.log('➕ Adding new day:', dayId);
    
    // Create day data
    itineraryData[dayId] = {
        title: `Hari ${dayCounter}`,
        activities: [
            { time: '09:00', description: 'Kegiatan pagi' }
        ]
    };
    
    // Render the day
    renderDay(dayId);
    updateItineraryPreview();
    
    console.log('✅ Day added successfully:', dayId);
}

function renderDay(dayId) {
    const container = document.getElementById('itinerary-days');
    if (!container) {
        console.error('❌ Itinerary container not found');
        return;
    }
    
    const dayData = itineraryData[dayId];
    if (!dayData) {
        console.error('❌ Day data not found for:', dayId);
        return;
    }
    
    const dayElement = document.createElement('div');
    dayElement.className = 'itinerary-day';
    dayElement.id = dayId;
    dayElement.setAttribute('data-day-id', dayId);
    
    dayElement.innerHTML = `
        <div class="day-header-editor" onclick="toggleDayActivities('${dayId}')">
            <input type="text" 
                   class="day-title-input" 
                   value="${dayData.title}" 
                   onchange="updateDayTitle('${dayId}', this.value)"
                   onclick="event.stopPropagation()"
                   placeholder="Masukkan judul hari">
            <div class="day-controls">
                <button type="button" class="btn-day-control" onclick="event.stopPropagation(); deleteDayPrompt('${dayId}')"
                        title="Hapus hari">
                    <i class="fas fa-trash"></i>
                </button>
                <span class="day-toggle" title="Buka/Tutup">
                    <i class="fas fa-chevron-down"></i>
                </span>
            </div>
        </div>
        <div class="day-activities" id="${dayId}-activities" style="display: block;">
            <div class="activities-container" id="${dayId}-activities-container">
                ${renderActivities(dayId)}
            </div>
            <button type="button" class="btn-add-activity" onclick="addActivity('${dayId}')">
                <i class="fas fa-plus"></i> Tambah Aktivitas
            </button>
        </div>
    `;
    
    container.appendChild(dayElement);
    console.log('🏗️ Day rendered:', dayId);
}

function renderActivities(dayId) {
    const dayData = itineraryData[dayId];
    if (!dayData || !dayData.activities) {
        console.warn('⚠️ No activities data for:', dayId);
        return '';
    }
    
    return dayData.activities.map((activity, index) => {
        return `
            <div class="activity-item" data-activity-index="${index}">
                <div class="activity-header">
                    <input type="text" 
                           class="time-input" 
                           value="${activity.time || ''}" 
                           placeholder="09:00"
                           onchange="updateActivity('${dayId}', ${index}, 'time', this.value)"
                           title="Waktu aktivitas">
                    <textarea class="activity-description" 
                              placeholder="Deskripsi aktivitas..."
                              onchange="updateActivity('${dayId}', ${index}, 'description', this.value)"
                              title="Deskripsi aktivitas">${activity.description || ''}</textarea>
                    <div class="activity-controls">
                        <button type="button" 
                                class="btn-activity-control ${index === 0 ? 'disabled' : ''}" 
                                onclick="moveActivity('${dayId}', ${index}, 'up')" 
                                ${index === 0 ? 'disabled' : ''}
                                title="Pindah ke atas">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button type="button" 
                                class="btn-activity-control ${index === dayData.activities.length - 1 ? 'disabled' : ''}" 
                                onclick="moveActivity('${dayId}', ${index}, 'down')" 
                                ${index === dayData.activities.length - 1 ? 'disabled' : ''}
                                title="Pindah ke bawah">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" 
                                class="btn-activity-control delete ${dayData.activities.length <= 1 ? 'disabled' : ''}" 
                                onclick="deleteActivity('${dayId}', ${index})" 
                                ${dayData.activities.length <= 1 ? 'disabled' : ''}
                                title="Hapus aktivitas">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function updateDayTitle(dayId, newTitle) {
    if (!itineraryData[dayId]) {
        console.error('❌ Day not found:', dayId);
        return;
    }
    
    itineraryData[dayId].title = newTitle.trim() || `Hari ${dayId.split('-')[1]}`;
    updateItineraryPreview();
    console.log('📝 Updated day title:', dayId, itineraryData[dayId].title);
}

function addActivity(dayId) {
    if (!itineraryData[dayId]) {
        console.error('❌ Day not found:', dayId);
        return;
    }
    
    itineraryData[dayId].activities.push({
        time: '',
        description: ''
    });
    
    rerenderDayActivities(dayId);
    updateItineraryPreview();
    console.log('➕ Added activity to:', dayId);
}

function updateActivity(dayId, activityIndex, field, value) {
    if (!itineraryData[dayId] || !itineraryData[dayId].activities[activityIndex]) {
        console.error('❌ Activity not found:', dayId, activityIndex);
        return;
    }
    
    itineraryData[dayId].activities[activityIndex][field] = value;
    updateItineraryPreview();
    console.log('📝 Updated activity:', dayId, activityIndex, field, value);
}

function deleteActivity(dayId, activityIndex) {
    if (!itineraryData[dayId]) {
        console.error('❌ Day not found:', dayId);
        return;
    }
    
    if (itineraryData[dayId].activities.length <= 1) {
        alert('⚠️ Setiap hari harus memiliki minimal 1 aktivitas');
        return;
    }
    
    if (confirm('🗑️ Hapus aktivitas ini?')) {
        itineraryData[dayId].activities.splice(activityIndex, 1);
        rerenderDayActivities(dayId);
        updateItineraryPreview();
        console.log('🗑️ Deleted activity:', dayId, activityIndex);
    }
}

function moveActivity(dayId, activityIndex, direction) {
    if (!itineraryData[dayId]) {
        console.error('❌ Day not found:', dayId);
        return;
    }
    
    const activities = itineraryData[dayId].activities;
    const newIndex = direction === 'up' ? activityIndex - 1 : activityIndex + 1;
    
    if (newIndex < 0 || newIndex >= activities.length) {
        console.warn('⚠️ Cannot move activity beyond bounds');
        return;
    }
    
    // Swap activities
    [activities[activityIndex], activities[newIndex]] = [activities[newIndex], activities[activityIndex]];
    
    rerenderDayActivities(dayId);
    updateItineraryPreview();
    console.log('↕️ Moved activity:', dayId, activityIndex, direction);
}

function deleteDayPrompt(dayId) {
    const dayCount = Object.keys(itineraryData).length;
    
    if (dayCount <= 1) {
        alert('⚠️ Harus ada minimal 1 hari dalam itinerary');
        return;
    }
    
    const dayTitle = itineraryData[dayId]?.title || dayId;
    if (confirm(`🗑️ Hapus "${dayTitle}" beserta semua aktivitasnya?`)) {
        deleteDay(dayId);
    }
}

function deleteDay(dayId) {
    if (!itineraryData[dayId]) {
        console.error('❌ Day not found:', dayId);
        return;
    }
    
    delete itineraryData[dayId];
    
    const dayElement = document.getElementById(dayId);
    if (dayElement) {
        dayElement.remove();
    }
    
    updateItineraryPreview();
    console.log('🗑️ Deleted day:', dayId);
}

function toggleDayActivities(dayId) {
    const activitiesEl = document.getElementById(`${dayId}-activities`);
    const toggleIcon = document.querySelector(`#${dayId} .day-toggle i`);
    
    if (!activitiesEl || !toggleIcon) {
        console.error('❌ Toggle elements not found for:', dayId);
        return;
    }
    
    if (activitiesEl.style.display === 'none') {
        activitiesEl.style.display = 'block';
        toggleIcon.className = 'fas fa-chevron-down';
    } else {
        activitiesEl.style.display = 'none';
        toggleIcon.className = 'fas fa-chevron-right';
    }
}

function rerenderDayActivities(dayId) {
    const container = document.getElementById(`${dayId}-activities-container`);
    if (!container) {
        console.error('❌ Activities container not found for:', dayId);
        return;
    }
    
    container.innerHTML = renderActivities(dayId);
    console.log('🔄 Re-rendered activities for:', dayId);
}

function updateItineraryPreview() {
    const preview = document.getElementById('itinerary-preview');
    const hiddenInput = document.getElementById('itinerary-data');
    
    if (!preview) {
        console.warn('⚠️ Preview element not found');
        return;
    }
    
    let previewText = '';
    const dayIds = Object.keys(itineraryData).sort();
    
    dayIds.forEach(dayId => {
        const day = itineraryData[dayId];
        if (!day) return;
        
        previewText += `${day.title}:\n`;
        
        day.activities.forEach(activity => {
            if (activity.description && activity.description.trim()) {
                const time = activity.time || '--:--';
                previewText += `  ${time} - ${activity.description.trim()}\n`;
            }
        });
        
        previewText += '\n';
    });
    
    preview.textContent = previewText.trim() || 'Belum ada itinerary';
    
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(itineraryData);
    }
    
    console.log('🔄 Updated itinerary preview');
}

// Debug function
function debugItinerary() {
    console.log('🔍 === ITINERARY DEBUG ===');
    console.log('Day counter:', dayCounter);
    console.log('Itinerary data:', itineraryData);
    console.log('Preview element:', document.getElementById('itinerary-preview'));
    console.log('Hidden input:', document.getElementById('itinerary-data'));
    console.log('Days container:', document.getElementById('itinerary-days'));
}

// Global functions
window.initializeItinerary = initializeItinerary;
window.addNewDay = addNewDay;
window.updateDayTitle = updateDayTitle;
window.addActivity = addActivity;
window.updateActivity = updateActivity;
window.deleteActivity = deleteActivity;
window.moveActivity = moveActivity;
window.deleteDayPrompt = deleteDayPrompt;
window.deleteDay = deleteDay;
window.toggleDayActivities = toggleDayActivities;
window.debugItinerary = debugItinerary;

// Global functions for onclick events
window.previewForm = previewForm;
window.closePreviewModal = closePreviewModal;
window.submitFormFromPreview = submitFormFromPreview;
window.validateForm = validateForm;
window.showFieldError = showFieldError;
window.clearFieldError = clearFieldError;

// Tambahkan ke admin.php script section

// Mobile menu functionality
function toggleMobileMenu() {
    const sidebar = document.getElementById('mobileSidebar');
    const overlay = document.getElementById('mobileOverlay');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (!sidebar || !overlay || !toggle) return;
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
    toggle.classList.toggle('active');
    
    // Prevent body scroll when menu is open
    if (sidebar.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Smooth scroll to section
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        const headerHeight = document.querySelector('.admin-header').offsetHeight;
        const targetPosition = element.offsetTop - headerHeight - 20;
        
        window.scrollTo({
            top: targetPosition,
            behavior: 'smooth'
        });
    }
}

// Close mobile menu on resize
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        const sidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('mobileOverlay');
        const toggle = document.querySelector('.mobile-menu-toggle');
        
        if (sidebar && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            toggle.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// Close mobile menu on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('mobileSidebar');
        if (sidebar && sidebar.classList.contains('active')) {
            toggleMobileMenu();
        }
    }
});

// Enhanced form validation for mobile
function validateFormMobile() {
    const isValid = validateForm(document.querySelector('form[action="tambah.php"]'));
    
    if (!isValid) {
        // Scroll to first error on mobile
        const firstError = document.querySelector('.field-error');
        if (firstError) {
            const headerHeight = document.querySelector('.admin-header').offsetHeight;
            const targetPosition = firstError.offsetTop - headerHeight - 20;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    }
    
    return isValid;
}

// Update form submission for mobile
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[action="tambah.php"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateFormMobile()) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<!-- Tambahkan tombol debug di bawah header -->
<div style="position: fixed; top: 100px; right: 20px; z-index: 999;">
    <button onclick="window.debugGallery()" style="background: #e74c3c; color: white; padding: 8px 12px; border: none; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">
        🔍 Debug Gallery
    </button>
</div>
</body>
</html>
