document.addEventListener("DOMContentLoaded", () => {
    initGroupPage();
});

function initGroupPage() {
    handleSidebarNavigation();
    manejarSidebarResponsivo();
    configurarModalExpulsion();
    configurarModalEliminarTarea();
    configurarModalEditarTarea();
    configurarBotonCompletarTarea();
    mostrarToastExpulsion();
    configurarBotonCopiarCodigo();
}

// --- Navegación lateral ---
function handleSidebarNavigation() {
    const menuItems = document.querySelectorAll(".sidebar-menu .menu-item");
    const sections = document.querySelectorAll(".content-section");

    menuItems.forEach(item => {
        item.addEventListener("click", (e) => {
            const isExternal = item.dataset.external === "true" || item.classList.contains("external");
            if (isExternal) return;

            e.preventDefault();

            // Quitar 'active' de todos
            menuItems.forEach(i => i.classList.remove("active"));
            sections.forEach(s => s.classList.remove("active"));

            // Activar el clicado
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

// --- Modal expulsar miembro ---
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

// --- Modal eliminar tarea ---
function configurarModalEliminarTarea() {
    const eliminarTareaModal = document.getElementById("eliminarTareaModal");
    if (!eliminarTareaModal) return;

    eliminarTareaModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        const idTarea = button.getAttribute("data-id");
        const titulo = button.getAttribute("data-titulo");

        document.getElementById("idTareaEliminar").value = idTarea;
        document.getElementById("tareaTitulo").textContent = titulo;
    });
}

// --- Modal editar tarea ---
function configurarModalEditarTarea() {
    const editarTareaModal = document.getElementById("editarTareaModal");
    if (!editarTareaModal) return;

    editarTareaModal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;

        document.getElementById("editIdTarea").value = button.getAttribute("data-id");
        document.getElementById("editTitulo").value = button.getAttribute("data-titulo");
        document.getElementById("editDescripcion").value = button.getAttribute("data-descripcion");
        document.getElementById("editPuntos").value = button.getAttribute("data-puntos");
        document.getElementById("editFecha").value = button.getAttribute("data-fecha");

        // Seleccionar el asignado
        const asignado = button.getAttribute("data-asignado");
        const select = document.getElementById("editAsignado");
        if (select) {
            [...select.options].forEach(opt => {
                opt.selected = (opt.value === asignado);
            });
        }
    });
}

//completar tarea
function configurarBotonCompletarTarea() {
    document.querySelectorAll(".complete-task-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const idTarea = btn.getAttribute("data-id");
            const idGrupo = new URLSearchParams(window.location.search).get("id");

            if (!idTarea || !idGrupo) {
                console.error("Faltan datos para completar tarea");
                return;
            }

            fetch("../tareas/completar_tarea.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_tarea=${encodeURIComponent(idTarea)}&id_grupo=${encodeURIComponent(idGrupo)}`
            })
            .then(res => res.text())
            .then(data => {
                console.log("Respuesta completar_tarea:", data);
                window.location.href = `ver_grupo.php?id=${idGrupo}&section=tareas`;
            })
            .catch(err => console.error("Error al completar tarea:", err));
        });
    });
}

function mostrarToastExpulsion() {
    const toastEl = document.getElementById("expulsionToast");
    if (toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        limpiarParametrosToast();
    }
}

function limpiarParametrosToast() {
    const url = new URL(window.location.href);
    if (url.searchParams.has("expulsion")) {
        url.searchParams.delete("expulsion");
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
}

function configurarBotonCopiarCodigo() {
    const btn = document.getElementById("btnCopiarCodigo");
    if (!btn) return;

    btn.addEventListener("click", () => {
        const codigo = btn.getAttribute("data-codigo");
        navigator.clipboard.writeText(codigo).then(() => {
            const toastEl = document.getElementById("copiadoToast");
            if (toastEl) {
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            }
        }).catch(err => {
            console.error("Error al copiar:", err);
        });
    });
}

