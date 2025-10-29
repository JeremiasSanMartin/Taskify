<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Verificar sesión
if (!isset($_SESSION['nombre']) || !isset($_SESSION['email'])) {
    header("Location: /Taskify/index.php");
    exit();
}

require_once '../../includes/connection.php';

$id_grupo = $_GET['id'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if (!$id_grupo || !$userEmail) {
    echo "<p class='text-danger'>Grupo no especificado o sesión incompleta.</p>";
    exit();
}

// Obtener el ID del usuario desde el email
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
$stmt->execute([':email' => $userEmail]);
$usuario_id = $stmt->fetchColumn();

if (!$usuario_id) {
    echo "<p class='text-danger'>Usuario no encontrado.</p>";
    exit();
}

// Obtener datos del grupo
$stmt = $conn->prepare("SELECT nombre, tipo FROM grupo WHERE id_grupo = :id");
$stmt->execute([':id' => $id_grupo]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo "<p class='text-danger'>El grupo no existe.</p>";
    exit();
}

// Obtener cantidad de miembros
$stmt = $conn->prepare("SELECT COUNT(*) FROM grupousuario WHERE grupo_id = :id");
$stmt->execute([':id' => $id_grupo]);
$total_miembros = $stmt->fetchColumn();

// Obtenemos los datos reales de los miembros
$stmt = $conn->prepare("
    SELECT u.id_usuario, u.nombre, gu.rol
    FROM grupousuario gu
    JOIN usuario u ON gu.usuario_id = u.id_usuario
    WHERE gu.grupo_id = :id AND gu.estado = 1
");
$stmt->execute([':id' => $id_grupo]);
$miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Verificamos si el usuario actual es admin
$stmt = $conn->prepare("
    SELECT rol FROM grupousuario
    WHERE grupo_id = :id AND usuario_id = :uid
");
$stmt->execute([':id' => $id_grupo, ':uid' => $usuario_id]);
$rol_usuario = $stmt->fetchColumn();
$isAdmin = ($rol_usuario === 'administrador');

$userName = htmlspecialchars($_SESSION['nombre']);
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Grupo - Taskify</title>

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

<body class="dashboard-body">


    <!-- Sidebar -->
    <nav class="sidebar" id="main-sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <div class="logo d-flex align-items-center gap-2">
                <i class="bi bi-people-fill"></i>
                <span id="group-sidebar-title">Grupo Demo</span>
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
            <a href="#" class="menu-item" data-section="aprobar-tareas">
                <i class="bi bi-check2-square"></i>
                <span>Aprobar Tareas</span>
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
        <header class="dashboard-header d-flex align-items-center justify-content-between flex-wrap w-100">
            <div class="d-flex flex-column">
                <h1 class="page-title mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill"></i>
                    <?php echo htmlspecialchars($grupo['nombre']); ?>
                </h1>
                <p class="page-subtitle mt-1">
                    Categoría: <span id="group-category"><?php echo strtoupper($grupo['tipo']); ?></span> •
                    <span id="group-members-count"><?php echo $total_miembros; ?>
                        <?php echo $total_miembros == 1 ? 'miembro' : 'miembros'; ?>
                    </span>
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 ms-auto">
                <button class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#editarGrupoModal">
                    <i class="bi bi-pencil-square me-1"></i> Editar
                </button>
                <button class="btn btn-danger btn-lg px-4" data-bs-toggle="modal" data-bs-target="#eliminarGrupoModal">
                    <i class="bi bi-trash me-1"></i> Eliminar
                </button>
            </div>
        </header>

        <div class="dashboard-content">

            <!-- Miembros -->
            <div id="miembros-section" class="content-section active">
                <div class="content-card p-3">
                    <h3><i class="bi bi-people"></i> Miembros</h3>
                    <ul id="member-list" class="list-group">
                        <?php if (empty($miembros)): ?>
                            <li class="list-group-item text-muted">Este grupo aún no tiene miembros.</li>
                        <?php else: ?>
                            <?php foreach ($miembros as $miembro): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($miembro['nombre']); ?>
                                    <?php echo $miembro['rol'] === 'administrador' ? '(Admin)' : ''; ?>
                                    <?php if ($isAdmin && $miembro['rol'] !== 'administrador'): ?>
                                        <button class="btn btn-sm btn-outline-danger remove-member-btn admin-only"
                                            data-bs-toggle="modal" data-bs-target="#expulsarModal"
                                            data-nombre="<?php echo htmlspecialchars($miembro['nombre']); ?>"
                                            data-id="<?php echo $miembro['id_usuario']; ?>">
                                            <i class="bi bi-person-x"></i>
                                        </button>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Tareas -->
            <div id="tareas-section" class="content-section">
                <div class="content-card p-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3><i class="bi bi-list-check"></i> Tareas</h3>
                        <button id="btn-create-task" class="btn-primary">
                            <i class="bi bi-plus-circle"></i> Crear Tarea
                        </button>
                    </div>
                    <ul id="task-list" class="list-group mt-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Comprar víveres - 10 pts
                            <div class="task-actions">
                                <button class="btn btn-sm btn-outline-primary admin-only me-1" title="Modificar">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger admin-only" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success complete-task-btn" title="Completada">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Diseñar interfaz - 20 pts
                            <div class="task-actions">
                                <button class="btn btn-sm btn-outline-primary admin-only me-1" title="Modificar">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger admin-only" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success complete-task-btn" title="Completada">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Recompensas -->
            <div id="recompensas-section" class="content-section">
                <div class="content-card p-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3><i class="bi bi-gift-fill"></i> Recompensas</h3>
                        <button id="btn-create-reward" class="btn-primary">
                            <i class="bi bi-plus-circle"></i> Crear Recompensa
                        </button>
                    </div>
                    <ul id="reward-list" class="list-group mt-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Café gratis - 30 pts
                            <div class="reward-actions">
                                <!-- Solo admin -->
                                <button class="btn btn-sm btn-outline-primary admin-only me-1" title="Modificar">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger admin-only" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Día libre - 50 pts
                            <div class="reward-actions">
                                <!-- Solo admin -->
                                <button class="btn btn-sm btn-outline-primary admin-only me-1" title="Modificar">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger admin-only" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Historial -->
            <div id="historial-section" class="content-section">
                <div class="content-card p-3">
                    <h3><i class="bi bi-clock-history"></i> Historial</h3>
                    <ul id="history-list" class="list-group">
                        <li class="list-group-item">Mía completó "Diseñar interfaz"</li>
                        <li class="list-group-item">Carlos canjeó "Café gratis"</li>
                    </ul>
                </div>
            </div>

            <!-- Aprobar Tareas (solo admin) -->
            <div id="aprobar-tareas-section" class="content-section">
                <div class="content-card p-3">
                    <h3><i class="bi bi-check2-square"></i> Aprobar Tareas</h3>
                    <ul id="approve-task-list" class="list-group mt-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Diseñar interfaz - 20 pts
                            <button class="btn btn-sm btn-outline-success" title="Aprobar">
                                <i class="bi bi-check-circle"></i>
                            </button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Comprar víveres - 10 pts
                            <button class="btn btn-sm btn-outline-success" title="Aprobar">
                                <i class="bi bi-check-circle"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Configuración -->
            <div id="configuracion-section" class="content-section">
                <div class="content-card p-3">
                    <h3><i class="bi bi-gear-fill"></i> Configuración</h3>
                    <p>aca van las diferentes configuraciones</p>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/group.js"></script>

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
                    <form action="eliminar_grupo.php" method="POST">
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
            <form action="editar_grupo.php" method="POST" class="modal-content">
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
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="expulsarLabel">Confirmar expulsión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro que querés expulsar a <strong id="nombreMiembro"></strong> del grupo?
                    <input type="hidden" name="id_usuario" id="idUsuarioExpulsar">
                    <input type="hidden" name="id_grupo" value="<?php echo $id_grupo; ?>">
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

</body>

</html>