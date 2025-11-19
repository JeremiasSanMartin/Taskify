document.addEventListener("DOMContentLoaded", () => {
    initGroupPage();

});

function initGroupPage() {

    const role = document.body.dataset.role || "colaborador";

    if (role === "admin") {
        cargarConfiguracionAdministrador();
        editarGrupoDesdeConfiguracion();
        sincronizarModalEditarGrupo();
    } else {
        cargarConfiguracionColaborador();
    }
    refrescarTodoConBoton();

    //MIEMBROS
    handleSidebarNavigation();
    manejarSidebarResponsivo();
    configurarModalExpulsion();
    mostrarToastExpulsion();
    configurarBotonCopiarCodigo();

    //TAREAS
    crearTareaConModal();
    configurarModalEliminarTarea();
    configurarModalEditarTarea();
    editarTareaConModal();
    eliminarTareaConModal();
    configurarBotonCompletarTarea();
    configurarBotonesAprobarRechazar()

    //RECOMPENSAS
    crearRecompensa();
    controlFormCrearRecompensa();
    prellenarModalEditarRecompensa();
    editarRecompensa();
    eliminarRecompensa();
    abrirModalConfirmCanje();
    canjearRecompensa();
    mostrarAlerta();
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

function editarGrupoDesdeConfiguracion() {
    const form = document.getElementById("formEditarGrupo");
    const modal = document.getElementById("editarGrupoModal");
    if (!form || !modal) return;

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        try {
            const res = await fetch(form.action, { method: "POST", body: formData });
            const raw = await res.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch {
                console.error("Respuesta no JSON:", raw);
                mostrarAlerta(`❌ Respuesta no válida del servidor (HTTP ${res.status}).`, "danger");
                return;
            }

            if (!res.ok || !data.success) {
                const errorMsg = data?.error || `HTTP ${res.status}`;
                mostrarAlerta("❌ Error al editar grupo: " + errorMsg, "danger");
                return;
            }

            const categoriaBonita = data.tipo ? (data.tipo[0].toUpperCase() + data.tipo.slice(1)) : "";
            const cont = document.getElementById("configuracion-container");
            if (cont) {
                const nombreEl = cont.querySelector('[data-field="nombre"]');
                const tipoEl = cont.querySelector('[data-field="tipo"]');
                const descItem = cont.querySelector('[data-field="descripcion"]');

                if (nombreEl) nombreEl.textContent = data.nombre;
                if (tipoEl) tipoEl.textContent = categoriaBonita;

                const desc = (data.descripcion || "").trim();
                if (desc) {
                    if (descItem) {
                        const p = descItem.querySelector("p");
                        if (p) {
                            p.textContent = desc;
                        } else {
                            const span = descItem.querySelector("span");
                            if (span) span.textContent = desc;
                            else {
                                const nuevoP = document.createElement("p");
                                nuevoP.className = "mb-0";
                                nuevoP.textContent = desc;
                                descItem.appendChild(nuevoP);
                            }
                        }
                    } else {
                        const nuevoDiv = document.createElement("div");
                        nuevoDiv.className = "config-description mt-3";
                        nuevoDiv.setAttribute("data-field", "descripcion");
                        nuevoDiv.innerHTML = `
                <h6 class="text-muted mb-2">Descripción</h6>
                <p class="mb-0">${desc}</p>
              `;
                        cont.insertBefore(nuevoDiv, cont.querySelector(".config-actions-footer"));
                    }
                } else if (descItem) {
                    descItem.remove();
                }
            }

            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
            form.reset();
            mostrarAlerta("✏️ Grupo editado correctamente", "info");
        } catch (err) {
            console.error("Error en fetch editar_grupo:", err);
            mostrarAlerta("❌ Error inesperado al editar grupo", "danger");
        }
    });
}

function sincronizarModalEditarGrupo() {
    const modal = document.getElementById("editarGrupoModal");
    if (!modal) return;

    modal.addEventListener("show.bs.modal", () => {
        const cont = document.getElementById("configuracion-container");
        if (!cont) return;

        const nombreEl = cont.querySelector('[data-field="nombre"]');
        const tipoEl = cont.querySelector('[data-field="tipo"]');
        const descItem = cont.querySelector('[data-field="descripcion"]');

        const nombre = nombreEl?.textContent?.trim() || "";
        const tipo = tipoEl?.textContent?.trim().toLowerCase() || "";
        const desc = descItem?.querySelector("p")?.textContent?.trim()
            || descItem?.querySelector("span")?.textContent?.trim()
            || "";

        // Rellenar campos del modal con valores actuales
        const nombreInput = modal.querySelector("#nombreGrupo");
        const tipoSelect = modal.querySelector("#tipoGrupo");
        const descTextarea = modal.querySelector("#descripcionGrupo");

        if (nombreInput) nombreInput.value = nombre;
        if (tipoSelect && tipo) tipoSelect.value = tipo; // familiar/laboral/personal
        if (descTextarea) descTextarea.value = desc;
    });
}


