<?php
$koneksi = new mysqli("localhost", "root", "", "paket_travel");

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $query = $koneksi->query("SELECT foto FROM paket WHERE id=$id");
    $row = $query->fetch_assoc();
    if (file_exists("uploads/" . $row['foto'])) {
        unlink("uploads/" . $row['foto']);
    }
    $koneksi->query("DELETE FROM paket WHERE id=$id");
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
        input, textarea { width: 100%; padding: 8px; margin: 5px 0; }
        .paket { border: 1px solid #ccc; padding: 15px; border-radius: 10px; margin-bottom: 10px; }
        img { max-width: 200px; display: block; margin-bottom: 10px; }
        .hapus { color: red; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<h1>Admin - Kelola Paket Tour</h1>

<h2>Tambah Paket Baru</h2>
<form action="tambah.php" method="post" enctype="multipart/form-data">
    <input type="text" name="nama" placeholder="Nama Paket" required>
    <textarea name="deskripsi" placeholder="Deskripsi Paket" required></textarea>
    <input type="file" name="foto" accept="image/*" required>
    <button type="submit">Tambah Paket</button>
</form>

<h2>Daftar Paket</h2>

<?php
$result = $koneksi->query("SELECT * FROM paket ORDER BY id DESC");
while ($row = $result->fetch_assoc()):
?>
    <div class="paket">
        <h3><?= htmlspecialchars($row['nama']) ?></h3>
        <img src="uploads/<?= htmlspecialchars($row['foto']) ?>" alt="Foto Paket">
        <p><?= nl2br(htmlspecialchars($row['deskripsi'])) ?></p>
        <a href="admin.php?hapus=<?= $row['id'] ?>" class="hapus" onclick="return confirm('Yakin ingin hapus?')">Hapus</a>
    </div>
<?php endwhile; ?>

</body>
</html>
