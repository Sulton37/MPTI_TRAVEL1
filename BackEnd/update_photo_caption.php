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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Try form data
    $input = $_POST;
}

try {
    $photo_id = filter_var($input['photo_id'] ?? null, FILTER_VALIDATE_INT);
    $caption = trim($input['caption'] ?? '');
    
    if (!$photo_id || $photo_id <= 0) {
        throw new Exception('Invalid photo ID');
    }

    $koneksi = new mysqli("localhost", "root", "", "paket_travel");
    if ($koneksi->connect_error) {
        throw new Exception('Database connection failed');
    }

    $koneksi->set_charset("utf8mb4");

    // Use correct column name 'caption'
    $stmt = $koneksi->prepare("UPDATE package_gallery SET caption = ? WHERE id = ?");
    $stmt->bind_param("si", $caption, $photo_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Caption berhasil diupdate',
                'new_caption' => $caption
            ]);
        } else {
            throw new Exception('Photo not found or no changes made');
        }
    } else {
        throw new Exception('Failed to update caption: ' . $stmt->error);
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