function crearTareaConModal() {
    document.addEventListener("submit", async (e) => {
        const form = e.target;
        if (!form || form.id !== "formCrearTarea") return;

        e.preventDefault();

        const modal = document.getElementById("crearTareaModal");
        const formData = new FormData(form);

        try {
            const res = await fetch(form.action, { method: "POST", body: formData });
            const raw = await res.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch {
                console.error("Respuesta no JSON:", raw);
                mostrarAlerta(`❌ Respuesta no válida del servidor (HTTP ${res.status}).`, "danger");
                return;
            }
            if (!res.ok || !data.success) {
                const errorMsg = data?.error || `HTTP ${res.status}`;
                mostrarAlerta(`❌ Error al crear tarea: ${errorMsg}`, "danger");
                return;
            }

            // Eliminar placeholder si existe
            const noTasksMsg = document.querySelector("#task-list .list-group-item");
            if (noTasksMsg && noTasksMsg.textContent.includes("No hay tareas pendientes")) {
                noTasksMsg.remove();
            }

            const asignadoNombre = data.asignado || "Sin asignar";
            const asignadoId = data.asignado_id || "";

            const nuevoLi = document.createElement("li");
            nuevoLi.className = "list-group-item d-flex justify-content-between align-items-start flex-column flex-md-row";
            nuevoLi.innerHTML = `
          <div>
            <strong>${data.titulo}</strong> - ${data.puntos} pts<br>
            <small>${data.descripcion}</small><br>
            <small class="text-muted">Fecha límite: ${data.fecha_limite}</small><br>
            <small class="text-muted">Asignado a: ${asignadoNombre}</small>
          </div>
          <div class="task-actions mt-2 mt-md-0">
            <button class="btn btn-sm btn-outline-primary admin-only me-1"
                    data-bs-toggle="modal" data-bs-target="#editarTareaModal"
                    data-id="${data.id_tarea}" data-titulo="${data.titulo}"
                    data-descripcion="${data.descripcion}" data-puntos="${data.puntos}"
                    data-fecha="${data.fecha_limite}" data-asignado="${asignadoNombre}"
                    data-asignado-id="${asignadoId}"
                    title="Modificar" aria-label="Modificar tarea ${data.titulo}">
              <i class="bi bi-pencil-square"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger admin-only"
                    data-bs-toggle="modal" data-bs-target="#eliminarTareaModal"
                    data-id="${data.id_tarea}" data-titulo="${data.titulo}"
                    title="Eliminar" aria-label="Eliminar tarea ${data.titulo}">
              <i class="bi bi-trash"></i>
            </button>
            <button class="btn btn-sm btn-outline-success complete-task-btn"
                    data-id="${data.id_tarea}" title="Completada" aria-label="Marcar completada ${data.titulo}">
              <i class="bi bi-check-circle"></i>
            </button>
          </div>
        `;
            document.getElementById("task-list").prepend(nuevoLi);
            asegurarMensajeVacio("task-list", "tasks-empty-placeholder", "No hay tareas pendientes en este grupo.");

            // Cerrar modal y resetear form
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
            form.reset();

            // Activar sección Tareas
            document.querySelectorAll(".sidebar-menu .menu-item").forEach(i => i.classList.remove("active"));
            document.querySelectorAll(".content-section").forEach(s => s.classList.remove("active"));
            const tareasItem = document.querySelector('.sidebar-menu .menu-item[data-section="tareas"]');
            const tareasSection = document.getElementById("tareas-section");
            if (tareasItem) tareasItem.classList.add("active");
            if (tareasSection) tareasSection.classList.add("active");

            mostrarAlerta("✅ Tarea creada con éxito.", "success");
        } catch (err) {
            console.error("Error en crearTareaConModal:", err);
            mostrarAlerta("❌ Error inesperado al crear tarea.", "danger");
        }
    });
}

function eliminarTareaConModal() {
    const form = document.getElementById("formEliminarTarea");
    const modal = document.getElementById("eliminarTareaModal");
    if (!form) return;

    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const id = formData.get("id_tarea");

        fetch(form.action, { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.error || "Error al eliminar tarea.");
                    return;
                }

                // Encontrar y eliminar el <li>
                const actionBtn = document.querySelector(`#task-list .task-actions [data-id="${id}"]`);
                const li = actionBtn ? actionBtn.closest("li") : null;
                if (li) {
                    li.style.transition = "opacity 0.25s ease";
                    li.style.opacity = "0";
                    setTimeout(() => {
                        li.remove();
                        asegurarMensajeVacio("task-list", "tasks-empty-placeholder", "No hay tareas pendientes en este grupo.");
                    }, 250);
                }

                // Si la lista queda vacía, mostrar mensaje
                const list = document.getElementById("task-list");
                if (list && list.children.length === 0) {
                    const msg = document.createElement("li");
                    msg.className = "list-group-item";
                    msg.textContent = "No hay tareas pendientes en este grupo.";
                    list.appendChild(msg);
                }

                // Cerrar modal y resetear
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
                form.reset();

                // Alerta
                const alertContainer = document.querySelector(".container.mt-4");
                if (alertContainer) {
                    alertContainer.innerHTML = `
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    🗑️ Tarea eliminada correctamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>`;
                }
            })
            .catch(err => console.error("Error:", err));
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

