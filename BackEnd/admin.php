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

        /* Itinerary Builder Styles */
        .itinerary-builder {
            border: 2px solid #e8ecef;
            border-radius: 15px;
            padding: 20px;
            background: #f8f9fa;
        }

        .itinerary-controls {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
        }

        .add-day-btn, .reset-itinerary-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-day-btn:hover {
            background: linear-gradient(45deg, #20c997, #28a745);
            transform: translateY(-2px);
        }

        .reset-itinerary-btn {
            background: linear-gradient(45deg, #6c757d, #495057);
        }

        .reset-itinerary-btn:hover {
            background: linear-gradient(45deg, #495057, #6c757d);
        }

        .day-counter {
            margin-left: auto;
            font-weight: 600;
            color: #2980b9;
        }

        .itinerary-days {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .day-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .day-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .day-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .remove-day-btn {
            background: rgba(231, 76, 60, 0.8);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .remove-day-btn:hover {
            background: #e74c3c;
            transform: scale(1.1);
        }

        .day-content {
            padding: 20px;
        }

        .activities-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }

        .time-selector {
            min-width: 140px;
        }

        .time-selector select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background: white;
            font-size: 0.9rem;
        }

        .activity-input {
            flex: 1;
        }

        .activity-input input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .activity-controls {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .add-activity-btn, .remove-activity-btn {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .add-activity-btn {
            background: #28a745;
            color: white;
        }

        .add-activity-btn:hover {
            background: #20c997;
            transform: scale(1.1);
        }

        .remove-activity-btn {
            background: #dc3545;
            color: white;
        }

        .remove-activity-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        .add-activity-to-day {
            margin-top: 15px;
            padding: 10px;
            background: #e3f2fd;
            border: 1px dashed #3498db;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #2980b9;
            font-weight: 600;
        }

        .add-activity-to-day:hover {
            background: #bbdefb;
            border-color: #2980b9;
        }

        /* Enhanced Form Styling - User Friendly */
.form-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    position: relative;
}

.subsection-title {
    font-family: 'Lora', serif;
    font-size: 1.3rem;
    color: #2c3e50;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #3498db;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-description {
    color: #666;
    font-style: italic;
    margin-bottom: 20px;
    padding: 10px;
    background: #e3f2fd;
    border-left: 4px solid #3498db;
    border-radius: 4px;
}

.form-grid-two {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.required {
    color: #e74c3c;
    font-weight: bold;
}

.form-help {
    color: #666;
    font-size: 0.85rem;
    margin-top: 5px;
    line-height: 1.4;
}

.price-input-wrapper {
    display: flex;
    align-items: center;
    border: 2px solid #e8ecef;
    border-radius: 12px;
    background: #f8f9fa;
    overflow: hidden;
    transition: all 0.3s ease;
}

.price-input-wrapper:focus-within {
    border-color: #3498db;
    background: white;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.currency-symbol {
    background: #3498db;
    color: white;
    padding: 15px;
    font-weight: 600;
}

.price-input-wrapper input {
    border: none;
    padding: 15px;
    flex: 1;
    background: transparent;
    font-size: 1rem;
}

.price-input-wrapper input:focus {
    outline: none;
}

.price-suffix {
    padding: 15px;
    color: #666;
    font-style: italic;
}

/* File Upload Area */
.file-upload-area {
    border: 3px dashed #3498db;
    border-radius: 15px;
    padding: 40px 20px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.file-upload-area:hover {
    background: #e3f2fd;
    border-color: #2980b9;
}

.file-upload-area.dragover {
    background: #e3f2fd;
    border-color: #2980b9;
    transform: scale(1.02);
}

.file-upload-area input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.upload-content h4 {
    margin: 10px 0;
    color: #2c3e50;
}

.upload-content p {
    color: #666;
    margin-bottom: 15px;
}

.upload-icon {
    font-size: 3rem;
    color: #3498db;
    margin-bottom: 15px;
}

.upload-requirements {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.req-item {
    background: #e8f5e8;
    color: #27ae60;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.photo-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.photo-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    background: #f8f9fa;
}

.photo-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.photo-remove {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.photo-info {
    padding: 8px;
    font-size: 0.8rem;
    color: #666;
    text-align: center;
}

/* Itinerary Builder */
.itinerary-builder {
    background: white;
    border-radius: 15px;
    padding: 20px;
    border: 1px solid #e9ecef;
}

.itinerary-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.btn-add-day, .btn-reset {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-add-day {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
}

.btn-add-day:hover {
    background: linear-gradient(45deg, #20c997, #28a745);
    transform: translateY(-2px);
}

.btn-reset {
    background: #6c757d;
    color: white;
}

.btn-reset:hover {
    background: #5a6268;
}

.day-counter {
    margin-left: auto;
    font-weight: 600;
    color: #2980b9;
    background: #e3f2fd;
    padding: 8px 15px;
    border-radius: 20px;
}

.day-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.day-card-header {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.day-title {
    font-weight: 600;
    font-size: 1.1rem;
}

.btn-remove-day {
    background: rgba(231, 76, 60, 0.8);
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.day-content {
    padding: 20px;
}

.activity-item {
    display: flex;
    gap: 15px;
    align-items: flex-start;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid #3498db;
}

.time-input {
    min-width: 140px;
}

.time-input select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: white;
}

.activity-input {
    flex: 1;
}

.activity-input input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.activity-actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.btn-activity {
    width: 30px;
    height: 30px;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.btn-add-activity {
    background: #28a745;
    color: white;
}

.btn-remove-activity {
    background: #dc3545;
    color: white;
}

.add-activity-btn {
    margin-top: 10px;
    padding: 10px;
    background: #e3f2fd;
    border: 1px dashed #3498db;
    border-radius: 8px;
    text-align: center;
    cursor: pointer;
    color: #2980b9;
    font-weight: 600;
    transition: all 0.3s ease;
}

.add-activity-btn:hover {
    background: #bbdefb;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.btn-preview, .btn-submit {
    padding: 15px 30px;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 180px;
    justify-content: center;
}

.btn-preview {
    background: linear-gradient(45deg, #17a2b8, #138496);
    color: white;
}

.btn-preview:hover {
    background: linear-gradient(45deg, #138496, #17a2b8);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(23, 162, 184, 0.3);
}

.btn-submit {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
}

.btn-submit:hover {
    background: linear-gradient(45deg, #20c997, #28a745);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-grid-two {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-preview, .btn-submit {
        min-width: auto;
    }
    
    .upload-requirements {
        flex-direction: column;
        align-items: center;
    }
    
    .itinerary-controls {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .activity-item {
        flex-direction: column;
        gap: 10px;
    }
    
    .activity-actions {
        flex-direction: row;
        align-self: center;
    }
}

/* Character Counter */
#desc-counter {
    font-weight: 600;
    color: #3498db;
}

/* Loading States */
.uploading {
    opacity: 0.7;
    pointer-events: none;
}

.upload-progress {
    width: 100%;
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 10px;
}

.upload-progress-bar {
    height: 100%;
    background: linear-gradient(45deg, #3498db, #2980b9);
    width: 0%;
    transition: width 0.3s ease;
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
                                <i class="fas fa-tag"></i> Nama Paket Wisata
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="nama" 
                                   name="nama" 
                                   required 
                                   maxlength="255"
                                   placeholder="Contoh: Wisata Candi Borobudur 2 Hari 1 Malam">
                            <small class="form-help">Berikan nama yang menarik dan jelas untuk paket wisata</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration">
                                <i class="fas fa-clock"></i> Durasi Perjalanan
                                <span class="required">*</span>
                            </label>
                            <select id="duration" name="duration" required onchange="updateItineraryDays()">
                                <option value="">-- Pilih Durasi --</option>
                                <option value="1D">1 Hari (Tidak menginap)</option>
                                <option value="2D1N" selected>2 Hari 1 Malam</option>
                                <option value="3D2N">3 Hari 2 Malam</option>
                                <option value="4D3N">4 Hari 3 Malam</option>
                                <option value="5D4N">5 Hari 4 Malam</option>
                                <option value="custom">Durasi Lainnya</option>
                            </select>
                            <small class="form-help">Pilih berapa lama paket wisata ini berlangsung</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">
                            <i class="fas fa-edit"></i> Deskripsi Paket
                            <span class="required">*</span>
                        </label>
                        <textarea id="deskripsi" 
                                  name="deskripsi" 
                                  rows="4" 
                                  required 
                                  maxlength="1000"
                                  placeholder="Jelaskan secara singkat tentang paket wisata ini. Misalnya: Nikmati keindahan candi bersejarah dan budaya Jawa yang autentik dalam perjalanan 2 hari yang tak terlupakan..."></textarea>
                        <small class="form-help">
                            <span id="desc-counter">0</span>/1000 karakter. 
                            Ceritakan pengalaman menarik yang akan didapat wisatawan.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">
                            <i class="fas fa-money-bill-wave"></i> Harga per Orang (Rupiah)
                        </label>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">Rp</span>
                            <input type="number" 
                                   id="price" 
                                   name="price" 
                                   min="0" 
                                   step="1000"
                                   placeholder="350000">
                            <span class="price-suffix">per orang</span>
                        </div>
                        <small class="form-help">
                            Masukkan harga tanpa titik atau koma. Contoh: 350000 untuk Rp 350.000
                            <br>Kosongkan jika harga akan ditentukan setelah konsultasi
                        </small>
                    </div>
                </div>

                <!-- Upload Foto -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-camera"></i> Foto-foto Paket Wisata
                    </h3>
                    
                    <div class="form-group">
                        <label for="fotos">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Foto Wisata
                            <span class="required">*</span>
                        </label>
                        <div class="file-upload-area" id="file-upload-area">
                            <input type="file" 
                                   id="fotos" 
                                   name="fotos[]" 
                                   multiple 
                                   accept="image/*" 
                                   required>
                            <div class="upload-content">
                                <i class="fas fa-cloud-upload-alt upload-icon"></i>
                                <h4>Klik atau Seret Foto ke Sini</h4>
                                <p>Upload 3-6 foto terbaik dari destinasi wisata</p>
                                <div class="upload-requirements">
                                    <span class="req-item"><i class="fas fa-check"></i> Format: JPG, PNG</span>
                                    <span class="req-item"><i class="fas fa-check"></i> Ukuran maksimal: 5MB per foto</span>
                                    <span class="req-item"><i class="fas fa-check"></i> Minimal 3 foto, maksimal 6 foto</span>
                                </div>
                            </div>
                        </div>
                        <div id="photo-preview" class="photo-preview"></div>
                        <small class="form-help">
                            Tips: Gunakan foto berkualitas tinggi yang menunjukkan keindahan destinasi. 
                            Foto pertama akan menjadi gambar utama.
                        </small>
                    </div>
                </div>

                <!-- Jadwal Perjalanan -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-map-marked-alt"></i> Jadwal Perjalanan (Itinerary)
                    </h3>
                    <p class="section-description">
                        Buat jadwal harian untuk paket wisata. Anda bisa menambah atau mengurangi hari sesuai kebutuhan.
                    </p>
                    
                    <div class="itinerary-builder">
                        <div class="itinerary-controls">
                            <button type="button" class="btn-add-day" onclick="addItineraryDay()">
                                <i class="fas fa-plus"></i> Tambah Hari
                            </button>
                            <button type="button" class="btn-reset" onclick="resetItinerary()">
                                <i class="fas fa-refresh"></i> Reset Semua
                            </button>
                            <div class="day-counter">
                                Total: <span id="day-count">0</span> hari
                            </div>
                        </div>
                        
                        <div id="itinerary-days" class="itinerary-days">
                            <!-- Hari akan ditambahkan secara dinamis -->
                        </div>
                        
                        <input type="hidden" id="itinerary" name="itinerary">
                    </div>
                </div>

                <!-- Daya Tarik Utama -->
                <div class="form-section">
                    <h3 class="subsection-title">
                        <i class="fas fa-star"></i> Daya Tarik Utama
                    </h3>
                    
                    <div class="form-group">
                        <label for="highlights">
                            <i class="fas fa-heart"></i> Highlight Wisata
                        </label>
                        <textarea id="highlights" 
                                  name="highlights" 
                                  rows="4" 
                                  placeholder="Tuliskan daya tarik utama paket ini, pisahkan dengan tanda | (garis tegak)

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
                                <i class="fas fa-check-circle" style="color: #27ae60;"></i> 
                                Yang Sudah Termasuk dalam Paket
                            </label>
                            <textarea id="inclusions" 
                                      name="inclusions" 
                                      rows="6" 
                                      placeholder="Tuliskan apa saja yang sudah termasuk, pisahkan dengan tanda |

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
                                <a href="../FrontEnd/html/package_detail.html?id=<?= $row['id'] ?>" class="view-btn">
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
            // Enhanced JavaScript untuk form yang lebih user-friendly
let dayCounter = 0;
let itineraryData = {};
let uploadedFiles = [];

// Time periods dalam Bahasa Indonesia
const timePeriods = [
    { value: 'Pagi', label: '🌅 Pagi (06:00 - 10:00)' },
    { value: 'Siang', label: '☀️ Siang (10:00 - 14:00)' },
    { value: 'Sore', label: '🌇 Sore (14:00 - 18:00)' },
    { value: 'Malam', label: '🌙 Malam (18:00 - 22:00)' },
    { value: '06:00', label: '06:00 - Subuh' },
    { value: '07:00', label: '07:00 - Pagi' },
    { value: '08:00', label: '08:00 - Pagi' },
    { value: '09:00', label: '09:00 - Pagi' },
    { value: '10:00', label: '10:00 - Pagi' },
    { value: '11:00', label: '11:00 - Siang' },
    { value: '12:00', label: '12:00 - Siang' },
    { value: '13:00', label: '13:00 - Siang' },
    { value: '14:00', label: '14:00 - Sore' },
    { value: '15:00', label: '15:00 - Sore' },
    { value: '16:00', label: '16:00 - Sore' },
    { value: '17:00', label: '17:00 - Sore' },
    { value: '18:00', label: '18:00 - Malam' },
    { value: '19:00', label: '19:00 - Malam' },
    { value: '20:00', label: '20:00 - Malam' },
    { value: 'Sarapan', label: '🍳 Sarapan' },
    { value: 'Makan Siang', label: '🍽️ Makan Siang' },
    { value: 'Makan Malam', label: '🍽️ Makan Malam' },
    { value: 'Check-in', label: '🏨 Check-in Hotel' },
    { value: 'Check-out', label: '🏨 Check-out Hotel' }
];

// Initialize form
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    setupFileUpload();
    updateItineraryDays();
});

function initializeForm() {
    // Character counter untuk deskripsi
    const descTextarea = document.getElementById('deskripsi');
    const descCounter = document.getElementById('desc-counter');
    
    descTextarea.addEventListener('input', function() {
        const currentLength = this.value.length;
        descCounter.textContent = currentLength;
        
        if (currentLength > 800) {
            descCounter.style.color = '#e74c3c';
        } else if (currentLength > 600) {
            descCounter.style.color = '#f39c12';
        } else {
            descCounter.style.color = '#3498db';
        }
    });

    // Format harga otomatis
    const priceInput = document.getElementById('price');
    priceInput.addEventListener('input', function() {
        // Remove non-numeric characters
        let value = this.value.replace(/[^\d]/g, '');
        
        // Add thousands separator for display
        if (value) {
            const formatted = parseInt(value).toLocaleString('id-ID');
            // Don't change the actual input value, just show formatted in placeholder
        }
    });
}

function setupFileUpload() {
    const fileInput = document.getElementById('fotos');
    const uploadArea = document.getElementById('file-upload-area');
    const preview = document.getElementById('photo-preview');
    
    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        handleFiles(files);
    });
    
    // File input change
    fileInput.addEventListener('change', function(e) {
        handleFiles(this.files);
    });
    
    function handleFiles(files) {
        if (files.length < 3 || files.length > 6) {
            alert('Silakan pilih 3-6 foto');
            return;
        }
        
        preview.innerHTML = '';
        uploadedFiles = [];
        
        Array.from(files).forEach((file, index) => {
            if (!file.type.startsWith('image/')) {
                alert(`File ${file.name} bukan foto yang valid`);
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert(`Foto ${file.name} terlalu besar (maksimal 5MB)`);
                return;
            }
            
            uploadedFiles.push(file);
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const photoItem = document.createElement('div');
                photoItem.className = 'photo-item';
                photoItem.innerHTML = `
                    <img src="${e.target.result}" alt="Foto ${index + 1}">
                    <button type="button" class="photo-remove" onclick="removePhoto(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="photo-info">
                        ${index === 0 ? 'Foto Utama' : `Foto ${index + 1}`}
                        <br><small>${(file.size / 1024).toFixed(1)} KB</small>
                    </div>
                `;
                preview.appendChild(photoItem);
            };
            reader.readAsDataURL(file);
        });
    }
}

function removePhoto(index) {
    uploadedFiles.splice(index, 1);
    
    // Update file input
    const dataTransfer = new DataTransfer();
    uploadedFiles.forEach(file => dataTransfer.items.add(file));
    document.getElementById('fotos').files = dataTransfer.files;
    
    // Refresh preview
    const event = new Event('change');
    document.getElementById('fotos').dispatchEvent(event);
}

function updateItineraryDays() {
    const duration = document.getElementById('duration').value;
    
    if (duration === 'custom') {
        const customDays = prompt('Berapa hari durasi paket wisata ini?', '3');
        if (customDays && !isNaN(customDays) && customDays > 0) {
            initializeItinerary(parseInt(customDays));
        }
        return;
    }
    
    const days = parseInt(duration.match(/\d+/)?.[0] || 2);
    initializeItinerary(days);
}

function initializeItinerary(days) {
    resetItinerary();
    
    for (let i = 1; i <= days; i++) {
        addItineraryDay(i);
    }
    
    updateDayCounter();
}

function addItineraryDay(dayNumber = null) {
    dayCounter++;
    if (!dayNumber) dayNumber = dayCounter;
    
    const dayId = `day-${dayNumber}`;
    itineraryData[dayId] = {
        title: `Hari ${dayNumber}`,
        activities: [
            { time: 'Pagi', description: '' }
        ]
    };
    
    const dayCard = createDayCard(dayId, dayNumber);
    document.getElementById('itinerary-days').appendChild(dayCard);
    
    updateDayCounter();
    saveItineraryData();
}

function createDayCard(dayId, dayNumber) {
    const dayCard = document.createElement('div');
    dayCard.className = 'day-card';
    dayCard.setAttribute('data-day-id', dayId);
    
    dayCard.innerHTML = `
        <div class="day-card-header">
            <div class="day-title">Hari ${dayNumber}</div>
            <button type="button" class="btn-remove-day" onclick="removeDayCard('${dayId}')" 
                    ${dayNumber <= 1 ? 'style="display: none;"' : ''}>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="day-content">
            <div class="activities-list" id="${dayId}-activities">
                ${createActivityHTML(dayId, 0)}
            </div>
            <div class="add-activity-btn" onclick="addActivityToDay('${dayId}')">
                <i class="fas fa-plus"></i> Tambah Aktivitas
            </div>
        </div>
    `;
    
    return dayCard;
}

function createActivityHTML(dayId, activityIndex) {
    const activity = itineraryData[dayId].activities[activityIndex];
    
    return `
        <div class="activity-item" data-activity-index="${activityIndex}">
            <div class="time-input">
                <select onchange="updateActivityTime('${dayId}', ${activityIndex}, this.value)">
                    ${timePeriods.map(period => 
                        `<option value="${period.value}" ${activity.time === period.value ? 'selected' : ''}>${period.label}</option>`
                    ).join('')}
                </select>
            </div>
            <div class="activity-input">
                <input type="text" 
                       placeholder="Jelaskan aktivitas. Contoh: Kunjungi Candi Borobudur dan nikmati sunrise yang memukau" 
                       value="${activity.description}"
                       onchange="updateActivityDescription('${dayId}', ${activityIndex}, this.value)">
            </div>
            <div class="activity-actions">
                <button type="button" class="btn-activity btn-add-activity" 
                        onclick="addActivityToDay('${dayId}', ${activityIndex + 1})" 
                        title="Tambah aktivitas">
                    <i class="fas fa-plus"></i>
                </button>
                ${itineraryData[dayId].activities.length > 1 ? `
                    <button type="button" class="btn-activity btn-remove-activity" 
                            onclick="removeActivity('${dayId}', ${activityIndex})" 
                            title="Hapus aktivitas">
                        <i class="fas fa-minus"></i>
                    </button>
                ` : ''}
            </div>
        </div>
    `;
}

// Fungsi-fungsi lainnya tetap sama, tapi dengan pesan Bahasa Indonesia
function addActivityToDay(dayId, insertIndex = null) {
    if (insertIndex === null) {
        insertIndex = itineraryData[dayId].activities.length;
    }
    
    itineraryData[dayId].activities.splice(insertIndex, 0, {
        time: 'Pagi',
        description: ''
    });
    
    refreshDayActivities(dayId);
    saveItineraryData();
}

function removeActivity(dayId, activityIndex) {
    if (itineraryData[dayId].activities.length > 1) {
        itineraryData[dayId].activities.splice(activityIndex, 1);
        refreshDayActivities(dayId);
        saveItineraryData();
    } else {
        alert('Setiap hari harus memiliki minimal 1 aktivitas');
    }
}

function removeDayCard(dayId) {
    if (confirm('Hapus hari ini beserta semua aktivitasnya?')) {
        delete itineraryData[dayId];
        document.querySelector(`[data-day-id="${dayId}"]`).remove();
        updateDayCounter();
        saveItineraryData();
    }
}

function refreshDayActivities(dayId) {
    const activitiesContainer = document.getElementById(`${dayId}-activities`);
    activitiesContainer.innerHTML = '';
    
    itineraryData[dayId].activities.forEach((_, index) => {
        activitiesContainer.innerHTML += createActivityHTML(dayId, index);
    });
}

function updateActivityTime(dayId, activityIndex, time) {
    itineraryData[dayId].activities[activityIndex].time = time;
    saveItineraryData();
}

function updateActivityDescription(dayId, activityIndex, description) {
    itineraryData[dayId].activities[activityIndex].description = description;
    saveItineraryData();
}

function resetItinerary() {
    if (Object.keys(itineraryData).length > 0) {
        if (!confirm('Hapus semua jadwal yang sudah dibuat?')) {
            return;
        }
    }
    
    dayCounter = 0;
    itineraryData = {};
    document.getElementById('itinerary-days').innerHTML = '';
    updateDayCounter();
}

function updateDayCounter() {
    const dayCount = Object.keys(itineraryData).length;
    document.getElementById('day-count').textContent = dayCount;
}

function saveItineraryData() {
    const formattedData = {};
    
    Object.keys(itineraryData).forEach(dayId => {
        const day = itineraryData[dayId];
        formattedData[dayId] = {
            title: day.title,
            activities: day.activities.map(activity => ({
                time: activity.time,
                description: activity.description
            }))
        };
    });
    
    document.getElementById('itinerary').value = JSON.stringify(formattedData);
}

function previewPackage() {
    const formData = new FormData(document.querySelector('form'));
    
    // Basic validation
    if (!formData.get('nama')) {
        alert('Nama paket wisata harus diisi');
        document.getElementById('nama').focus();
        return;
    }
    
    if (!formData.get('deskripsi')) {
        alert('Deskripsi paket harus diisi');
        document.getElementById('deskripsi').focus();
        return;
    }
    
    if (!formData.get('fotos[]') || formData.getAll('fotos[]').length < 3) {
        alert('Minimal 3 foto harus diupload');
        document.getElementById('fotos').focus();
        return;
    }
    
    // Show preview modal or new window
    showPreviewModal(formData);
}

function showPreviewModal(formData) {
    const nama = formData.get('nama');
    const deskripsi = formData.get('deskripsi');
    const duration = formData.get('duration');
    const price = formData.get('price');
    
    const modal = document.createElement('div');
    modal.className = 'preview-modal';
    modal.innerHTML = `
        <div class="preview-content">
            <div class="preview-header">
                <h2>Preview Paket Wisata</h2>
                <button onclick="this.closest('.preview-modal').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="preview-body">
                <h3>${nama}</h3>
                <p><strong>Durasi:</strong> ${duration}</p>
                <p><strong>Harga:</strong> ${price ? 'Rp ' + parseInt(price).toLocaleString('id-ID') : 'Hubungi untuk harga'}</p>
                <p><strong>Deskripsi:</strong> ${deskripsi}</p>
                <p><strong>Foto:</strong> ${formData.getAll('fotos[]').length} foto</p>
            </div>
            <div class="preview-footer">
                <button onclick="this.closest('.preview-modal').remove()">Tutup</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}
        </script>
    </div>

    <?php
    $koneksi->close();
    ?>
</body>
</html>
