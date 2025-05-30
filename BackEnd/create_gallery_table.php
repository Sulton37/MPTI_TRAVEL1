<?php
// filepath: c:\xampp\htdocs\MPTI_TRAVEL\BackEnd\create_gallery_table.php
$koneksi = new mysqli("localhost", "root", "", "paket_travel");

if ($koneksi->connect_error) {
    die("Connection failed: " . $koneksi->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS `package_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `photo_filename` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `photo_order` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($koneksi->query($sql) === TRUE) {
    echo "Table `package_gallery` created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $koneksi->error . "<br>";
}

// Create upload directory
$uploadDir = 'uploads/gallery/';
if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        echo "Directory '$uploadDir' created successfully.<br>";
    } else {
        echo "Error creating directory '$uploadDir'.<br>";
    }
} else {
    echo "Directory '$uploadDir' already exists.<br>";
}

// Create .htaccess file to protect uploads
$htaccessContent = "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>";
file_put_contents($uploadDir . '.htaccess', $htaccessContent);
echo ".htaccess file created in uploads directory.<br>";

$koneksi->close();
echo "<br>Setup completed!";
?>