function editarTareaConModal() {
    const form = document.getElementById("formEditarTarea");
    const modal = document.getElementById("editarTareaModal");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        try {
            const res = await fetch(form.action, { method: "POST", body: formData });
            const raw = await res.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch {
                console.error("Respuesta no JSON:", raw);
                mostrarAlerta(`❌ Respuesta no válida del servidor (HTTP ${res.status}).`, "danger");
                return;
            }
            if (!res.ok || !data.success) {
                const errorMsg = data?.error || `HTTP ${res.status}`;
                mostrarAlerta(`❌ Error al editar tarea: ${errorMsg}`, "danger");
                return;
            }

            // Encontrar el <li> por cualquier botón con data-id=id_tarea
            const actionBtn = document.querySelector(`#task-list .task-actions [data-id="${data.id_tarea}"]`);
            const li = actionBtn ? actionBtn.closest("li") : null;
            if (!li) {
                mostrarAlerta("❌ No se encontró la tarjeta de la tarea para actualizar.", "danger");
                return;
            }

            const asignadoNombre = data.asignado || "Sin asignar";
            const left = li.querySelector("div:first-child");
            if (left) {
                left.innerHTML = `
            <strong>${data.titulo}</strong> - ${data.puntos} pts<br>
            <small>${data.descripcion}</small><br>
            <small class="text-muted">Fecha límite: ${data.fecha_limite}</small><br>
            <small class="text-muted">Asignado a: ${asignadoNombre}</small>
          `;
            }

            // Refrescar data-* en los botones de acción, incluyendo data-asignado-id
            const asignadoId = data.asignado_id || "";
            li.querySelectorAll(".task-actions [data-id]").forEach(btn => {
                btn.setAttribute("data-titulo", data.titulo);
                btn.setAttribute("data-descripcion", data.descripcion);
                btn.setAttribute("data-puntos", data.puntos);
                btn.setAttribute("data-fecha", data.fecha_limite);
                btn.setAttribute("data-asignado", asignadoNombre || "");
                btn.setAttribute("data-asignado-id", asignadoId);
            });

            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
            form.reset();

            mostrarAlerta("✏️ Tarea editada con éxito.", "info");
        } catch (err) {
            console.error("Error en editarTareaConModal:", err);
            mostrarAlerta("❌ Error inesperado al editar tarea.", "danger");
        }
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

        const asignadoId = button.getAttribute("data-asignado-id") || "";
        const select = document.getElementById("editAsignado");
        if (select) {
            // Si asignadoId vacío, seleccionar opción '' (Ninguno)
            [...select.options].forEach(opt => { opt.selected = (opt.value === asignadoId); });
            if (!asignadoId) {
                const none = select.querySelector('option[value=""]');
                if (none) none.selected = true;
            }
        }
    });
}

//completar tarea
function configurarBotonCompletarTarea() {
    const taskList = document.getElementById("task-list");
    if (!taskList) return;

    taskList.addEventListener("click", (e) => {
        const btn = e.target.closest(".complete-task-btn");
        if (!btn) return;

        const idTarea = btn.dataset.id;
        const idGrupo = new URLSearchParams(window.location.search).get("id");

        if (!idTarea || !idGrupo) {
            console.error("Faltan datos para completar tarea");
            return;
        }

        fetch("../../administrador/tareas/completar_tarea.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id_tarea=${encodeURIComponent(idTarea)}&id_grupo=${encodeURIComponent(idGrupo)}`
        })
            .then(res => res.json())
            .then(data => {
                console.log("Respuesta completar_tarea:", data);
                if (data.success) {
                    // refrescar lista sin recargar toda la página
                    refrescarTodoConBoton();
                } else {
                    alert(data.error || "Error al completar tarea");
                }
            })
            .catch(err => console.error("Error al completar tarea:", err));
    });
}
function configurarBotonesAprobarRechazar() {
    const lista = document.getElementById("approve-task-list");
    if (!lista) return;

    lista.addEventListener("click", (e) => {
        const approveBtn = e.target.closest(".approve-task-btn");
        const rejectBtn = e.target.closest(".reject-task-btn");
        const idGrupo = new URLSearchParams(window.location.search).get("id");

        if (approveBtn) {
            const idTarea = approveBtn.dataset.id;
            fetch("../tareas/aprobar_tarea.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_tarea=${encodeURIComponent(idTarea)}&id_grupo=${encodeURIComponent(idGrupo)}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) refrescarTodoConBoton();
                    else alert(data.error || "Error al aprobar tarea");
                })
                .catch(err => console.error("Error al aprobar tarea:", err));
        }

        if (rejectBtn) {
            const idTarea = rejectBtn.dataset.id;
            fetch("../tareas/rechazar_tarea.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_tarea=${encodeURIComponent(idTarea)}&id_grupo=${encodeURIComponent(idGrupo)}`
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) refrescarTodoConBoton();
                    else alert(data.error || "Error al rechazar tarea");
                })
                .catch(err => console.error("Error al rechazar tarea:", err));
        }
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

    btn.addEventListener("click", async () => {
        const codigo = btn.getAttribute("data-codigo");
        try {
            await navigator.clipboard.writeText(codigo);
            mostrarAlerta("🔗 Código copiado al portapapeles", "success");
        } catch {
            mostrarAlerta("❌ No se pudo copiar el código", "danger");
        }
    });
}

function obtenerGrupoIdDesdeURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get("id");
}

function renderizarMiembros(miembros, isAdmin) {
    const lista = document.getElementById("member-list");
    if (!lista) return;

    lista.innerHTML = "";
    if (!miembros || miembros.length === 0) {
        lista.innerHTML = "<li class='list-group-item text-muted'>Este grupo aún no tiene miembros.</li>";
        return;
    }

    miembros.forEach(m => {
        const li = document.createElement("li");
        li.className = "list-group-item d-flex justify-content-between align-items-center";

        const nombreSpan = document.createElement("span");
        nombreSpan.textContent = m.nombre + (m.rol === "administrador" ? " (Admin)" : "");
        li.appendChild(nombreSpan);

        if (isAdmin && m.rol !== "administrador") {
            const btn = document.createElement("button");
            btn.className = "btn btn-sm btn-outline-danger remove-member-btn admin-only";
            btn.setAttribute("data-bs-toggle", "modal");
            btn.setAttribute("data-bs-target", "#expulsarModal");
            btn.setAttribute("data-nombre", m.nombre);
            btn.setAttribute("data-id", m.id_usuario);
            btn.innerHTML = '<i class="bi bi-person-x"></i>';
            li.appendChild(btn);
        }

        lista.appendChild(li);
    });
}

