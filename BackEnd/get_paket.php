<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Koneksi database
$koneksi = new mysqli("localhost", "root", "", "paket_travel");
if ($koneksi->connect_error) {
    die("Connection failed: " . $koneksi->connect_error);
} else {
    echo "Database connected successfully!";
}

// Ambil data paket
$stmt = $koneksi->prepare("SELECT id, nama, deskripsi, foto FROM paket ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();

$paket = [];
while ($row = $result->fetch_assoc()) {
    $paket[] = [
        'id' => $row['id'],
        'nama' => htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8'),
        'deskripsi' => htmlspecialchars($row['deskripsi'], ENT_QUOTES, 'UTF-8'),
        'foto' => '../BackEnd/uploads/' . htmlspecialchars($row['foto'], ENT_QUOTES, 'UTF-8')
    ];
}

echo json_encode($paket);

$stmt->close();
$koneksi->close();
?>

document.addEventListener('DOMContentLoaded', function() {
    loadPackages();
});

async function loadPackages() {
    try {
        const response = await fetch('../../BackEnd/get_paket.php');
        const packages = await response.json();
        
        if (packages.error) {
            console.error('Error:', packages.error);
            showFallbackContent();
            return;
        }
        
        displayPackages(packages);
    } catch (error) {
        console.error('Error fetching packages:', error);
        showFallbackContent();
    }
}

function displayPackages(packages) {
    const slider = document.querySelector('.slider');
    
    if (packages.length === 0) {
        slider.innerHTML = '<div class="no-packages">Belum ada paket tour tersedia.</div>';
        return;
    }
    
    slider.innerHTML = ''; // Kosongkan konten yang ada
    
    packages.forEach(package => {
        const tourCard = createTourCard(package);
        slider.appendChild(tourCard);
    });
}

function createTourCard(package) {
    const card = document.createElement('div');
    card.className = 'tour-card';
    
    card.innerHTML = `
        <div class="image-slideshow">
            <img src="${package.foto}" alt="${package.nama}" 
                 onerror="this.src='../../Asset/Package_Culture/borobudur.jpg'">
        </div>
        <div class="content">
            <h3>${package.nama}</h3>
            <p>${package.deskripsi}</p>
            <a href="Package_1.html?id=${package.id}" class="detail-button">More Details</a>
        </div>
    `;
    
    return card;
}

function showFallbackContent() {
    const slider = document.querySelector('.slider');
    slider.innerHTML = `
        <div class="error-message">
            <p>Tidak dapat memuat data paket. Silakan coba lagi nanti.</p>
            <button onclick="loadPackages()">Coba Lagi</button>
        </div>
    `;
}