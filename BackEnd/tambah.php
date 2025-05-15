<?php
$koneksi = new mysqli("localhost", "root", "", "paket_travel");

$nama = $_POST['nama'];
$deskripsi = $_POST['deskripsi'];

$foto = $_FILES['foto']['name'];
$tmp = $_FILES['foto']['tmp_name'];

$path = "uploads/";
$target = $path . time() . "_" . basename($foto);

if (move_uploaded_file($tmp, $target)) {
    $namaFile = basename($target);
    $koneksi->query("INSERT INTO paket (nama, deskripsi, foto) VALUES ('$nama', '$deskripsi', '$namaFile')");
}

header("Location: admin.php");
exit;
?>