function renderizarTareas(tareas, isAdmin, usuarioId) {
    const lista = document.getElementById("task-list");
    if (!lista) return;

    lista.innerHTML = "";

    // 🔹 Filtrar tareas si no es admin
    if (!isAdmin) {
        tareas = tareas.filter(t => !t.asignado_id || t.asignado_id === usuarioId);
    }

    if (!tareas || tareas.length === 0) {
        lista.innerHTML = "<li class='list-group-item text-muted'>No hay tareas pendientes en este grupo.</li>";
        return;
    }

    tareas.forEach(t => {
        const li = document.createElement("li");
        li.className = "list-group-item d-flex justify-content-between align-items-start flex-column flex-md-row";

        let acciones = `
            <button class="btn btn-sm btn-outline-success complete-task-btn" data-id="${t.id_tarea}" title="Completada">
                <i class="bi bi-check-circle"></i>
            </button>
        `;

        if (isAdmin) {
            acciones = `
                <button class="btn btn-sm btn-outline-primary admin-only me-1"
                    data-bs-toggle="modal" data-bs-target="#editarTareaModal"
                    data-id="${t.id_tarea}" data-titulo="${t.titulo}"
                    data-descripcion="${t.descripcion}" data-puntos="${t.puntos}"
                    data-fecha="${t.fecha_limite}" data-asignado="${t.asignado || ''}"
                    data-asignado-id="${t.asignado_id || ''}"
                    title="Modificar">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger admin-only"
                    data-bs-toggle="modal" data-bs-target="#eliminarTareaModal"
                    data-id="${t.id_tarea}" data-titulo="${t.titulo}" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
                ${acciones}
            `;
        }

        li.innerHTML = `
            <div>
                <strong>${t.titulo}</strong> - ${t.puntos} pts<br>
                <small>${t.descripcion}</small><br>
                <small class="text-muted">Fecha límite: ${t.fecha_limite}</small><br>
                <small class="text-muted">Asignado a: ${t.asignado || 'Sin asignar'}</small>
            </div>
            <div class="task-actions mt-2 mt-md-0">
                ${acciones}
            </div>
        `;
        lista.appendChild(li);
    });
}


function renderizarRecompensas(recompensas, isAdmin) {
    const lista = document.getElementById("reward-list");
    if (!lista) return;

    lista.innerHTML = "";
    if (!recompensas || recompensas.length === 0) {
        lista.innerHTML = "<li class='list-group-item text-muted'>No hay recompensas disponibles.</li>";
        return;
    }

    recompensas.forEach(r => {
        if (r.disponibilidad == -1) return; // ocultar eliminadas

        const li = document.createElement("li");
        li.className = `list-group-item d-flex justify-content-between align-items-start flex-column flex-md-row ${r.disponibilidad == 0 ? 'text-muted bg-light' : ''}`;
        li.dataset.id = r.id_recompensa;

        // Bloque de acciones según rol
        let acciones = "";
        if (isAdmin) {
            acciones = `
                <button class="btn btn-sm btn-outline-primary admin-only me-1" title="Modificar"
                    data-bs-toggle="modal" data-bs-target="#modalEditarRecompensa"
                    data-id="${r.id_recompensa}" data-nombre="${r.titulo}"
                    data-costo="${r.costo}" data-descripcion="${r.descripcion || ''}"
                    data-disponibilidad="${r.disponibilidad}">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger admin-only" title="Eliminar"
                    data-id="${r.id_recompensa}" data-nombre="${r.titulo}"
                    data-bs-toggle="modal" data-bs-target="#modalEliminarRecompensa"
                    ${r.disponibilidad == 0 ? 'disabled' : ''}>
                    <i class="bi bi-trash"></i>
                </button>
            `;
        } else {
            acciones = `
                <button class="btn btn-sm btn-outline-primary mt-2 mt-md-0" title="Canjear"
                    data-id="${r.id_recompensa}" data-nombre="${r.titulo}"
                    ${r.disponibilidad <= 0 ? 'disabled' : ''}>
                    <i class="bi bi-cart-check"></i> Canjear
                </button>
            `;
        }

        li.innerHTML = `
            <div>
                <strong>${r.titulo}</strong> - ${r.costo} pts<br>
                ${r.descripcion ? `<small class="text-muted">${r.descripcion}</small><br>` : ""}
                ${r.disponibilidad > 0
                ? `<small class="text-muted">Stock: <span class="stock-span">${r.disponibilidad}</span></small>`
                : `<span class="badge bg-secondary">No disponible</span>`}
            </div>
            <div class="reward-actions">
                ${acciones}
            </div>
        `;
        lista.appendChild(li);
    });
}

