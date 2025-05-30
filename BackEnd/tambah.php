<?php
$koneksi = new mysqli("localhost", "root", "", "paket_travel");

$nama = $_POST['nama'];
$deskripsi = $_POST['deskripsi'];

// Validasi file upload
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $foto = $_FILES['foto'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Validasi ekstensi file juga (double check)
    $fileExtension = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png'];
    
    if (!in_array($foto['type'], $allowedTypes) || !in_array($fileExtension, $allowedExt)) {
        header("Location: admin.php?error=invalid_file_type");
        exit;
    }
    
    // Validasi ukuran file
    if ($foto['size'] > $maxSize) {
        header("Location: admin.php?error=file_too_large");
        exit;
    }
    
    // Buat direktori uploads jika belum ada
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate nama file yang aman
    $namaFile = time() . "_" . uniqid() . "." . $fileExtension;
    $target = $uploadDir . $namaFile;
    
    if (move_uploaded_file($foto['tmp_name'], $target)) {
        $koneksi->query("INSERT INTO paket (nama, deskripsi, foto) VALUES ('$nama', '$deskripsi', '$namaFile')");
    }
}

header("Location: admin.php");
exit;
?>
