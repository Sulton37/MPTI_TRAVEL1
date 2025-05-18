document.addEventListener("DOMContentLoaded", function () {
  const toggle = document.querySelector(".mobile-menu-toggle");
  const sidebar = document.querySelector(".mobile-sidebar");
  const overlay = document.querySelector(".sidebar-overlay");
  const body = document.body;
  const header = document.querySelector("header");

  // Scroll effect for header
  window.addEventListener("scroll", function () {
    if (window.scrollY > 50) {
      header.classList.add("scrolled");
    } else {
      header.classList.remove("scrolled");
    }
  });

  // Toggle sidebar open/close
  toggle.addEventListener("click", function (e) {
    e.stopPropagation();
    toggle.classList.toggle("active");
    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");
    body.classList.toggle("sidebar-open");
  });

  // Close sidebar if click on overlay
  overlay.addEventListener("click", function () {
    toggle.classList.remove("active");
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
    body.classList.remove("sidebar-open");
  });

  // Close sidebar if click outside sidebar and toggle
  document.addEventListener("click", function (e) {
    if (
      !sidebar.contains(e.target) &&
      e.target !== toggle &&
      sidebar.classList.contains("active")
    ) {
      toggle.classList.remove("active");
      sidebar.classList.remove("active");
      overlay.classList.remove("active");
      body.classList.remove("sidebar-open");
    }
  });
});
