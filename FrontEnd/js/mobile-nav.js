document.addEventListener("DOMContentLoaded", function () {
  const toggle = document.querySelector(".mobile-menu-toggle");
  const sidebar = document.querySelector(".mobile-sidebar");
  const overlay = document.querySelector(".sidebar-overlay");
  const body = document.body;

  toggle.addEventListener("click", function (e) {
    e.stopPropagation();
    body.classList.toggle("sidebar-open");
  });

  overlay.addEventListener("click", function () {
    body.classList.remove("sidebar-open");
  });

  // Close sidebar when clicking outside
  document.addEventListener("click", function (e) {
    if (!sidebar.contains(e.target) && e.target !== toggle) {
      body.classList.remove("sidebar-open");
    }
  });
});
