<?php
// Pastikan tidak ada output sebelum header
ob_start();

// Set header dengan encoding yang benar
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Koneksi database
    $koneksi = new mysqli("localhost", "root", "", "paket_travel");
    
    if ($koneksi->connect_error) {
        throw new Exception('Database connection failed: ' . $koneksi->connect_error);
    }

    // Set charset untuk database
    $koneksi->set_charset("utf8");

    // Pastikan menggunakan kolom 'fotos'
    $stmt = $koneksi->prepare("SELECT id, nama, deskripsi, fotos FROM paket ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $paket = [];
    while ($row = $result->fetch_assoc()) {
        // Decode photos JSON
        $fotosArray = json_decode($row['fotos'], true);
        
        // Handle different data formats
        if (!is_array($fotosArray)) {
            $fotosArray = !empty($row['fotos']) ? [$row['fotos']] : [];
        }
        
        $processedFotos = [];
        $fotosExist = [];
        
        foreach ($fotosArray as $index => $foto) {
            if (empty($foto)) continue;
            
            $fotoPath = 'uploads/' . $foto;
            $fullPath = __DIR__ . '/' . $fotoPath;
            
            // Enhanced file checking
            $fileExists = file_exists($fullPath);
            $fileReadable = $fileExists ? is_readable($fullPath) : false;
            $fileSize = $fileExists ? filesize($fullPath) : 0;
            
            // Log file check for debugging
            error_log("File check - {$foto}: exists={$fileExists}, readable={$fileReadable}, size={$fileSize}");
            
            if ($fileExists && $fileReadable && $fileSize > 0) {
                // Create absolute URL for better compatibility
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $baseUrl = $protocol . '://' . $host . '/MPTI_TRAVEL/BackEnd/uploads/';
                
                $processedFotos[] = $baseUrl . htmlspecialchars($foto, ENT_QUOTES, 'UTF-8');
                $fotosExist[] = true;
            } else {
                // Use placeholder
                $processedFotos[] = getPlaceholderImage($row['nama'], $row['deskripsi'], $index);
                $fotosExist[] = false;
            }
        }
        
        // Ensure minimum 3 photos for slideshow
        while (count($processedFotos) < 3) {
            $processedFotos[] = getPlaceholderImage($row['nama'], $row['deskripsi'], count($processedFotos));
            $fotosExist[] = false;
        }
        
        $paket[] = [
            'id' => (int)$row['id'],
            'nama' => trim(htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8')),
            'deskripsi' => trim(htmlspecialchars($row['deskripsi'], ENT_QUOTES, 'UTF-8')),
            'fotos' => $processedFotos,
            'fotos_original' => $fotosArray,
            'fotos_exist' => $fotosExist,
            'foto_count' => count($processedFotos)
        ];
    }

    $stmt->close();
    $koneksi->close();
    
    ob_end_clean();
    echo json_encode($paket, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit;

function getPlaceholderImage($nama, $deskripsi, $index = 0) {
    // Use absolute URLs for placeholders too
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host . '/MPTI_TRAVEL/Asset/Package_Culture/';
    
    $placeholders = [
        $baseUrl . 'borobudur.jpg',
        $baseUrl . 'prambanan.jpg',
        $baseUrl . 'kraton.jpg',
        $baseUrl . 'malioboro.jpg',
        $baseUrl . 'taman_sari.jpg'
    ];
    
    return $placeholders[$index % count($placeholders)];
}
?>
