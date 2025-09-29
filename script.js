window.onload = function () {
  // --- Scroll suave para los enlaces de navegación ---
  let enlaces = document.querySelectorAll('a[href^="#"]');
  enlaces.forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      let destino = document.querySelector(this.getAttribute("href"));
      if (destino) {
        destino.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // --- Animación de aparición (fade-in) al hacer scroll ---
  let opcionesObserver = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  };

  let observer = new IntersectionObserver((entradas) => {
    entradas.forEach((entrada) => {
      if (entrada.isIntersecting) {
        entrada.target.classList.add("visible");
      }
    });
  }, opcionesObserver);

  let elementosFadeIn = document.querySelectorAll(".fade-in");
  elementosFadeIn.forEach((el) => {
    observer.observe(el);
  });

  // --- Manejo del login con Google ---
  function manejarLoginGoogle() {
    let boton = document.querySelector(".google-login-btn");

    // Estado de "cargando"
    boton.classList.add("loading");
    boton.innerHTML = `
      <svg class="google-icon" viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
      </svg>
      Conectando...
    `;

    // Simular proceso OAuth y redirigir al index de dashboard
    setTimeout(() => {
      window.location.href = "dashboard/index.html";
    }, 2000);
  }

  // 👉 Aquí engancho el botón al evento click
  let botonGoogle = document.querySelector(".google-login-btn");
  if (botonGoogle) {
    botonGoogle.addEventListener("click", manejarLoginGoogle);
  }

  // --- Cambio de fondo de la navbar al hacer scroll ---
  window.addEventListener("scroll", () => {
    let navbar = document.querySelector(".navbar");
    if (window.scrollY > 50) {
      navbar.style.background = "rgba(255, 255, 255, 0.98)";
    } else {
      navbar.style.background = "rgba(255, 255, 255, 0.95)";
    }
  });

  // --- Efectos hover para las tarjetas ---
  let tarjetas = document.querySelectorAll(".feature-card");
  tarjetas.forEach((card) => {
    card.addEventListener("mouseenter", function () {
      this.style.transform = "translateY(-10px) scale(1.02)";
    });

    card.addEventListener("mouseleave", function () {
      this.style.transform = "translateY(0) scale(1)";
    });
  });
};