function renderizarHistorial(historial) {
    const contenedor = document.getElementById("historial-list");
    if (!contenedor) return;

    if (!historial || historial.length === 0) {
        contenedor.innerHTML = "<p class='text-muted'>Todavía no hay actividad registrada.</p>";
        return;
    }

    let html = `
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Tarea/Recompensa</th>
                    <th>Fecha</th>
                    <th>Puntos</th>
                </tr>
            </thead>
            <tbody>
    `;

    historial.forEach(h => {
        let accion;
        switch (parseInt(h.estadoTarea)) {
            case 0: accion = "Rechazó la tarea"; break;
            case 1: accion = "Marcó como realizada"; break;
            case 2: accion = "Aprobó la tarea"; break;
            case 3: accion = "Eliminó la tarea"; break;
            case 4: accion = "Editó la tarea"; break;
            case 5: accion = "Creó la tarea"; break;
            case 10: accion = "Creó la recompensa"; break;
            case 11: accion = "Editó la recompensa"; break;
            case 12: accion = "Eliminó la recompensa"; break;
            default: accion = "Acción desconocida";
        }

        const titulo = h.recompensa || h.tarea || 'Sin título';
        const puntos = h.puntosOtorgados > 0
            ? `<span class="badge bg-success">${h.puntosOtorgados} pts</span>`
            : "-";

        html += `
            <tr>
                <td>${h.usuario}</td>
                <td>${accion}</td>
                <td>${titulo}</td>
                <td>${h.fecha}</td>
                <td class="text-center">${puntos}</td>
            </tr>
        `;
    });

    html += "</tbody></table>";
    contenedor.innerHTML = html;
}
function renderizarAprobarTareas(tareas_realizadas) {
    const lista = document.getElementById("approve-task-list");
    if (!lista) return;

    lista.innerHTML = "";
    if (!tareas_realizadas || tareas_realizadas.length === 0) {
        lista.innerHTML = "<li class='list-group-item text-muted'>No hay tareas marcadas como realizadas.</li>";
        return;
    }

    tareas_realizadas.forEach(t => {
        const li = document.createElement("li");
        li.className = "list-group-item d-flex justify-content-between align-items-start flex-column flex-md-row";
        li.innerHTML = `
            <div>
                <strong>${t.titulo}</strong> - ${t.puntos} pts<br>
                <small>${t.descripcion}</small><br>
                <small class="text-muted">Asignado a: ${t.asignado || 'Desconocido'}</small><br>
                ${t.fecha_entrega
                ? `<small class="text-muted">Entregada: ${t.fecha_entrega}</small>`
                : `<small class="text-muted">Fecha límite: ${t.fecha_limite}</small>`}
            </div>
            <div class="task-actions mt-2 mt-md-0">
                <button class="btn btn-sm btn-outline-success approve-task-btn" data-id="${t.id_tarea}" title="Aprobar">
                    <i class="bi bi-check-circle"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger reject-task-btn ms-2" data-id="${t.id_tarea}" title="Rechazar">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        `;
        lista.appendChild(li);
    });
}


function prellenarModalEditarRecompensa() {
    const modalEditar = document.getElementById("modalEditarRecompensa");

    if (!modalEditar) return;

    modalEditar.addEventListener("show.bs.modal", (event) => {
        const button = event.relatedTarget;
        document.getElementById("edit-id").value = button.dataset.id;
        document.getElementById("edit-nombre").value = button.dataset.nombre;
        document.getElementById("edit-costo").value = button.dataset.costo;
        document.getElementById("edit-descripcion").value = button.dataset.descripcion;
        document.getElementById("edit-disponibilidad").value = button.dataset.disponibilidad;
        ;
    });
}

function editarRecompensa() {
    const formEditar = document.getElementById("formEditarRecompensa");

    if (formEditar) {
        formEditar.addEventListener("submit", async (e) => {
            e.preventDefault();

            const formData = new FormData(formEditar);
            try {
                const resp = await fetch("../../administrador/recompensas/editar_recompensa.php", {
                    method: "POST",
                    body: formData
                });
                const data = await resp.json();

                if (data.success) {
                    // actualizar el li en la lista
                    const li = document.querySelector(`#reward-list li[data-id="${data.id_recompensa}"]`);
                    if (li) {
                        // actualizar título
                        li.querySelector("strong").textContent = data.titulo;

                        // actualizar costo
                        const costoSpan = li.querySelector(".points");
                        if (costoSpan) {
                            costoSpan.textContent = data.costo + " pts";
                        }

                        // limpiar descripción, stock/badge y saltos de línea
                        li.querySelectorAll("small.text-muted, span.badge, br").forEach(el => el.remove());


                        // agregar nueva descripción si existe
                        if (data.descripcion) {
                            const br = document.createElement("br");
                            const desc = document.createElement("small");
                            desc.classList.add("text-muted");
                            desc.textContent = data.descripcion;
                            li.querySelector("div").appendChild(br);
                            li.querySelector("div").appendChild(desc);
                        }

                        // actualizar disponibilidad visual
                        if (data.disponibilidad > 0) {
                            // quitar estilos apagados
                            li.classList.remove("text-muted", "opacity-75", "bg-light");

                            const stock = document.createElement("small");
                            stock.classList.add("text-muted");
                            stock.textContent = "Stock: " + data.disponibilidad;
                            li.querySelector("div").appendChild(document.createElement("br"));
                            li.querySelector("div").appendChild(stock);
                        } else {
                            // aplicar estilos apagados
                            li.classList.add("text-muted", "opacity-75", "bg-light");

                            const badge = document.createElement("span");
                            badge.classList.add("badge", "bg-secondary");
                            badge.textContent = "No disponible";
                            li.querySelector("div").appendChild(document.createElement("br"));
                            li.querySelector("div").appendChild(badge);
                        }

                        // 👉 actualizar atributos del botón editar
                        const btnEditar = li.querySelector("button[title='Modificar']");
                        if (btnEditar) {
                            btnEditar.dataset.id = data.id_recompensa;
                            btnEditar.dataset.nombre = data.titulo;
                            btnEditar.dataset.costo = data.costo;
                            btnEditar.dataset.descripcion = data.descripcion || '';
                            btnEditar.dataset.disponibilidad = data.disponibilidad;
                        }
                    }

                    // cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById("modalEditarRecompensa"));
                    modal.hide();

                    mostrarAlerta("✅ Recompensa editada correctamente", "success");
                } else {
                    mostrarAlerta("❌ Error: " + data.error, "danger");
                }
            } catch (err) {
                mostrarAlerta("❌ Error inesperado al editar", "danger");
            }
        });
    }
}




