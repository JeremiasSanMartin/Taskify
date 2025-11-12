document.addEventListener("DOMContentLoaded", () => {
    initGroupPage();

});

function initGroupPage() {
    //MIEMBROS
    handleSidebarNavigation();
    manejarSidebarResponsivo();
    configurarModalExpulsion();
    mostrarToastExpulsion();
    actualizarMiembrosPeriodicamente();
    configurarBotonCopiarCodigo();

    //TAREAS
    crearTareaConModal();
    configurarModalEliminarTarea();
    configurarModalEditarTarea();
    editarTareaConModal();
    eliminarTareaConModal();
    configurarBotonCompletarTarea();
    crearRecompensa();
    controlFormCrearRecompensa();
    prellenarModalEditarRecompensa();
    editarRecompensa();
    eliminarRecompensa();
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

function crearTareaConModal() {

    document.addEventListener("submit", (e) => {
        const form = e.target;
        if (!form || form.id !== "formCrearTarea") return;

        e.preventDefault();

        const modal = document.getElementById("crearTareaModal");
        const formData = new FormData(form);

        fetch(form.action, { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.error || "Error al crear tarea.");
                    return;
                }

                // Eliminar mensaje vacío si existe
                const noTasksMsg = document.querySelector("#task-list .list-group-item");
                if (noTasksMsg && noTasksMsg.textContent.includes("No hay tareas pendientes")) {
                    noTasksMsg.remove();
                }

                // Insertar <li> con los mismos botones que PHP
                const nuevoLi = document.createElement("li");
                nuevoLi.className = "list-group-item d-flex justify-content-between align-items-start flex-column flex-md-row";
                nuevoLi.innerHTML = `
  <div>
    <strong>${data.titulo}</strong> - ${data.puntos} pts<br>
    <small>${data.descripcion}</small><br>
    <small class="text-muted">Fecha límite: ${data.fecha_limite}</small><br>
    <small class="text-muted">Asignado a: ${data.asignado || 'Sin asignar'}</small>
  </div>
  <div class="task-actions mt-2 mt-md-0">
    <button class="btn btn-sm btn-outline-primary admin-only me-1" 
            data-bs-toggle="modal" data-bs-target="#editarTareaModal"
            data-id="${data.id_tarea}" data-titulo="${data.titulo}"
            data-descripcion="${data.descripcion}" data-puntos="${data.puntos}"
            data-fecha="${data.fecha_limite}" data-asignado="${data.asignado}" 
            title="Modificar">
      <i class="bi bi-pencil-square"></i>
    </button>
    <button class="btn btn-sm btn-outline-danger admin-only" 
            data-bs-toggle="modal" data-bs-target="#eliminarTareaModal"
            data-id="${data.id_tarea}" data-titulo="${data.titulo}" 
            title="Eliminar">
      <i class="bi bi-trash"></i>
    </button>
    <button class="btn btn-sm btn-outline-success complete-task-btn"
            data-id="${data.id_tarea}" title="Completada">
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

                // Alerta de éxito
                const alertContainer = document.querySelector(".container.mt-4");
                if (alertContainer) {
                    alertContainer.innerHTML = `
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    ✅ Tarea creada con éxito.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>`;
                }
            })
            .catch(err => console.error("Error:", err));
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

    form.addEventListener("submit", (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        fetch(form.action, { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.error || "Error al editar tarea.");
                    return;
                }

                // Encontrar el <li> de la tarea por cualquier botón con data-id=id_tarea
                const actionBtn = document.querySelector(`#task-list .task-actions [data-id="${data.id_tarea}"]`);
                const li = actionBtn ? actionBtn.closest("li") : null;
                if (!li) return;

                // Actualizar bloque de información
                const left = li.querySelector("div:first-child");
                if (left) {
                    left.innerHTML = `
  <strong>${data.titulo}</strong> - ${data.puntos} pts<br>
  <small>${data.descripcion}</small><br>
  <small class="text-muted">Fecha límite: ${data.fecha_limite}</small><br>
  <small class="text-muted">Asignado a: ${data.asignado || 'Sin asignar'}</small>
            `;
                }

                // Refrescar data-* en los botones de acción
                li.querySelectorAll(".task-actions [data-id]").forEach(btn => {
                    btn.setAttribute("data-titulo", data.titulo);
                    btn.setAttribute("data-descripcion", data.descripcion);
                    btn.setAttribute("data-puntos", data.puntos);
                    btn.setAttribute("data-fecha", data.fecha_limite);
                    btn.setAttribute("data-asignado", data.asignado || '');
                });

                // Cerrar modal y resetear
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
                form.reset();

                // Alerta
                const alertContainer = document.querySelector(".container.mt-4");
                if (alertContainer) {
                    alertContainer.innerHTML = `
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    ✏️ Tarea editada con éxito.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>`;
                }
            })
            .catch(err => console.error("Error:", err));
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

        // Seleccionar el asignado por ID
        const asignadoId = button.getAttribute("data-asignado-id");
        const select = document.getElementById("editAsignado");
        if (select && asignadoId) {
            [...select.options].forEach(opt => { opt.selected = (opt.value === asignadoId); });
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


function prellenarModalEditarRecompensa() {
    const modalEditar = document.getElementById("modalEditarRecompensa");

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
                const response = await fetch("/Taskify/administrador/recompensas/editar_recompensa.php", {
                    method: "POST",
                    body: formData
                });
                const data = await response.json();

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
                const resp = await fetch("/Taskify/administrador/recompensas/eliminar_recompensa.php", {
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

    costoInput.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            event.preventDefault(); // evita el submit
            descripcionInput.focus(); // mueve el foco al textarea
        }
    });
}

function mostrarAlerta(texto, tipo = "success") {

    if (!mensaje) return;
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







