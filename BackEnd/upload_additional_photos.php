<?php
// Matikan semua output dan error reporting yang bisa mengganggu JSON
ini_set('display_errors', 0);
error_reporting(0);

// Start output buffering untuk mencegah output tidak diinginkan
ob_start();

session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$koneksi = new mysqli("localhost", "root", "", "paket_travel");
if ($koneksi->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $koneksi->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['package_id']) || !isset($_FILES['photos'])) {
    echo json_encode(['error' => 'Package ID and photos are required']);
    exit;
}

$package_id = intval($_POST['package_id']);
$captions = $_POST['captions'] ?? [];

// Verify package exists
$stmt = $koneksi->prepare("SELECT id FROM paket WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Package not found']);
    exit;
}
$stmt->close();

// Create upload directory if it doesn't exist
$uploadDir = 'uploads/gallery/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
$maxFileSize = 5 * 1024 * 1024; // 5MB
$uploadedFiles = [];
$errors = [];

// Get current max order
$stmt = $koneksi->prepare("SELECT COALESCE(MAX(photo_order), 0) as max_order FROM package_gallery WHERE package_id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$currentMaxOrder = $row['max_order'];
$stmt->close();

try {
    $koneksi->begin_transaction();
    
    for ($i = 0; $i < count($_FILES['photos']['name']); $i++) {
        if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
            $tempName = $_FILES['photos']['tmp_name'][$i];
            $originalName = $_FILES['photos']['name'][$i];
            $fileSize = $_FILES['photos']['size'][$i];
            $fileType = $_FILES['photos']['type'][$i];
            
            // Validate file type
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Invalid file type for $originalName";
                continue;
            }
            
            // Validate file size
            if ($fileSize > $maxFileSize) {
                $errors[] = "File $originalName is too large";
                continue;
            }
            
            // Generate unique filename
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = 'gallery_' . $package_id . '_' . time() . '_' . uniqid() . '.' . $extension;
            $filePath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($tempName, $filePath)) {
                // Insert into database with correct column name
                $caption = isset($captions[$i]) ? trim($captions[$i]) : '';
                $photoOrder = $currentMaxOrder + $i + 1;
                
                $stmt = $koneksi->prepare("INSERT INTO package_gallery (package_id, photo_filename, caption, photo_order) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $package_id, $filename, $caption, $photoOrder);
                
                if ($stmt->execute()) {
                    $uploadedFiles[] = $filename;
                } else {
                    $errors[] = "Database error for $originalName: " . $stmt->error;
                    // Delete uploaded file if database insert failed
                    unlink($filePath);
                }
                $stmt->close();
            } else {
                $errors[] = "Failed to upload $originalName";
            }
        } else {
            $errors[] = "Upload error for file " . ($i + 1);
        }
    }
    
    if (empty($errors)) {
        $koneksi->commit();
        echo json_encode([
            'success' => true,
            'uploaded_files' => $uploadedFiles,
            'message' => count($uploadedFiles) . ' photos uploaded successfully'
        ]);
    } else {
        $koneksi->rollback();
        echo json_encode([
            'error' => 'Some files failed to upload',
            'errors' => $errors,
            'uploaded_files' => $uploadedFiles
        ]);
    }
} catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode(['error' => 'Database transaction failed: ' . $e->getMessage()]);
}

$koneksi->close();
?>