<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: loginadmin.php?error=not_logged_in");
    exit;
}

// Koneksi database dengan error handling
$koneksi = new mysqli("localhost", "root", "", "paket_travel");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Tampilkan pesan berdasarkan parameter URL
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(-45deg, #f8f9fa, #e9ecef, #dee2e6, #ced4da);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            line-height: 1.6;
            color: #333;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .admin-header {
            background: linear-gradient(-45deg, #3498db, #2980b9, #8dc6ff, #01f6c5);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-section img {
            height: 50px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .logo-section h1 {
            font-family: 'Lora', serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 1rem;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .admin-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .section-title {
            font-family: 'Lora', serif;
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #3498db;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .form-group input,
        .form-group textarea {
            padding: 15px;
            border: 2px solid #e8ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border: 2px dashed #3498db;
            border-radius: 12px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            justify-content: center;
        }

        .file-input-label:hover {
            background: #e3f2fd;
            border-color: #2980b9;
        }

        .submit-btn {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .submit-btn:hover {
            background: linear-gradient(45deg, #2980b9, #3498db);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .package-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .package-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .package-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .package-card:hover .package-image img {
            transform: scale(1.05);
        }

        .package-content {
            padding: 20px;
        }

        .package-title {
            font-family: 'Lora', serif;
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .package-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .package-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .delete-btn {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .delete-btn:hover {
            background: linear-gradient(45deg, #c0392b, #e74c3c);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .view-btn {
            background: linear-gradient(45deg, #3498db, #2980b9);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .view-btn:hover {
            background: linear-gradient(45deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .error {
            background: linear-gradient(45deg, #fee, #fdd);
            color: #c33;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .success {
            background: linear-gradient(45deg, #efe, #dfd);
            color: #2d5;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .no-packages {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-packages i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #3498db;
        }

        .upload-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #3498db;
        }

        .upload-info small {
            color: #1565c0;
            display: block;
            margin-bottom: 5px;
        }

        .photo-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(52, 152, 219, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .no-image-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 200px;
            background: #f8f9fa;
            color: #666;
        }

        .no-image-placeholder i {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .admin-info {
                font-size: 0.9rem;
            }

            .packages-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 20px 15px;
            }

            .admin-section {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .logo-section h1 {
                font-size: 1.4rem;
            }

            .section-title {
                font-size: 1.4rem;
            }

            .package-actions {
                flex-direction: column;
            }
        }
    </style>
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

        <div class="admin-section">
            <h2 class="section-title">
                <i class="fas fa-plus-circle"></i> Tambah Paket Wisata
            </h2>
            
            <form action="tambah.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nama">Nama Paket:</label>
                        <input type="text" id="nama" name="nama" maxlength="255" required placeholder="Masukkan nama paket wisata">
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">Deskripsi:</label>
                        <textarea id="deskripsi" name="deskripsi" rows="4" maxlength="1000" required placeholder="Deskripsikan paket wisata Anda"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fotos">Upload Foto (3-6 foto):</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="fotos" name="fotos[]" multiple accept="image/*" required>
                            <label for="fotos" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Pilih File Foto</span>
                            </label>
                        </div>
                        <div class="upload-info">
                            <small>📸 Upload 3-6 foto untuk paket wisata</small>
                            <small>📏 Format: JPG, JPEG, PNG • Maksimal 5MB per file</small>
                            <small>💡 Foto pertama akan menjadi foto utama</small>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Simpan Paket
                    </button>
                </div>
            </form>
        </div>

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
                            if ($fotosArray && is_array($fotosArray) && count($fotosArray) > 0):
                                $firstPhoto = $fotosArray[0];
                                $photoPath = "uploads/" . $firstPhoto;
                                if (file_exists($photoPath)):
                            ?>
                                <img src="<?= $photoPath ?>" alt="<?= htmlspecialchars($row['nama']) ?>">
                                <div class="photo-badge"><?= count($fotosArray) ?> Foto</div>
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <i class="fas fa-image"></i>
                                    <p>Foto tidak tersedia</p>
                                </div>
                            <?php endif; ?>
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <i class="fas fa-image"></i>
                                    <p>Foto tidak tersedia</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="package-content">
                            <h3 class="package-title"><?= htmlspecialchars($row['nama']) ?></h3>
                            <p class="package-description">
                                <?= htmlspecialchars(strlen($row['deskripsi']) > 150 ? substr($row['deskripsi'], 0, 150) . '...' : $row['deskripsi']) ?>
                            </p>
                            
                            <div class="package-actions">
                                <a href="../FrontEnd/html/Package_1.html?id=<?= $row['id'] ?>" class="view-btn">
                                    <i class="fas fa-eye"></i> Lihat
                                </a>
                                <a href="admin.php?hapus=<?= $row['id'] ?>" class="delete-btn" 
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

        <script>
            // File input enhancement
            document.getElementById('fotos').addEventListener('change', function(e) {
                const files = e.target.files;
                const label = document.querySelector('.file-input-label span');
                
                if (files.length > 0) {
                    if (files.length < 3 || files.length > 6) {
                        alert('Silakan pilih 3-6 foto!');
                        e.target.value = '';
                        label.textContent = 'Pilih File Foto';
                        return;
                    }
                    
                    // Validate file size and type
                    for (let file of files) {
                        if (file.size > 5 * 1024 * 1024) { // 5MB
                            alert('File ' + file.name + ' terlalu besar! Maksimal 5MB.');
                            e.target.value = '';
                            label.textContent = 'Pilih File Foto';
                            return;
                        }
                        
                        if (!file.type.match('image.*')) {
                            alert('File ' + file.name + ' bukan gambar!');
                            e.target.value = '';
                            label.textContent = 'Pilih File Foto';
                            return;
                        }
                    }
                    
                    label.textContent = files.length + ' foto dipilih';
                } else {
                    label.textContent = 'Pilih File Foto';
                }
            });

            // Auto-hide messages
            setTimeout(() => {
                const messages = document.querySelectorAll('.error, .success');
                messages.forEach(message => {
                    message.style.opacity = '0';
                    setTimeout(() => message.remove(), 300);
                });
            }, 5000);
        </script>
    </div>

    <?php
    $koneksi->close();
    ?>
</body>
</html>
