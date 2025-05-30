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
    $direction = trim($_POST['direction'] ?? '');
    
    if (!$photo_id || $photo_id <= 0) {
        throw new Exception('Invalid photo ID');
    }
    
    if (!$package_id || $package_id <= 0) {
        throw new Exception('Invalid package ID');
    }
    
    if (!in_array($direction, ['up', 'down'])) {
        throw new Exception('Invalid direction');
    }

    $koneksi = new mysqli("localhost", "root", "", "paket_travel");
    if ($koneksi->connect_error) {
        throw new Exception('Database connection failed');
    }

    $koneksi->set_charset("utf8mb4");

    // Get current photo order
    $stmt = $koneksi->prepare("SELECT photo_order FROM package_gallery WHERE id = ? AND package_id = ?");
    $stmt->bind_param("ii", $photo_id, $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$row = $result->fetch_assoc()) {
        throw new Exception('Photo not found');
    }
    
    $current_order = $row['photo_order'];
    $new_order = $direction === 'up' ? $current_order - 1 : $current_order + 1;
    
    if ($new_order < 1) {
        throw new Exception('Photo is already at the top');
    }
    
    // Check if target position exists
    $checkStmt = $koneksi->prepare("SELECT id FROM package_gallery WHERE package_id = ? AND photo_order = ?");
    $checkStmt->bind_param("ii", $package_id, $new_order);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0 && $direction === 'down') {
        throw new Exception('Photo is already at the bottom');
    }
    
    if ($checkResult->num_rows > 0) {
        $target_photo = $checkResult->fetch_assoc();
        $target_id = $target_photo['id'];
        
        // Swap orders
        $koneksi->begin_transaction();
        
        try {
            // Temporarily set current photo to order 0
            $stmt1 = $koneksi->prepare("UPDATE package_gallery SET photo_order = 0 WHERE id = ?");
            $stmt1->bind_param("i", $photo_id);
            $stmt1->execute();
            
            // Move target photo to current photo's position
            $stmt2 = $koneksi->prepare("UPDATE package_gallery SET photo_order = ? WHERE id = ?");
            $stmt2->bind_param("ii", $current_order, $target_id);
            $stmt2->execute();
            
            // Move current photo to target position
            $stmt3 = $koneksi->prepare("UPDATE package_gallery SET photo_order = ? WHERE id = ?");
            $stmt3->bind_param("ii", $new_order, $photo_id);
            $stmt3->execute();
            
            $koneksi->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Photo order updated successfully',
                'old_order' => $current_order,
                'new_order' => $new_order
            ]);
            
        } catch (Exception $e) {
            $koneksi->rollback();
            throw new Exception('Failed to update photo order: ' . $e->getMessage());
        }
    } else {
        // Just update the order (for moving down to a new position)
        $updateStmt = $koneksi->prepare("UPDATE package_gallery SET photo_order = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $new_order, $photo_id);
        
        if ($updateStmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Photo order updated successfully',
                'old_order' => $current_order,
                'new_order' => $new_order
            ]);
        } else {
            throw new Exception('Failed to update photo order');
        }
        $updateStmt->close();
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