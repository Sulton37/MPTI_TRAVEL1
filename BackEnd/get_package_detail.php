<?php
// Optimized version dengan caching dan error handling yang lebih baik
ini_set('display_errors', 0);
error_reporting(0);

// Start output buffering
ob_start();

// Set headers untuk performa yang lebih baik
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$startTime = microtime(true);

try {
    // Quick validation
    $package_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$package_id || $package_id <= 0) {
        throw new Exception('Invalid package ID');
    }

    // Database connection dengan timeout
    $koneksi = new mysqli("localhost", "root", "", "paket_travel");
    
    if ($koneksi->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Set timeout untuk query
    $koneksi->set_charset("utf8mb4");
    
    // Optimized query - ambil semua kolom yang diperlukan
    $stmt = $koneksi->prepare("
        SELECT id, nama, deskripsi, fotos, itinerary, highlights, inclusions, exclusions, price, duration
        FROM paket 
        WHERE id = ? 
        LIMIT 1
    ");
    
    if (!$stmt) {
        throw new Exception('Query preparation failed');
    }
    
    $stmt->bind_param("i", $package_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed');
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Package not found');
    }
    
    $package = $result->fetch_assoc();
    
    // Process main photos dengan optimasi
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
        SELECT photo_filename, photo_caption, photo_order 
        FROM package_gallery 
        WHERE package_id = ? 
        ORDER BY photo_order ASC, uploaded_at ASC
    ");
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
                'caption' => $galleryPhoto['photo_caption'] ?: 'Foto Gallery',
                'type' => 'gallery'
            ];
        }
    }
    
    // Fallback photos jika tidak ada
    if (empty($processedFotos)) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . '://' . $host . '/MPTI_TRAVEL/Asset/Package_Culture/';
        
        $processedFotos = [
            ['url' => $baseUrl . 'borobudur.jpg', 'caption' => 'Candi Borobudur', 'type' => 'default'],
            ['url' => $baseUrl . 'prambanan.jpg', 'caption' => 'Candi Prambanan', 'type' => 'default'],
            ['url' => $baseUrl . 'keraton.jpg', 'caption' => 'Keraton Yogyakarta', 'type' => 'default'],
            ['url' => $baseUrl . 'tamansari.jpg', 'caption' => 'Taman Sari', 'type' => 'default']
        ];
    }
    
    // Build response dengan data yang sudah dioptimasi
    $response = [
        'success' => true,
        'id' => (int)$package['id'],
        'nama' => trim($package['nama'] ?? ''),
        'deskripsi' => trim($package['deskripsi'] ?? ''),
        'fotos' => $processedFotos, // Now includes both main and gallery photos
        'itinerary' => trim($package['itinerary'] ?? ''),
        'highlights' => trim($package['highlights'] ?? ''),
        'inclusions' => trim($package['inclusions'] ?? ''),
        'exclusions' => trim($package['exclusions'] ?? ''),
        'price' => $package['price'] ? (float)$package['price'] : null,
        'duration' => trim($package['duration'] ?? '2D1N'),
        'total_photos' => count($processedFotos),
        'load_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
    ];
    
    $stmt->close();
    $koneksi->close();
    
    // Clear output buffer dan kirim response
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