document.addEventListener("DOMContentLoaded", () => {
    handleSidebarNavigation();
    manejarSidebarResponsivo();
    configurarModalExpulsion();
});

// --- Navegación lateral ---
function handleSidebarNavigation() {
    const menuItems = document.querySelectorAll(".sidebar-menu .menu-item");
    const sections = document.querySelectorAll(".content-section");

    menuItems.forEach(item => {
        item.addEventListener("click", (e) => {
            const isExternal = item.dataset.external === "true" || item.classList.contains("external");
            if (isExternal) return;

            e.preventDefault();
            menuItems.forEach(i => i.classList.remove("active"));
            sections.forEach(s => s.classList.remove("active"));

            item.classList.add("active");
            const target = item.dataset.section;
            if (target) {
                const el = document.getElementById(`${target}-section`);
                if (el) el.classList.add("active");
            }
        });
    });
}

// --- Sidebar móvil ---
function manejarSidebarResponsivo() {
    const sidebar = document.getElementById("main-sidebar");

    function crearBotonMenuMovil() {
        if (document.querySelector(".mobile-menu-btn")) return;
        const boton = document.createElement("button");
        boton.className = "mobile-menu-btn";
        boton.innerHTML = '<i class="bi bi-list"></i>';
        Object.assign(boton.style, {
            position: "fixed",
            top: "20px",
            left: "20px",
            zIndex: 1001,
            width: "44px",
            height: "44px",
            background: "rgba(255,255,255,0.9)",
            border: "1px solid rgba(139,92,246,0.2)",
            color: "#8b5cf6",
            borderRadius: "8px",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            cursor: "pointer",
            backdropFilter: "blur(10px)"
        });
        document.body.appendChild(boton);

        boton.addEventListener("click", () => {
            sidebar.style.transform = sidebar.style.transform === "translateX(0px)" ? "translateX(-100%)" : "translateX(0px)";
        });
    }

    function eliminarBotonMenuMovil() {
        const boton = document.querySelector(".mobile-menu-btn");
        if (boton) boton.remove();
    }

    function ajustarSidebar() {
        if (window.innerWidth <= 768) {
            sidebar.style.transform = "translateX(-100%)";
            crearBotonMenuMovil();
        } else {
            sidebar.style.transform = "translateX(0)";
            eliminarBotonMenuMovil();
        }
    }

    ajustarSidebar();
    window.addEventListener("resize", ajustarSidebar);
}

function configurarModalExpulsion() {
    const expulsarModal = document.getElementById("expulsarModal");
    if (!expulsarModal) return;

    expulsarModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        const nombre = button.getAttribute("data-nombre");
        const idUsuario = button.getAttribute("data-id");

        document.getElementById("nombreMiembro").textContent = nombre;
        document.getElementById("idUsuarioExpulsar").value = idUsuario;
    });
}
