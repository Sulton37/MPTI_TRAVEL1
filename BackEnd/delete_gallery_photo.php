<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $photo_id = filter_input(INPUT_POST, 'photo_id', FILTER_VALIDATE_INT);
    $package_id = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
    
    if (!$photo_id || $photo_id <= 0) {
        throw new Exception('Invalid photo ID');
    }
    
    if (!$package_id || $package_id <= 0) {
        throw new Exception('Invalid package ID');
    }

    $koneksi = new mysqli("localhost", "root", "", "paket_travel");
    if ($koneksi->connect_error) {
        throw new Exception('Database connection failed');
    }

    $koneksi->set_charset("utf8mb4");

    // Get photo info before deleting
    $stmt = $koneksi->prepare("SELECT photo_filename, photo_order FROM package_gallery WHERE id = ? AND package_id = ?");
    $stmt->bind_param("ii", $photo_id, $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $filename = $row['photo_filename'];
        $deleted_order = $row['photo_order'];
        
        // Delete from database
        $deleteStmt = $koneksi->prepare("DELETE FROM package_gallery WHERE id = ? AND package_id = ?");
        $deleteStmt->bind_param("ii", $photo_id, $package_id);
        
        if ($deleteStmt->execute()) {
            // Delete physical file
            $filePath = __DIR__ . '/uploads/gallery/' . $filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Reorder remaining photos
            $reorderStmt = $koneksi->prepare("
                UPDATE package_gallery 
                SET photo_order = photo_order - 1 
                WHERE package_id = ? AND photo_order > ?
            ");
            $reorderStmt->bind_param("ii", $package_id, $deleted_order);
            $reorderStmt->execute();
            $reorderStmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Foto berhasil dihapus',
                'deleted_file' => $filename
            ]);
        } else {
            throw new Exception('Failed to delete photo from database');
        }
        
        $deleteStmt->close();
    } else {
        throw new Exception('Photo not found');
    }

    $stmt->close();
    $koneksi->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit;
?>