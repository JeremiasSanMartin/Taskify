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
