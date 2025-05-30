<?php
// Koneksi database dengan error handling
$koneksi = new mysqli("localhost", "root", "", "paket_travel");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Tampilkan pesan berdasarkan parameter URL
$message = '';
if (isset($_GET['success'])) {
    $message = '<div class="success">Paket berhasil ditambahkan!</div>';
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'input_too_long':
            $message = '<div class="error">Input terlalu panjang!</div>';
            break;
        case 'invalid_file_type':
            $message = '<div class="error">Tipe file tidak valid! Gunakan JPG, JPEG, atau PNG.</div>';
            break;
        case 'file_too_large':
            $message = '<div class="error">Ukuran file terlalu besar! Maksimal 5MB.</div>';
            break;
        case 'upload_failed':
            $message = '<div class="error">Upload file gagal!</div>';
            break;
        case 'database_error':
            $message = '<div class="error">Error database!</div>';
            break;
        case 'no_file':
            $message = '<div class="error">File foto harus diupload!</div>';
            break;
    }
}

// Perbaikan SQL Injection dengan prepared statement
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']); // Validasi input sebagai integer
    
    // Gunakan prepared statement
    $stmt = $koneksi->prepare("SELECT foto FROM paket WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Hapus file foto jika ada
        $fotoPath = "uploads/" . $row['foto'];
        if (file_exists($fotoPath)) {
            unlink($fotoPath);
        }
        
        // Hapus record dari database
        $deleteStmt = $koneksi->prepare("DELETE FROM paket WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        $deleteStmt->execute();
        $deleteStmt->close();
    }
    
    $stmt->close();
    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Paket Tour</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; }
        form, .paket { margin-bottom: 30px; }
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; box-sizing: border-box; }
        .paket { border: 1px solid #ccc; padding: 15px; border-radius: 10px; margin-bottom: 10px; }
        img { max-width: 200px; display: block; margin-bottom: 10px; }
        .hapus { color: red; text-decoration: none; font-weight: bold; }
        .error { color: red; margin: 10px 0; padding: 10px; background: #ffebee; border-radius: 5px; }
        .success { color: green; margin: 10px 0; padding: 10px; background: #e8f5e8; border-radius: 5px; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
        button:hover { background: #005a87; }
    </style>
</head>
<body>

<h1>Admin - Kelola Paket Tour</h1>

<?= $message ?>

<h2>Tambah Paket Baru</h2>
<form action="tambah.php" method="post" enctype="multipart/form-data">
    <input type="text" name="nama" placeholder="Nama Paket" required maxlength="255">
    <textarea name="deskripsi" placeholder="Deskripsi Paket" required maxlength="1000" rows="4"></textarea>
    <input type="file" name="foto" accept="image/jpeg,image/png,image/jpg" required>
    <button type="submit">Tambah Paket</button>
</form>

<h2>Daftar Paket</h2>

<?php
// Prepared statement untuk select
$stmt = $koneksi->prepare("SELECT id, nama, deskripsi, foto FROM paket ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
?>
    <div class="paket">
        <h3><?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?></h3>
        <img src="uploads/<?= htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto Paket" onerror="this.src='uploads/default.jpg'">
        <p><?= nl2br(htmlspecialchars($row['deskripsi'], ENT_QUOTES, 'UTF-8')) ?></p>
        <a href="admin.php?hapus=<?= $row['id'] ?>" class="hapus" onclick="return confirm('Yakin ingin hapus?')">Hapus</a>
    </div>
<?php 
    endwhile;
else:
?>
    <p>Belum ada paket tour yang tersedia.</p>
<?php endif; ?>

<?php
$stmt->close();
$koneksi->close();
?>

</body>
</html>
