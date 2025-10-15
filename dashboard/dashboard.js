window.onload = function () {
  // --- Inicializar Dashboard ---
  inicializarDashboard();
  manejarNavegacionSidebar();
  manejarSidebarResponsivo();
  handleGroupCards();

  // --- Cierre de sesión ---
  document.addEventListener("click", (e) => {
    if (e.target.closest(".logout-btn")) {
      console.log("[v1] Botón de cerrar sesión clickeado");
      if (confirm("¿Estás seguro de que quieres cerrar sesión?")) {
        window.location.href = "../index.html";
      }
    }
  });
};

// --- Función: inicialización del dashboard ---
function inicializarDashboard() {
  console.log("[v1] Dashboard inicializado");
  // Mostrar por defecto la sección de grupos
  mostrarSeccion("grupos");
}

// --- Función: navegación del sidebar ---
function manejarNavegacionSidebar() {
  let menuItems = document.querySelectorAll(".menu-item[data-section]");

  menuItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      e.preventDefault();

      // Quitar la clase activa de todos los ítems
      menuItems.forEach((menuItem) => menuItem.classList.remove("active"));

      // Activar el ítem clickeado
      item.classList.add("active");

      // Obtener y mostrar la sección correspondiente
      let seccion = item.getAttribute("data-section");
      mostrarSeccion(seccion);

      console.log(`[v1] Navegación hacia la sección: ${seccion}`);
    });
  });
}

// --- Función: mostrar sección ---
function mostrarSeccion(nombreSeccion) {
  // Ocultar todas las secciones
  let secciones = document.querySelectorAll(".content-section");
  secciones.forEach((seccion) => {
    seccion.classList.remove("active");
  });

  // Mostrar la sección seleccionada
  let seccionDestino = document.getElementById(`${nombreSeccion}-section`);
  if (seccionDestino) {
    seccionDestino.classList.add("active");
  }

  // Actualizar títulos de la página
  let titulo = document.querySelector(".page-title");
  let subtitulo = document.querySelector(".page-subtitle");

  if (nombreSeccion === "grupos") {
    titulo.textContent = "Mis Grupos";
    subtitulo.textContent = "Gestiona tus grupos y colaboraciones";
  } else if (nombreSeccion === "notificaciones") {
    titulo.textContent = "Notificaciones";
    subtitulo.textContent = "Mantente al día con las últimas actualizaciones";
  }
}

// --- Función: manejar sidebar responsivo ---
function manejarSidebarResponsivo() {
  if (window.innerWidth <= 768) {
    crearBotonMenuMovil();
  }

  window.addEventListener("resize", () => {
    if (window.innerWidth <= 768) {
      crearBotonMenuMovil();
    } else {
      eliminarBotonMenuMovil();
      let sidebar = document.querySelector(".sidebar");
      if (sidebar) {
        sidebar.style.transform = "translateX(0px)";
      }
    }
  });
}

// --- Función: crear botón del menú móvil ---
function crearBotonMenuMovil() {
  if (document.querySelector(".mobile-menu-btn")) return;

  let botonMovil = document.createElement("button");
  botonMovil.className = "mobile-menu-btn";
  botonMovil.innerHTML = '<i class="bi bi-list"></i>';
  botonMovil.style.cssText = `
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(139, 92, 246, 0.2);
    color: #8b5cf6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    backdrop-filter: blur(10px);
  `;

  document.body.appendChild(botonMovil);

  botonMovil.addEventListener("click", () => {
    let sidebar = document.querySelector(".sidebar");
    let estaAbierto = sidebar.style.transform === "translateX(0px)";

    if (estaAbierto) {
      sidebar.style.transform = "translateX(-100%)";
    } else {
      sidebar.style.transform = "translateX(0px)";
    }
  });
}

// --- Función: eliminar botón del menú móvil ---
function eliminarBotonMenuMovil() {
  let botonMovil = document.querySelector(".mobile-menu-btn");
  if (botonMovil) {
    botonMovil.remove();
  }
}

// --- Función: para redirigir al grupo --- esto debe modificarse al añadir back
function handleGroupCards() {
  document.addEventListener("click", (e) => {
    const card = e.target.closest(".group-card");
    if (!card) return;
    const id = card.dataset.groupId || "demo";
    window.location.href = `group.php?id=${encodeURIComponent(id)}`;
  });
}