function eliminarRecompensa() {
    const formEliminar = document.getElementById("formEliminarRecompensa");

    // 👉 capturar el click en el botón eliminar y abrir el modal con el id
    document.addEventListener("click", (e) => {
        const btn = e.target.closest("button[title='Eliminar']");
        if (btn) {
            document.getElementById("delete-id").value = btn.dataset.id;
            document.getElementById("delete-nombre").value = btn.dataset.nombre;
        }
    });

    // 👉 manejar el submit del modal
    if (formEliminar) {
        formEliminar.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(formEliminar);

            try {
                const resp = await fetch("../../administrador/recompensas/eliminar_recompensa.php", {
                    method: "POST",
                    body: formData
                });
                const data = await resp.json();

                if (data.success) {
                    const li = document.querySelector(`#reward-list li[data-id="${data.id_recompensa}"]`);
                    if (li) {
                        if (data.disponibilidad === -1) {
                            // eliminada definitivamente → remover del DOM
                            li.remove();
                        } else if (data.disponibilidad === 0) {
                            // desactivada → actualizar visualmente
                            li.classList.add("text-muted", "opacity-75", "bg-light");
                            li.querySelectorAll("small.text-muted, span.badge, br").forEach(el => el.remove());

                            const badge = document.createElement("span");
                            badge.classList.add("badge", "bg-secondary");
                            badge.textContent = "No disponible";
                            li.querySelector("div").appendChild(document.createElement("br"));
                            li.querySelector("div").appendChild(badge);

                            // actualizar dataset del botón editar
                            const btnEditar = li.querySelector("button[title='Modificar']");
                            if (btnEditar) {
                                btnEditar.dataset.disponibilidad = 0;
                            }

                            // desactivar botón eliminar
                            const btnEliminar = li.querySelector("button[title='Eliminar']");
                            if (btnEliminar) {
                                btnEliminar.disabled = true;
                            }
                        }
                    }

                    // cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById("modalEliminarRecompensa"));
                    modal.hide();

                    mostrarAlerta("✅ Recompensa eliminada correctamente", "success");
                } else {
                    mostrarAlerta("❌ Error: " + data.error, "danger");
                }
            } catch (err) {
                mostrarAlerta("❌ Error inesperado al eliminar", "danger");
            }
        });
    }
}



