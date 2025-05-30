<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$koneksi = new mysqli("localhost", "root", "", "paket_travel");
if ($koneksi->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $koneksi->connect_error]);
    exit;
}

// Check if package_id is provided
if (!isset($_GET['package_id']) || empty($_GET['package_id'])) {
    echo json_encode(['error' => 'Package ID is required']);
    exit;
}

$package_id = intval($_GET['package_id']);

// Validate that package_id is a positive integer
if ($package_id <= 0) {
    echo json_encode(['error' => 'Invalid Package ID']);
    exit;
}

try {
    // Check if package exists first
    $checkStmt = $koneksi->prepare("SELECT id FROM paket WHERE id = ?");
    $checkStmt->bind_param("i", $package_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode([]);
        exit;
    }
    $checkStmt->close();
    
    // Get gallery photos
    $stmt = $koneksi->prepare("SELECT id, package_id, photo_filename, caption, photo_order, created_at FROM package_gallery WHERE package_id = ? ORDER BY photo_order ASC, id ASC");
    $stmt->bind_param("i", $package_id);
    
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
        exit;
    }
    
    $result = $stmt->get_result();
    
    // Build array of photos
    $photos = [];
    while ($row = $result->fetch_assoc()) {
        $photos[] = [
            'id' => (int)$row['id'],
            'package_id' => (int)$row['package_id'],
            'photo_filename' => $row['photo_filename'],
            'caption' => $row['caption'] ?: '',
            'photo_order' => (int)$row['photo_order'],
            'created_at' => $row['created_at']
        ];
    }
    
    $stmt->close();
    $koneksi->close();
    
    // Ensure we always return an array (even if empty)
    echo json_encode($photos, JSON_NUMERIC_CHECK);
    
} catch(Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>