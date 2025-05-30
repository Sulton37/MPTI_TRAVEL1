document.addEventListener('DOMContentLoaded', function() {
    loadPackages();
});

async function loadPackages() {
    try {
        const response = await fetch('../BackEnd/get_paket.php');
        const packages = await response.json();
        
        if (packages.error) {
            console.error('Error:', packages.error);
            return;
        }
        
        displayPackages(packages);
    } catch (error) {
        console.error('Error fetching packages:', error);
        // Fallback ke data statis jika ada error
        displayStaticPackages();
    }
}

function displayPackages(packages) {
    const slider = document.querySelector('.slider');
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
            <img src="${package.foto}" alt="${package.nama}" onerror="this.src='../../Asset/Package_Culture/borobudur.jpg'">
        </div>
        <div class="content">
            <h3>${package.nama}</h3>
            <p>${package.deskripsi}</p>
            <a href="Package_1.html?id=${package.id}" class="detail-button">More Details</a>
        </div>
    `;
    
    return card;
}

function displayStaticPackages() {
    // Fallback ke data statis jika backend tidak tersedia
    console.log('Using static data as fallback');
}