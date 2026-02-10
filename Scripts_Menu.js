document.addEventListener("DOMContentLoaded", () => {

  const moduloBtns = document.querySelectorAll(".modulo-btn");
  const submenuBtns = document.querySelectorAll(".submenu-btn");

  // ===== MODULOS =====
  moduloBtns.forEach(btn => {
    btn.addEventListener("click", () => {

      const moduloContent = btn.nextElementSibling;

      // Cerrar otros módulos
      document.querySelectorAll(".modulo-content").forEach(m => {
        if (m !== moduloContent) {
          m.style.maxHeight = null;
          m.querySelectorAll(".submenu-content").forEach(s => {
            s.style.maxHeight = null;
          });
        }
      });

      // Toggle módulo actual
      if (moduloContent.style.maxHeight) {
        moduloContent.style.maxHeight = null;
      } else {
        moduloContent.style.maxHeight = moduloContent.scrollHeight + "px";
      }

    });
  });

  // ===== SUBMENUS =====
  submenuBtns.forEach(btn => {
    btn.addEventListener("click", () => {

      const submenuContent = btn.nextElementSibling;
      const moduloActual = btn.closest(".modulo-content");

      // Cerrar otros submenus
      moduloActual.querySelectorAll(".submenu-content").forEach(s => {
        if (s !== submenuContent) {
          s.style.maxHeight = null;
        }
      });

      // Toggle submenu actual
      if (submenuContent.style.maxHeight) {
        submenuContent.style.maxHeight = null;
      } else {
        submenuContent.style.maxHeight = submenuContent.scrollHeight + "px";
      }

      // 🔥 CLAVE: recalcular altura del módulo padre
      setTimeout(() => {
        moduloActual.style.maxHeight = moduloActual.scrollHeight + "px";
      }, 300); // coincide con el transition

    });
  });

});

