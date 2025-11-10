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
    actualizarMiembrosPeriodicamente();
    editRecompensas();
    initConfirmarEliminarRecompensa();
    eliminarRecompensa();
    crearRecompensaConModal();

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

function actualizarMiembrosPeriodicamente() {
    console.log("Actualizando miembros...");
    const grupoId = obtenerGrupoIdDesdeURL(); // extraé el ID del grupo desde la URL
    if (!grupoId) return;

    setInterval(() => {
        fetch(`miembros_ajax.php?id=${grupoId}`)
            .then(res => res.json())
            .then(data => renderizarMiembros(data))
            .catch(err => console.error("Error al actualizar miembros:", err));
    }, 5000); // cada 5 segundos
}

function obtenerGrupoIdDesdeURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get("id");
}

function renderizarMiembros(miembros) {
    console.log("Miembros recibidos:", miembros);
    const lista = document.getElementById("member-list");
    if (!lista) return;

    lista.innerHTML = "";
    if (miembros.length === 0) {
        lista.innerHTML = "<li class='list-group-item text-muted'>Este grupo aún no tiene miembros.</li>";
        return;
    }

    miembros.forEach(m => {
        const li = document.createElement("li");
        li.className = "list-group-item d-flex justify-content-between align-items-center";
        li.textContent = m.nombre + (m.rol === "administrador" ? " (Admin)" : "");
        lista.appendChild(li);
    });
}

function editRecompensas() {
    const editarBtns = document.querySelectorAll('.btn-outline-primary[title="Modificar"]');

    editarBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const li = btn.closest('li');
            const nombre = li.querySelector('strong').textContent.trim();
            const descripcion = li.querySelector('small')?.textContent.trim() || '';
            const costoTexto = li.querySelector('strong').nextSibling.textContent;
            const costo = parseInt(costoTexto.match(/\d+/)?.[0] || 0);
            const id = btn.getAttribute('data-id');

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-nombre').value = nombre;
            document.getElementById('edit-costo_puntos').value = costo;
            document.getElementById('edit-descripcion').value = descripcion;
        });
    });
}

function eliminarRecompensa() {
    const form = document.getElementById("formEliminarRecompensa");
    const modal = document.getElementById("modalConfirmarEliminar");

    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const id = formData.get("id_recompensa");
        const li = document.querySelector(`#reward-list li[data-id="${id}"]`);

        fetch(form.action, {
            method: "POST",
            body: formData
        })
            .then(res => res.text())
            .then(() => {
                // Fade out
                li.style.transition = "opacity 0.4s ease, height 0.4s ease";
                li.style.opacity = "0";
                li.style.height = "0";
                li.style.padding = "0";
                li.style.margin = "0";
                setTimeout(() => li.remove(), 400);

                // Cerrar modal
                const bsModal = bootstrap.Modal.getInstance(modal);
                bsModal.hide();

                // Mostrar alerta
                const alertContainer = document.querySelector(".container.mt-4");
                if (alertContainer) {
                    alertContainer.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              🗑️ Recompensa eliminada correctamente.
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
          `;
                }
            })
            .catch(err => {
                console.error("Error al eliminar recompensa:", err);
            });
    });
}

function initConfirmarEliminarRecompensa() {
    const botones = document.querySelectorAll(".btn-confirmar-eliminar");
    const inputId = document.getElementById("eliminar-id");
    const nombreSpan = document.getElementById("nombreRecompensaEliminar");

    botones.forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.getAttribute("data-id");
            const nombre = btn.getAttribute("data-nombre");

            inputId.value = id;
            nombreSpan.textContent = `"${nombre}"`;
        });
    });
}

function crearRecompensaConModal() {
    const form = document.getElementById("formCrearRecompensa");
    const modal = document.getElementById("modalCrearRecompensa");

    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        fetch(form.action, {
            method: "POST",
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Crear el nuevo <li>
                    const nuevoLi = document.createElement("li");
                    nuevoLi.className = "list-group-item d-flex justify-content-between align-items-center";
                    nuevoLi.setAttribute("data-id", data.id_recompensa);
                    nuevoLi.innerHTML = `
  <div>
    <strong>${data.titulo}</strong> - ${data.costo} pts
    ${data.descripcion ? `<br><small class="text-muted">${data.descripcion}</small>` : ""}
  </div>
  <div class="reward-actions">
    <button class="btn btn-sm btn-outline-primary admin-only me-1" title="Modificar"
      data-bs-toggle="modal" data-bs-target="#modalEditarRecompensa"
      data-id="${data.id_recompensa}">
      <i class="bi bi-pencil-square"></i>
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger admin-only btn-confirmar-eliminar"
      data-id="${data.id_recompensa}" data-nombre="${data.titulo}"
      data-bs-toggle="modal" data-bs-target="#modalConfirmarEliminar" title="Eliminar">
      <i class="bi bi-trash"></i>
    </button>
  </div>
`;


                    // Agregar al listado
                    document.getElementById("reward-list").prepend(nuevoLi);
                    editRecompensas(); // vuelve a activar el botón de editar
                    initConfirmarEliminarRecompensa(); // vuelve a activar el botón de eliminar


                    // Cerrar modal
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    bsModal.hide();
                    form.reset();

                    // Mostrar alerta
                    const alertContainer = document.querySelector(".container.mt-4");
                    if (alertContainer) {
                        alertContainer.innerHTML = `
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                🎁 Recompensa creada con éxito.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
              </div>
            `;
                    }
                } else {
                    alert("Error al crear recompensa.");
                }
            })
            .catch(err => {
                console.error("Error:", err);
            });
    });
}









