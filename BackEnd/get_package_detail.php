<?php
// filepath: c:\xampp\htdocs\MPTI_TRAVEL\BackEnd\get_package_detail.php
// Disable error display to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(0);

// Start output buffering
ob_start();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$startTime = microtime(true);

try {
    // Validate package ID
    $package_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$package_id || $package_id <= 0) {
        throw new Exception('Invalid package ID');
    }

    // Database connection
    $koneksi = new mysqli("localhost", "root", "", "paket_travel");
    
    if ($koneksi->connect_error) {
        throw new Exception('Database connection failed: ' . $koneksi->connect_error);
    }

    $koneksi->set_charset("utf8mb4");
    
    // Get package data
    $stmt = $koneksi->prepare("
        SELECT id, nama, deskripsi, fotos, itinerary, highlights, inclusions, exclusions, 
               CAST(price AS DECIMAL(12,2)) as price, duration
        FROM paket 
        WHERE id = ? 
        LIMIT 1
    ");
    
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $koneksi->error);
    }
    
    $stmt->bind_param("i", $package_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Package not found');
    }
    
    $package = $result->fetch_assoc();
    
    // Process main photos
    $processedFotos = [];
    $fotosArray = json_decode($package['fotos'], true);
    
    if (is_array($fotosArray) && !empty($fotosArray)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . '://' . $host . '/MPTI_TRAVEL/BackEnd/uploads/';
        
        foreach ($fotosArray as $foto) {
            if (!empty($foto)) {
                $fotoPath = __DIR__ . '/uploads/' . $foto;
                
                if (file_exists($fotoPath) && is_readable($fotoPath)) {
                    $processedFotos[] = [
                        'url' => $baseUrl . $foto,
                        'caption' => 'Foto Paket',
                        'type' => 'main'
                    ];
                }
            }
        }
    }
    
    // Get additional gallery photos
    $galleryStmt = $koneksi->prepare("
        SELECT photo_filename, caption, photo_order 
        FROM package_gallery 
        WHERE package_id = ? 
        ORDER BY photo_order ASC, uploaded_at ASC
    ");
    
    if ($galleryStmt) {
        $galleryStmt->bind_param("i", $package_id);
        $galleryStmt->execute();
        $galleryResult = $galleryStmt->get_result();
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $galleryBaseUrl = $protocol . '://' . $host . '/MPTI_TRAVEL/BackEnd/uploads/gallery/';
        
        while ($galleryPhoto = $galleryResult->fetch_assoc()) {
            $galleryPath = __DIR__ . '/uploads/gallery/' . $galleryPhoto['photo_filename'];
            
            if (file_exists($galleryPath) && is_readable($galleryPath)) {
                $processedFotos[] = [
                    'url' => $galleryBaseUrl . $galleryPhoto['photo_filename'],
                    'caption' => $galleryPhoto['caption'] ?: 'Foto Gallery',
                    'type' => 'gallery'
                ];
            }
        }
        $galleryStmt->close();
    }
    
    // Add fallback photos if none exist
    if (empty($processedFotos)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . '://' . $host . '/MPTI_TRAVEL/Asset/Package_Culture/';
        
        $processedFotos = [
            ['url' => $baseUrl . 'borobudur.jpg', 'caption' => 'Candi Borobudur', 'type' => 'default'],
            ['url' => $baseUrl . 'prambanan.jpg', 'caption' => 'Candi Prambanan', 'type' => 'default'],
            ['url' => $baseUrl . 'kraton.jpg', 'caption' => 'Keraton Yogyakarta', 'type' => 'default']
        ];
    }
    
    // Build response
    $response = [
        'success' => true,
        'id' => (int)$package['id'],
        'nama' => trim($package['nama'] ?? ''),
        'deskripsi' => trim($package['deskripsi'] ?? ''),
        'fotos' => $processedFotos,
        'itinerary' => trim($package['itinerary'] ?? ''),
        'highlights' => trim($package['highlights'] ?? ''),
        'inclusions' => trim($package['inclusions'] ?? ''),
        'exclusions' => trim($package['exclusions'] ?? ''),
        'price' => $package['price'] ? (float)$package['price'] : null,
        'price_raw' => $package['price'], // Raw value dari database
        'formatted_price' => $package['price'] ? 'Rp ' . number_format($package['price'], 0, ',', '.') : null,
        'duration' => trim($package['duration'] ?? '2D1N'),
        'debug_price' => [
            'db_value' => $package['price'],
            'db_type' => gettype($package['price']),
            'cast_float' => (float)$package['price'],
            'formatted' => number_format($package['price'], 0, ',', '.')
        ],
        'total_photos' => count($processedFotos),
        'load_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
    ];
    
    $stmt->close();
    $koneksi->close();
    
    // Clear output buffer and send response
    ob_end_clean();
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    ob_end_clean();
    
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'id' => $_GET['id'] ?? null,
        'load_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
    ], JSON_UNESCAPED_UNICODE);
}

exit;
?>