function crearRecompensa() {
    const formCrear = document.getElementById("formCrearRecompensa");
    const modalCrear = document.getElementById("modalCrearRecompensa");

    if (!formCrear) return;

    formCrear.addEventListener("submit", async (e) => {
        e.preventDefault();

        const formData = new FormData(formCrear);

        try {
            const resp = await fetch(formCrear.action, {
                method: "POST",
                body: formData
            });
            const data = await resp.json();

            if (data.success) {
                // Crear el <li> nuevo
                const li = document.createElement("li");
                li.className = `list-group-item d-flex justify-content-between align-items-center ${data.disponibilidad == 0 ? 'text-muted bg-light' : ''}`;
                li.dataset.id = data.id_recompensa;

                li.innerHTML = `
                    <div>
                        <strong>${data.titulo}</strong> - <span class="points">${data.costo} pts</span>
                        ${data.descripcion ? `<br><small class="text-muted">${data.descripcion}</small>` : ""}
                        ${data.disponibilidad > 0
                        ? `<br><small class="text-muted">Stock: ${data.disponibilidad}</small>`
                        : `<br><span class="badge bg-secondary">No disponible</span>`}
                    </div>
                    <div class="reward-actions">
                        <button
                            class="btn btn-sm btn-outline-primary admin-only me-1"
                            title="Modificar"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEditarRecompensa"
                            data-id="${data.id_recompensa}"
                            data-nombre="${data.titulo}"
                            data-costo="${data.costo}"
                            data-descripcion="${data.descripcion || ''}"
                            data-disponibilidad="${data.disponibilidad}"
                        >
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button
                            class="btn btn-sm btn-outline-danger admin-only"
                            title="Eliminar"
                            data-id="${data.id_recompensa}"
                            data-nombre="${data.titulo}"
                            data-bs-toggle="modal"
                            data-bs-target="#modalEliminarRecompensa"
                            ${data.disponibilidad == 0 ? 'disabled' : ''}
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;

                // Insertar al inicio del listado
                const list = document.getElementById("reward-list");
                if (list) list.prepend(li);

                // Re-activar handlers de editar y eliminar
                if (typeof editarRecompensa === "function") editarRecompensa();
                if (typeof eliminarRecompensa === "function") eliminarRecompensa();

                // Cerrar modal y resetear form
                const modal = bootstrap.Modal.getInstance(modalCrear);
                if (modal) modal.hide();
                formCrear.reset();

                mostrarAlerta("✅ Recompensa creada con éxito", "success");
            } else {
                mostrarAlerta("❌ Error: " + (data.error || "No se pudo crear la recompensa"), "danger");
            }
        } catch (err) {
            console.error(err);
            mostrarAlerta("❌ Error inesperado al crear", "danger");
        }
    });
}

function asegurarMensajeVacio(listId, placeholderId, mensaje) {
    const lista = document.getElementById(listId);
    if (!lista) return;

    const placeholder = document.getElementById(placeholderId);
    const itemsReales = [...lista.children].filter(el => !el.id || el.id !== placeholderId);

    if (itemsReales.length === 0) {
        if (!placeholder) {
            const msg = document.createElement("li");
            msg.className = "list-group-item text-muted";
            msg.id = placeholderId;
            msg.textContent = mensaje;
            lista.appendChild(msg);
        }
    } else {
        if (placeholder) placeholder.remove();
    }
}


function controlFormCrearRecompensa() {

    const costoInput = document.getElementById("crear-costo");
    const descripcionInput = document.getElementById("crear-descripcion");

    if (!costoInput) return;
    if (!descripcionInput) return;

    costoInput.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault(); // evita el submit
            descripcionInput.focus(); // mueve el foco al textarea
        }
    });
}

function canjearRecompensa() {
    const grupoIdFromServer = document.getElementById("recompensas-section").dataset.grupo;
    const rewardList = document.getElementById("reward-list");
    if (!rewardList) return;

    rewardList.addEventListener("click", (e) => {
        const btn = e.target.closest("button[title='Canjear']");
        if (!btn) return;

        const idRecompensa = btn.dataset.id;
        const nombre = btn.dataset.nombre;

        // usar el grupo global si el botón no lo trae
        const idGrupo = btn.dataset.grupo || grupoIdFromServer;

        abrirModalConfirmCanje(nombre, async () => {
            const formData = new FormData();
            formData.append("id_recompensa", idRecompensa);
            formData.append("id_grupo", idGrupo);

            try {
                const resp = await fetch("../../administrador/recompensas/canjear_recompensa.php", {
                    method: "POST",
                    body: formData
                });
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                const data = await resp.json();

                if (data.success) {
                    mostrarAlerta("✅ Recompensa canjeada con éxito", "success");

                    // Actualizar stock visualmente
                    const stockSpan = btn.closest("li").querySelector(".stock-span");
                    if (stockSpan) {
                        stockSpan.textContent = data.nuevo_stock;
                    }

                    // Actualizar puntos del colaborador
                    const puntosSpan = document.getElementById("puntos-colaborador");
                    if (puntosSpan) {
                        puntosSpan.textContent = data.puntos_restantes;
                    }

                    // Opcional: deshabilitar el botón si stock llega a 0
                    if (data.nuevo_stock <= 0) {
                        btn.disabled = true;
                        btn.innerHTML = `<i class="bi bi-cart-x"></i> Agotado`;
                    }
                } else {
                    mostrarAlerta("❌ " + (data.error || "No se pudo canjear la recompensa"), "danger");
                }


            } catch (err) {
                console.error("Error en canjearRecompensa:", err);
                mostrarAlerta("❌ Error inesperado al canjear", "danger");
            }
        });
    });
}



function mostrarAlerta(texto, tipo = "success") {

    if (!texto) return;
    const alertContainer = document.querySelector(".dashboard-content");
    if (alertContainer) {
        const wrapper = document.createElement("div");
        wrapper.innerHTML = `
      <div class="alert alert-${tipo} alert-dismissible fade show mt-3" role="alert">
        ${texto}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
      </div>
    `;
        alertContainer.prepend(wrapper);
        setTimeout(() => {
            const alert = wrapper.querySelector(".alert");
            if (alert) {
                alert.classList.remove("show");
                alert.classList.add("fade");
                setTimeout(() => wrapper.remove(), 500);
            }
        }, 4000);
    }
}

function abrirModalConfirmCanje(nombreRecompensa, onConfirm) {

    if (!nombreRecompensa) {
        return;
    }
    const modalEl = document.getElementById('modalConfirmCanje');
    const mensajeEl = document.getElementById('modalConfirmMensaje');
    const btnConfirm = document.getElementById('btnConfirmarCanje');
    const spinner = document.getElementById('spinnerConfirmCanje');
    if (!modalEl || !mensajeEl || !btnConfirm) return;

    mensajeEl.textContent = `¿Seguro que quieres canjear la recompensa "${nombreRecompensa}"?`;
    const modal = new bootstrap.Modal(modalEl);

    // limpiamos listeners previos
    const nuevoBtn = btnConfirm.cloneNode(true);
    btnConfirm.parentNode.replaceChild(nuevoBtn, btnConfirm);

    nuevoBtn.addEventListener('click', async () => {
        try {
            nuevoBtn.disabled = true;
            spinner.classList.remove('d-none');
            await onConfirm(); // onConfirm debe manejar resultado y actualizar UI
        } finally {
            spinner.classList.add('d-none');
            nuevoBtn.disabled = false;
            modal.hide();
        }
    });

    modal.show();
}

function refrescarTodoConBoton() {
    const grupoId = obtenerGrupoIdDesdeURL();
    const role = document.body.dataset.role; // lee admin o colaborador

    let ajaxUrl;
    if (role === "admin") {
        ajaxUrl = "grupo_ajax.php?id=" + grupoId;
    } else {
        ajaxUrl = "../../administrador/grupo/grupo_ajax.php?id=" + grupoId;
    }

    const tick = () => {
        fetch(ajaxUrl)
            .then(res => {
                if (!res.ok) throw new Error("HTTP " + res.status);
                return res.json();
            })
            .then(data => {
                if (!data.success) {
                    console.error("Error en grupo_ajax:", data.error);
                    return;
                }

                // Pintar cada sección con sus datos
                renderizarMiembros(data.miembros, data.isAdmin);
                renderizarTareas(data.tareas, data.isAdmin, data.usuarioId);
                renderizarRecompensas(data.recompensas, data.isAdmin);
                renderizarHistorial(data.historial);
                renderizarAprobarTareas(data.tareas_realizadas);

                // Actualizar puntaje del colaborador si existe el span
                const puntosSpan = document.getElementById("puntos-colaborador");
                if (puntosSpan) {
                    puntosSpan.textContent = data.puntos;
                }

                // 🔹 Actualizar badge de aprobar tareas en el sidebar
                const badge = document.getElementById("badge-aprobar");
                if (badge) {
                    const pendientes = data.tareas_realizadas.length;
                    if (data.isAdmin && pendientes > 0) {
                        badge.textContent = pendientes;   // muestra número
                        badge.classList.remove("d-none"); // lo hace visible
                    } else {
                        badge.classList.add("d-none");    // lo oculta
                    }
                }
            })
            .catch(err => console.error("Error refrescando grupo:", err));
    };

    // Enganchar al botón
    const btn = document.getElementById("btn-recargar");
    if (btn) {
        btn.addEventListener("click", tick);
    }

    // Si querés que cargue una vez al entrar:
    tick();
}


function cargarConfiguracionAdministrador() {
    const grupoId = new URLSearchParams(window.location.search).get("id");
    if (!grupoId) return;

    fetch(`../configuracion/configuracion.php?id=${grupoId}`)
        .then(res => res.json())
        .then(data => {
            const cont = document.getElementById("configuracion-container");
            if (!cont) return;

            if (!data.success) {
                cont.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }

            const tipoBonito = data.tipo ? (data.tipo[0].toUpperCase() + data.tipo.slice(1)) : "";
            const expulsados = typeof data.expulsados === "number" ? data.expulsados : 0;
            const desc = (data.descripcion || "").trim();

            cont.innerHTML = `
          <div class="config-header d-flex align-items-center justify-content-between flex-wrap">
            <div>
              <h4 class="mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle"></i> Información del grupo
              </h4>
              <div class="d-flex align-items-center gap-2">
                <span class="config-name" data-field="nombre">${data.nombre}</span>
                <span class="badge rounded-pill config-badge" data-field="tipo">${tipoBonito}</span>
              </div>
            </div>
            <div class="config-stats d-flex align-items-center gap-2">
              <span class="stat-badge">
                <i class="bi bi-people"></i> Activos: <strong data-field="miembros">${data.miembros}</strong>
              </span>
              <span class="stat-badge stat-muted">
                <i class="bi bi-person-x"></i> Expulsados: <strong>${expulsados}</strong>
              </span>
            </div>
          </div>
  
          ${desc ? `
          <div class="config-description mt-3" data-field="descripcion">
            <h6 class="text-muted mb-2">Descripción</h6>
            <p class="mb-0">${desc}</p>
          </div>` : ""}
  
          <div class="config-actions-footer mt-3">
            <div class="row g-2">
              <div class="col-12 col-md-4">
                <button id="btnCopiarCodigo" class="btn btn-outline-secondary w-100" data-codigo="${data.codigo}">
                  <i class="bi bi-link-45deg"></i> Copiar código
                </button>
              </div>
              <div class="col-6 col-md-4">
                <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#editarGrupoModal">
                  <i class="bi bi-pencil-square"></i> Editar grupo
                </button>
              </div>
              <div class="col-6 col-md-4">
                <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#eliminarGrupoModal">
                  <i class="bi bi-trash"></i> Eliminar grupo
                </button>
              </div>
            </div>
          </div>
        `;

            configurarBotonCopiarCodigo();
        })
        .catch(err => {
            console.error("Error al cargar configuración:", err);
            const cont = document.getElementById("configuracion-container");
            if (cont) cont.innerHTML = `<div class="alert alert-danger">Error al cargar configuración</div>`;
        });
}

// Cargar configuración para colaborador (role-aware, sin stats, con copiar y abandonar)
function cargarConfiguracionColaborador() {
    const grupoId = new URLSearchParams(window.location.search).get("id");
    if (!grupoId) return;

    fetch(`../../colaborador/configuracion/configuracion.php?id=${grupoId}`)
        .then(async (res) => {
            const raw = await res.text();
            let data;
            try { data = JSON.parse(raw); }
            catch {
                throw new Error(`Respuesta no JSON (HTTP ${res.status}): ${raw.slice(0, 120)}...`);
            }

            if (!res.ok || !data.success) {
                throw new Error(data?.error || `HTTP ${res.status}`);
            }

            const cont = document.getElementById("configuracion-container");
            if (!cont) return;

            const tipoBonito = data.tipo ? (data.tipo[0].toUpperCase() + data.tipo.slice(1)) : "";
            const desc = (data.descripcion || "").trim();

            // Pintamos únicamente nombre, categoría, descripción, código y acciones (copiar / abandonar)
            cont.innerHTML = `
          <div class="config-header d-flex align-items-center justify-content-between flex-wrap">
          <div>
            <h4 class="mb-1 d-flex align-items-center gap-2">
              <i class="bi bi-info-circle"></i> Información del grupo
            </h4>
            <div class="d-flex align-items-center gap-2">
              <span class="config-name" data-field="nombre">${escapeHtml(data.nombre)}</span>
              <span class="badge rounded-pill config-badge" data-field="tipo">${escapeHtml(tipoBonito)}</span>
            </div>
          </div>
          <!-- Sin estadísticas para colaborador -->
        </div>

        ${desc ? `
        <div class="config-description mt-3" data-field="descripcion">
          <h6 class="text-muted mb-2">Descripción</h6>
          <p class="mb-0">${escapeHtml(desc)}</p>
        </div>` : ""}

        <div class="config-actions-footer mt-3">
          <div class="row g-2">
            <div class="col-12 col-md-6">
              <button id="btnCopiarCodigo" class="btn btn-outline-secondary w-100" data-codigo="${escapeAttr(data.codigo)}">
                <i class="bi bi-link-45deg"></i> Copiar código
              </button>
            </div>
            <div class="col-12 col-md-6">
              <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#abandonarGrupoModal">
                <i class="bi bi-box-arrow-left"></i> Abandonar grupo
              </button>
            </div>
          </div>
        </div>
        `;

            configurarBotonCopiarCodigo();
        })
        .catch((err) => {
            console.error("Error al cargar configuración (colaborador):", err);
            const cont = document.getElementById("configuracion-container");
            if (cont) {
                cont.innerHTML = `<div class="alert alert-danger">Error al cargar configuración.</div>`;
            }
        });
}

// Utilidades de escape para seguridad XSS (en caso de que no estén ya definidas)
function escapeHtml(str) {
    return String(str)
        .replace(/&/g, "&amp;").replace(/</g, "&lt;")
        .replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
function escapeAttr(str) { return escapeHtml(str).replace(/"/g, "&quot;"); }
