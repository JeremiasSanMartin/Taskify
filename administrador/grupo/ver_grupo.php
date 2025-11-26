<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['nombre']) || !isset($_SESSION['email'])) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../includes/connection.php';

$id_grupo = $_GET['id'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if (!$id_grupo || !$userEmail) {
    echo "<p class='text-danger'>Grupo no especificado o sesión incompleta.</p>";
    exit();
}

// Obtener ID del usuario
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
$stmt->execute([':email' => $userEmail]);
$usuario_id = $stmt->fetchColumn();

if (!$usuario_id) {
    echo "<p class='text-danger'>Usuario no encontrado.</p>";
    exit();
}

// Verificar rol en el grupo
$stmt = $conn->prepare("SELECT rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
$stmt->execute([':gid' => $id_grupo, ':uid' => $usuario_id]);
$rol_usuario = $stmt->fetchColumn();
$isAdmin = ($rol_usuario === 'administrador');

if (!$isAdmin) {
    echo "<p class='text-danger'>Acceso restringido a administradores.</p>";
    exit();
}

// Datos básicos del grupo
$stmt = $conn->prepare("SELECT nombre, tipo, codigo_invitacion, descripcion FROM grupo WHERE id_grupo = :id");
$stmt->execute([':id' => $id_grupo]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo "<p class='text-danger'>El grupo no existe.</p>";
    exit();
}

// Miembros activos del grupo (incluye rol para excluir admin en el select)
$stmt = $conn->prepare("
    SELECT u.id_usuario, u.nombre, gu.rol
    FROM grupousuario gu
    JOIN usuario u ON gu.usuario_id = u.id_usuario
    WHERE gu.grupo_id = :gid AND gu.estado = 1
    ORDER BY u.nombre ASC
");
$stmt->execute([':gid' => $id_grupo]);
$miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cantidad de miembros
$stmt = $conn->prepare("SELECT COUNT(*) FROM grupousuario WHERE grupo_id = :id AND estado = 1");
$stmt->execute([':id' => $id_grupo]);
$total_miembros = $stmt->fetchColumn();

$userName = htmlspecialchars($_SESSION['nombre']);
$userEmail = htmlspecialchars($_SESSION['email']);

?>




<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Taskify</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Dashboard base styles -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <!-- Overrides para group -->
    <link rel="stylesheet" href="../../assets/css/group.css">
</head>

<body class="dashboard-body" data-role="admin">


    <!-- Sidebar -->
    <nav class="sidebar" id="main-sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <div class="logo d-flex align-items-center gap-2">
                <i class="bi bi-people-fill"></i>
                <span id="group-sidebar-title"><?= htmlspecialchars($grupo['nombre']) ?></span>
            </div>
        </div>

        <!-- Menú del grupo -->
        <div class="sidebar-menu">
            <a href="../../index.php" class="menu-item external" data-external="true">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Volver al Dashboard</span>
            </a>
            <a href="#" class="menu-item active" data-section="miembros">
                <i class="bi bi-people"></i>
                <span>Miembros</span>
            </a>
            <a href="#" class="menu-item" data-section="tareas">
                <i class="bi bi-list-check"></i>
                <span>Tareas</span>
            </a>
            <a href="#" class="menu-item" data-section="aprobar-tareas">
                <i class="bi bi-check2-square"></i>
                <span>Aprobar Tareas</span>
                <span id="badge-aprobar" class="badge bg-danger rounded-pill d-none">0</span>
            </a>
            <a href="#" class="menu-item" data-section="recompensas">
                <i class="bi bi-gift-fill"></i>
                <span>Recompensas</span>
            </a>
            <a href="#" class="menu-item" data-section="historial">
                <i class="bi bi-clock-history"></i>
                <span>Historial</span>
            </a>
            <a href="#" class="menu-item" data-section="configuracion">
                <i class="bi bi-gear-fill"></i>
                <span>Configuración</span>
            </a>
        </div>

        <!-- Footer -->
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-info">
                    <div class="user-name"><?= $userName ?></div>
                    <div class="user-email"><?= $userEmail ?></div>
                </div>
                <button class="logout-btn btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#logoutModal"
                    title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <header class="dashboard-header d-flex justify-content-between align-items-center flex-wrap w-100">
            <!-- ALERTAS -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['mensaje']['texto'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>

            <!-- Columna derecha: botones -->
            <div class="w-100 d-flex justify-content-center mt-3">
                <button id="btn-recargar" class="btn btn-primary btn-lg">
                    <i class="bi bi-arrow-clockwise"></i> Recargar datos
                </button>
            </div>
        </header>

        <div class="dashboard-content">

            <!-- Miembros -->
            <div id="miembros-section" class="content-section active">
                <div class="content-card p-3">
                    <h3><i class="bi bi-people"></i> Miembros</h3>
                    <ul id="member-list" class="list-group">
                    </ul>
                </div>
            </div>

            <!-- Tareas -->
            <div id="tareas-section" class="content-section">
                <div class="content-card p-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3><i class="bi bi-list-check"></i> Tareas</h3>
                        <button id="btn-create-task" class="btn-primary" data-bs-toggle="modal"
                            data-bs-target="#crearTareaModal">
                            <i class="bi bi-plus-circle"></i> Crear Tarea
                        </button>
                    </div>
                    <ul id="task-list" class="list-group mt-3">
                    </ul>
                </div>
            </div>

            <!-- Recompensas -->
            <div id="recompensas-section" data-grupo="<?= $id_grupo ?>" class="content-section">
                <div class="content-card p-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3><i class="bi bi-gift-fill"></i> Recompensas</h3>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearRecompensa">
                            <i class="bi bi-plus-circle"></i> Crear Recompensa
                        </button>
                    </div>
                    <p><strong>Mis puntos:</strong>
                        <span id="puntos-admin" class="badge bg-success fs-5 px-3 py-2 rounded-pill shadow-sm">0</span>
                        pts
                    </p>
                    <ul id="reward-list" class="list-group mt-4"></ul>
                </div>
            </div>

            <!-- Historial -->
            <div id="historial-section" class="content-section">
                <div class="content-card p-3">
                    <h3><i class="bi bi-clock-history"></i> Historial del grupo</h3>
                    <div id="historial-list" class="table-responsive"></div>
                </div>
            </div>


            <!-- Aprobar Tareas (solo admin) -->
            <div id="aprobar-tareas-section" class="content-section">
                <div class="content-card p-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3><i class="bi bi-check2-square"></i> Aprobar Tareas</h3>
                    </div>

                    <ul id="approve-task-list" class="list-group mt-3">
                    </ul>
                </div>
            </div>

            <!-- Configuración -->
            <div id="configuracion-section" class="content-section">
                <div class="content-card p-3" id="configuracion-container">
                    <!-- Aquí se pintará la ficha del grupo -->
                </div>
            </div>

        </div>
    </main>

    <!-- Toast de expulsión -->
    <?php if (isset($_GET['expulsion']) && $_GET['expulsion'] === 'ok'): ?>
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
            <div id="expulsionToast" class="toast align-items-center text-bg-success border-0 show" role="alert"
                aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        El miembro fue expulsado correctamente del grupo.
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Cerrar"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!--Toast de copiar el codigo de invitacion en portapapeles-->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
        <div id="copiadoToast" class="toast align-items-center text-bg-success border-0" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    Código copiado al portapapeles.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Cerrar"></button>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Grupo -->
    <div class="modal fade" id="eliminarGrupoModal" tabindex="-1" aria-labelledby="eliminarGrupoLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="eliminarGrupoLabel">¿Eliminar grupo?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    Esta acción eliminará el grupo <strong><?php echo htmlspecialchars($grupo['nombre']); ?></strong> y
                    no se podrá recuperar. ¿Estás seguro?
                </div>
                <div class="modal-footer">
                    <form action="../grupo/eliminar_grupo.php" method="POST">
                        <input type="hidden" name="id_grupo" value="<?php echo $id_grupo; ?>">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Grupo -->
    <div class="modal fade" id="editarGrupoModal" tabindex="-1" aria-labelledby="editarGrupoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formEditarGrupo" action="../grupo/editar_grupo.php" method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editarGrupoLabel">Editar grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_grupo" value="<?php echo $id_grupo; ?>">
                    <div class="mb-3">
                        <label for="nombreGrupo" class="form-label">Nombre del grupo</label>
                        <input type="text" class="form-control" id="nombreGrupo" name="nombre"
                            value="<?php echo htmlspecialchars($grupo['nombre']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="tipoGrupo" class="form-label">Categoría</label>
                        <select class="form-select" id="tipoGrupo" name="tipo" required>
                            <option value="familiar" <?php if ($grupo['tipo'] === 'familiar')
                                echo 'selected'; ?>>Familiar
                            </option>
                            <option value="laboral" <?php if ($grupo['tipo'] === 'laboral')
                                echo 'selected'; ?>>Laboral
                            </option>
                            <option value="personal" <?php if ($grupo['tipo'] === 'personal')
                                echo 'selected'; ?>>Personal
                            </option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="descripcionGrupo" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcionGrupo" name="descripcion" rows="3"
                            spellcheck="false"><?=
                                htmlspecialchars(trim($grupo['descripcion'] ?? ''))
                                ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Expulsar Miembro -->
    <div class="modal fade" id="expulsarModal" tabindex="-1" aria-labelledby="expulsarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form action="expulsar_miembro.php" method="POST" class="modal-content">
                <input type="hidden" name="grupo_id" value="<?= $id_grupo ?>">
                <input type="hidden" name="usuario_id" id="idUsuarioExpulsar">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="expulsarLabel">Confirmar expulsión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro que querés expulsar a <strong id="nombreMiembro"></strong> del grupo?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Expulsar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Cerrar Sesión -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="logoutLabel">¿Cerrar sesión?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro que querés cerrar sesión?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="../../auth/logout.php" class="btn btn-danger">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Tarea -->
    <div class="modal fade" id="crearTareaModal" tabindex="-1" aria-labelledby="crearTareaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formCrearTarea" action="../tareas/crear_tarea.php" method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="crearTareaLabel">Crear nueva tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">

                    <div class="mb-3">
                        <label for="tituloTarea" class="form-label">Título</label>
                        <input type="text" class="form-control" id="tituloTarea" name="titulo" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcionTarea" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcionTarea" name="descripcion" rows="3"
                            required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="puntosTarea" class="form-label">Puntos</label>
                        <input type="number" class="form-control" id="puntosTarea" name="puntos" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="fechaLimite" class="form-label">Fecha límite</label>
                        <input type="date" class="form-control" id="fechaLimite" name="fecha_limite" required>
                    </div>

                    <div class="mb-3">
                        <label for="asignadoA" class="form-label">Asignar a</label>
                        <select class="form-select" id="asignadoA" name="asignadoA" required>
                            <option value="" selected disabled>Seleccione un miembro</option>
                            <option value="0">Sin asignar</option>
                            <?php foreach ($miembros as $miembro): ?>
                                <option value="<?= $miembro['id_usuario'] ?>">
                                    <?= htmlspecialchars($miembro['nombre']) ?>
                                    <?= $miembro['rol'] === 'administrador' ? ' (Admin)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Eliminar Tarea -->
    <div class="modal fade" id="eliminarTareaModal" tabindex="-1" aria-labelledby="eliminarTareaLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="formEliminarTarea" action="../tareas/eliminar_tarea.php" method="POST" class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="eliminarTareaLabel">Eliminar tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>¿Seguro que deseas eliminar la tarea <strong id="tareaTitulo"></strong>?</p>
                    <input type="hidden" name="id_tarea" id="idTareaEliminar">
                    <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Tarea -->
    <div class="modal fade" id="editarTareaModal" tabindex="-1" aria-labelledby="editarTareaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formEditarTarea" action="../tareas/editar_tarea.php" method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editarTareaLabel">Editar tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_tarea" id="editIdTarea">
                    <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">

                    <div class="mb-3">
                        <label for="editTitulo" class="form-label">Título</label>
                        <input type="text" class="form-control" id="editTitulo" name="titulo" required>
                    </div>

                    <div class="mb-3">
                        <label for="editDescripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="editDescripcion" name="descripcion" rows="3"
                            required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editPuntos" class="form-label">Puntos</label>
                        <input type="number" class="form-control" id="editPuntos" name="puntos" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="editFecha" class="form-label">Fecha límite</label>
                        <input type="date" class="form-control" id="editFecha" name="fecha_limite" required>
                    </div>

                    <div class="mb-3">
                        <label for="editAsignado" class="form-label">Asignado a</label>
                        <select class="form-select" id="editAsignado" name="asignadoA" required>
                            <option value="" selected disabled>Seleccione un miembro</option>
                            <option value="0">Sin asignar</option>
                            <?php foreach ($miembros as $miembro): ?>
                                <option value="<?= $miembro['id_usuario'] ?>">
                                    <?= htmlspecialchars($miembro['nombre']) ?>
                                    <?= $miembro['rol'] === 'administrador' ? ' (Admin)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Completar Tarea -->
    <div class="modal fade" id="modalCompletarTarea" tabindex="-1">
        <div class="modal-dialog">
            <form id="formCompletarTarea">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Completar tarea</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_tarea" id="completar-id-tarea">
                        <input type="hidden" name="id_grupo" id="completar-id-grupo">
                        <label for="usuario_id">¿Quién completó esta tarea?</label>
                        <select name="usuario_id" id="usuario_id" class="form-select"></select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Completar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal: Crear recompensa -->
    <div class="modal fade" id="modalCrearRecompensa" tabindex="-1" aria-labelledby="crearRecompensaLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="formCrearRecompensa" class="modal-content" method="POST"
                action="../recompensas/crear_recompensa.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearRecompensaLabel">
                        <i class="bi bi-gift-fill"></i> Crear recompensa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                    <input type="hidden" name="accion" value="recompensas">

                    <div class="mb-3">
                        <label for="crear-nombre" class="form-label">Título</label>
                        <input type="text" name="nombre" id="crear-nombre" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="crear-costo" class="form-label">Costo en puntos</label>
                        <input type="number" name="costo_puntos" id="crear-costo" class="form-control" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="crear-descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion" id="crear-descripcion" class="form-control" rows="2"></textarea>
                    </div>

                    <!-- Nuevo campo: stock disponible -->
                    <div class="mb-3">
                        <label for="crear-stock" class="form-label">Stock inicial</label>
                        <input type="number" name="disponibilidad" id="crear-stock" class="form-control" min="0"
                            required>
                        <small class="form-text text-muted">
                            Cantidad de veces que esta recompensa puede ser canjeada.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Modal: Editar recompensa -->
    <div class="modal fade" id="modalEditarRecompensa" tabindex="-1" aria-labelledby="editarRecompensaLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="formEditarRecompensa" class="modal-content" method="POST"
                action="../recompensas/editar_recompensa.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarRecompensaLabel"><i class="bi bi-pencil-square"></i> Editar
                        recompensa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                    <input type="hidden" name="id_recompensa" id="edit-id">

                    <div class="mb-3">
                        <label for="edit-nombre" class="form-label">Título</label>
                        <input type="text" name="nombre" id="edit-nombre" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-costo" class="form-label">Costo en puntos</label>
                        <input type="number" name="costo_puntos" id="edit-costo" class="form-control" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit-descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion" id="edit-descripcion" class="form-control" rows="2"></textarea>
                    </div>

                    <!-- Campo de stock (disponibilidad) -->
                    <div class="mb-3">
                        <label for="edit-disponibilidad" class="form-label">Disponibilidad</label>
                        <input type="number" name="disponibilidad" id="edit-disponibilidad" class="form-control" min="0"
                            required>
                        <small class="form-text text-muted">Cantidad de veces que puede canjearse esta
                            recompensa.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Guardar cambios</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal confirmar canje -->
    <div class="modal fade" id="modalConfirmCanje" tabindex="-1" aria-labelledby="modalConfirmCanjeLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="modalConfirmCanjeLabel"><i class="bi bi-cart-check"></i> Confirmar canje
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p id="modalConfirmMensaje" class="mb-0"></p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmarCanje">
                        <span class="spinner-border spinner-border-sm me-2 d-none" id="spinnerConfirmCanje"></span>
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="modalEliminarRecompensa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formEliminarRecompensa" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar recompensa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Seguro que quieres eliminar esta recompensa?</p>
                    <input type="hidden" name="id_recompensa" id="delete-id">
                    <input type="hidden" name="nombre_recompensa" id="delete-nombre">
                    <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/group.js"></script>
</body>

</html>