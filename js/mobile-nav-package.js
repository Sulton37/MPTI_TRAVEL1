// Mobile Navigation
document.addEventListener('DOMContentLoaded', () => {
  const mobileToggle = document.querySelector(".mobile-menu-toggle");
  const mobileSidebar = document.querySelector(".mobile-sidebar");
  const sidebarOverlay = document.querySelector(".sidebar-overlay");

  mobileToggle.addEventListener("click", function () {
    mobileSidebar.classList.toggle("active");
    sidebarOverlay.classList.toggle("active");
    document.body.classList.toggle("sidebar-open");
    mobileToggle.classList.toggle("active");
  });

  sidebarOverlay.addEventListener("click", function () {
    mobileSidebar.classList.remove("active");
    sidebarOverlay.classList.remove("active");
    document.body.classList.remove("sidebar-open");
    mobileToggle.classList.remove("active");
  });




  // Header scroll effect
  const header = document.querySelector("header");
  window.addEventListener("scroll", function () {
    if (window.scrollY > 50) {
      header.classList.add("scrolled");
    } else {
      header.classList.remove("scrolled");
    }
  });

  // Accordion functionality for itinerary days
  const dayHeaders = document.querySelectorAll(".day-header");

  dayHeaders.forEach((header) => {
    header.addEventListener("click", function () {
      const content = this.nextElementSibling;
      const isExpanded = content.classList.contains("expanded");

      // Close all other open sections
      document.querySelectorAll(".day-content").forEach((item) => {
        if (item !== content) {
          item.classList.remove("expanded");
          item.previousElementSibling.classList.remove("active");
        }
      });

      // Toggle current section
      this.classList.toggle("active");
      content.classList.toggle("expanded");

      // Smooth scroll to expanded section
      if (!isExpanded) {
        setTimeout(() => {
          content.scrollIntoView({ behavior: "smooth", block: "nearest" });
        }, 350);
      }
    });
  });

  // Open first day by default
  if (dayHeaders.length > 0) {
    // Day pertama bisa di-expand tanpa scroll ke view
    dayHeaders[0].classList.add("active");
    dayHeaders[0].nextElementSibling.classList.add("expanded");
  }

  // Add floating book now button
  const floatingBook = document.createElement("a");
  floatingBook.href = "#book-now";
  floatingBook.className = "floating-book";
  floatingBook.innerHTML = '<i class="fas fa-calendar-check"></i> Book Now';
  document.body.appendChild(floatingBook);

  // Smooth scrolling for all links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();

      const targetId = this.getAttribute("href");
      if (targetId === "#") return;

      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 100,
          behavior: "smooth",
        });
      }
    });
  });

  // Logo click animation
  const logo = document.querySelector(".logo");
  if (logo) {
    logo.addEventListener("click", function (e) {
      e.preventDefault();
      this.classList.add("logo-clicked");
      setTimeout(() => {
        this.classList.remove("logo-clicked");
      }, 1000);
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  // Gallery hover effect
  const galleryItems = document.querySelectorAll(".gallery-item");
  galleryItems.forEach((item) => {
    item.addEventListener("mouseenter", function () {
      this.querySelector("img").style.transform = "scale(1.1)";
    });

    item.addEventListener("mouseleave", function () {
      this.querySelector("img").style.transform = "scale(1)";
    });
  });

  // Animation on scroll
  const animateOnScroll = function () {
    const elements = document.querySelectorAll(
      ".package-section, .gallery-section"
    );

    elements.forEach((element) => {
      const elementPosition = element.getBoundingClientRect().top;
      const screenPosition = window.innerHeight / 1.3;

      if (elementPosition < screenPosition) {
        element.style.opacity = "1";
        element.style.transform = "translateY(0)";
      }
    });
  };

  // Set initial state
  document
    .querySelectorAll(".package-section, .gallery-section")
    .forEach((el) => {
      el.style.opacity = "0";
      el.style.transform = "translateY(30px)";
      el.style.transition = "all 0.6s ease";
    });

  window.addEventListener("scroll", animateOnScroll);
  animateOnScroll(); // Run once on load
  
});

// 1. Sticky header shrink + shadow on scroll
const header = document.querySelector('header');
window.addEventListener('scroll', () => {
  if(window.scrollY > 80) {
    header.classList.add('scrolled');
  } else {
    header.classList.remove('scrolled');
  }
});

// 2. Scroll reveal animation (with Intersection Observer)
const revealElements = document.querySelectorAll('.fade-slide-up');
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if(entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.15 });

revealElements.forEach(el => observer.observe(el));

// 3. Mobile menu toggle with animated hamburger
const menuToggle = document.querySelector('.mobile-menu-toggle');
const sidebar = document.querySelector('.mobile-sidebar');
const overlay = document.querySelector('.sidebar-overlay');
menuToggle.addEventListener('click', () => {
  document.body.classList.toggle('sidebar-open');
});

// Close sidebar on overlay click
overlay.addEventListener('click', () => {
  document.body.classList.remove('sidebar-open');
});

// 4. Smooth scroll for nav links
document.querySelectorAll('nav a, .sidebar-nav a').forEach(link => {
  link.addEventListener('click', e => {
    const href = link.getAttribute('href');

    // Jika link adalah internal anchor (dimulai dengan # dan ada target ID)
    if (href.startsWith('#') && href.length > 1) {
      e.preventDefault();
      const targetID = href.slice(1);
      const targetSection = document.getElementById(targetID);
      if(targetSection) {
        window.scrollTo({
          top: targetSection.offsetTop - header.offsetHeight,
          behavior: 'smooth'
        });
      }

      // Tutup sidebar jika terbuka (untuk mobile)
      if(document.body.classList.contains('sidebar-open')) {
        document.body.classList.remove('sidebar-open');
      }
    } 
    // Kalau href "#" (tanpa target), preventDefault juga supaya gak reload
    else if (href === '#') {
      e.preventDefault();
    }
    // Kalau link ke halaman lain, biarkan default behavior (buka halaman)
  });
});


// 5. Simple lightbox for image gallery thumbnails
const mainImage = document.getElementById('main-image');
document.querySelectorAll('.thumb').forEach(thumb => {
  thumb.addEventListener('click', () => {
    // Update main image src and active thumbnail class
    mainImage.style.opacity = '0';
    setTimeout(() => {
      mainImage.src = thumb.src;
      mainImage.style.opacity = '1';
    }, 300);
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
  });
});

// 6. Ripple effect on CTA button
document.querySelectorAll('.cta-button').forEach(button => {
  button.addEventListener('click', e => {
    const ripple = document.createElement('span');
    ripple.classList.add('ripple');
    button.appendChild(ripple);
    const rect = button.getBoundingClientRect();
    ripple.style.left = `${e.clientX - rect.left}px`;
    ripple.style.top = `${e.clientY - rect.top}px`;
    setTimeout(() => {
      ripple.remove();
    }, 600);
  });
});

document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.mobile-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const body = document.body;

    function toggleMobileMenu() {
        mobileToggle.classList.toggle('active');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        body.classList.toggle('sidebar-open');
    }

    function closeMobileMenu() {
        mobileToggle.classList.remove('active');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        body.classList.remove('sidebar-open');
    }

    // Toggle menu on hamburger click
    if (mobileToggle) {
        mobileToggle.addEventListener('click', toggleMobileMenu);
    }

    // Close menu on overlay click
    if (overlay) {
        overlay.addEventListener('click', closeMobileMenu);
    }

    // Close menu on sidebar link click
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });

    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeMobileMenu();
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar.classList.contains('active')) {
            closeMobileMenu();
        }
    });
});
