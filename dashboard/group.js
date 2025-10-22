document.addEventListener("DOMContentLoaded", () => {
    handleSidebarNavigation();
    setupDemoData();
    manejarSidebarResponsivo();
});

// --- Navegación lateral ---
function handleSidebarNavigation() {
    const menuItems = document.querySelectorAll(".sidebar-menu .menu-item");
    const sections = document.querySelectorAll(".content-section");

    menuItems.forEach(item => {
        item.addEventListener("click", (e) => {
            // Si el item está marcado como externo → permitir comportamiento por defecto (navegación)
            const isExternal = item.dataset.external === "true" || item.classList.contains("external");
            if (isExternal) {
                return; // no preventDefault -> navegamos normalmente
            }

            e.preventDefault(); // sólo evitamos navegación para items internos
            // resetear clases
            menuItems.forEach(i => i.classList.remove("active"));
            sections.forEach(s => s.classList.remove("active"));

            // activar el actual
            item.classList.add("active");
            const target = item.dataset.section;
            if (target) {
                const el = document.getElementById(`${target}-section`);
                if (el) el.classList.add("active");
            }
        });
    });
}

// --- Datos demo y control de roles ---
function setupDemoData() {
    const urlParams = new URLSearchParams(window.location.search);
    const groupId = urlParams.get("id") || "demo";

    const groups = {
        demo: { name: "Grupo Demo", category: "Familia", members: 4, role: "admin" },
        1: { name: "Mi Familia", category: "Familia", members: 4, role: "admin" },
        2: { name: "Equipo Marketing", category: "Laboral", members: 8, role: "admin" },
        3: { name: "Metas Personales", category: "Individual", members: 1, role: "admin" },
        4: { name: "Desarrollo Web", category: "Laboral", members: 20, role: "colab" },
        5: { name: "Casa de los Abuelos", category: "Familia", members: 5, role: "colab" },
        6: { name: "Proyecto Alpha", category: "Laboral", members: 12, role: "colab" }
    };

    const group = groups[groupId] || groups.demo;

    // Sidebar y header
    document.getElementById("group-sidebar-title").textContent = group.name;
    document.querySelector(".page-title").innerHTML = `<i class="bi bi-people-fill"></i> ${group.name}`;
    document.getElementById("group-category").textContent = group.category.toUpperCase();
    document.getElementById("group-members-count").textContent = `${group.members} miembros`;

    // Botones según rol
    const createTaskBtn = document.getElementById("btn-create-task");
    const createRewardBtn = document.getElementById("btn-create-reward");
    const role = group.role;

    createTaskBtn.style.display = role === "admin" ? "inline-flex" : "none";
    createRewardBtn.style.display = role === "admin" ? "inline-flex" : "none";

    document.querySelectorAll(".admin-only").forEach(btn => btn.style.display = role === "admin" ? "inline-flex" : "none");

    // Marcar tarea como completada visible para todos
    document.querySelectorAll(".complete-task-btn").forEach(btn => btn.style.display = "inline-flex");

    // Aprobar Tareas visible solo admin
    if (role !== "admin") {
        const approveMenu = document.querySelector('[data-section="aprobar-tareas"]');
        if (approveMenu) approveMenu.style.display = "none";
    }

    // Eventos demo
    createTaskBtn.addEventListener("click", () => alert("Abrir modal para crear tarea"));
    createRewardBtn.addEventListener("click", () => alert("Abrir modal para crear recompensa"));

    document.addEventListener("click", e => {
        if (e.target.closest(".complete-task-btn")) alert("Tarea marcada como completada");
        if (e.target.closest(".admin-only")) alert("Acción admin (modificar/eliminar/expulsar)");
        if (e.target.closest("#approve-task-list .btn")) alert("Tarea aprobada");
        if (e.target.closest(".remove-member-btn")) {
            const memberName = e.target.closest("li").firstChild.textContent.trim();
            if (confirm(`¿Seguro que quieres echar a ${memberName} del grupo?`)) {
                alert(`${memberName} ha sido expulsado (demo)`);
                e.target.closest("li").remove();
            }
        }
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
