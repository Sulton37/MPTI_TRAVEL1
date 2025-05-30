<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: loginadmin.php?error=not_logged_in");
    exit;
}

$koneksi = new mysqli("localhost", "root", "", "paket_travel");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    // Validate input length
    if (strlen($nama) > 255 || strlen($deskripsi) > 1000) {
        header("Location: admin.php?error=input_too_long");
        exit;
    }
    
    if (!empty($nama) && !empty($deskripsi)) {
        
        // Validate files
        if (!isset($_FILES['fotos']) || empty($_FILES['fotos']['name'][0])) {
            header("Location: admin.php?error=no_files");
            exit;
        }
        
        $photos = $_FILES['fotos'];
        $photoCount = count($photos['name']);
        
        // Validate photo count
        if ($photoCount < 3 || $photoCount > 6) {
            header("Location: admin.php?error=invalid_photo_count");
            exit;
        }
        
        $uploadedFiles = [];
        $uploadDir = 'uploads/';
        
        // Create uploads directory if not exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Process each file
        for ($i = 0; $i < $photoCount; $i++) {
            $fileName = $photos['name'][$i];
            $fileTmpName = $photos['tmp_name'][$i];
            $fileSize = $photos['size'][$i];
            $fileError = $photos['error'][$i];
            $fileType = $photos['type'][$i];
            
            // Skip empty files
            if (empty($fileName)) continue;
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($fileType, $allowedTypes)) {
                header("Location: admin.php?error=invalid_file_type");
                exit;
            }
            
            // Validate file size (5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                header("Location: admin.php?error=file_too_large");
                exit;
            }
            
            // Generate unique filename
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = time() . '_' . uniqid() . '_' . ($i + 1) . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                $uploadedFiles[] = $newFileName;
            } else {
                // Clean up already uploaded files on error
                foreach ($uploadedFiles as $uploadedFile) {
                    unlink($uploadDir . $uploadedFile);
                }
                header("Location: admin.php?error=upload_failed");
                exit;
            }
        }
        
        // Save to database
        
        
        // Convert uploaded files array to JSON
        $fotosJson = json_encode($uploadedFiles);
        
        $stmt = $koneksi->prepare("INSERT INTO paket (nama, deskripsi, fotos) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $deskripsi, $fotosJson);
        
        if ($stmt->execute()) {
            $stmt->close();
            $koneksi->close();
            header("Location: admin.php?success=1");
            exit;
        } else {
            // Clean up uploaded files on database error
            foreach ($uploadedFiles as $uploadedFile) {
                unlink($uploadDir . $uploadedFile);
            }
            $stmt->close();
            $koneksi->close();
            header("Location: admin.php?error=database_error");
            exit;
        }
    } else {
        header("Location: admin.php?error=empty_fields");
        exit;
    }
} else {
    header("Location: admin.php");
    exit;
}

// Handle delete functionality
if (isset($_GET['hapus'])) {
    $koneksi = new mysqli("localhost", "root", "", "paket_travel");
    $id = intval($_GET['hapus']);
    
    $stmt = $koneksi->prepare("SELECT fotos FROM paket WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Decode JSON array of photos
        $fotosArray = json_decode($row['fotos'], true);
        if ($fotosArray) {
            // Delete all photos
            foreach ($fotosArray as $foto) {
                $fotoPath = "uploads/" . $foto;
                if (file_exists($fotoPath)) {
                    unlink($fotoPath);
                }
            }
        }
        
        $deleteStmt = $koneksi->prepare("DELETE FROM paket WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        $deleteStmt->close();
    }
    
    $stmt->close();
    $koneksi->close();
    header("Location: admin.php");
    exit;
}
?>
