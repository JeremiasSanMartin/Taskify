<?php
session_start();
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

// Verificar que el usuario pertenece al grupo como colaborador
$stmt = $conn->prepare("
    SELECT rol FROM grupousuario
    WHERE grupo_id = :id AND usuario_id = :uid AND estado = 1
");
$stmt->execute([':id' => $id_grupo, ':uid' => $usuario_id]);
$rol = $stmt->fetchColumn();

if ($rol !== 'colaborador') {
    echo "<p class='text-danger'>Acceso restringido a colaboradores.</p>";
    exit();
}

// Obtener datos del grupo
$stmt = $conn->prepare("SELECT nombre, tipo FROM grupo WHERE id_grupo = :id");
$stmt->execute([':id' => $id_grupo]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener miembros activos
$stmt = $conn->prepare("
    SELECT u.id_usuario, u.nombre, gu.rol
    FROM grupousuario gu
    JOIN usuario u ON gu.usuario_id = u.id_usuario
    WHERE gu.grupo_id = :id AND gu.estado = 1
");
$stmt->execute([':id' => $id_grupo]);
$miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_miembros = count($miembros ?? []);
$userName = htmlspecialchars($_SESSION['nombre']);
$userEmail = htmlspecialchars($_SESSION['email']);

// Obtener tareas asignadas al colaborador
$stmt = $conn->prepare("
    SELECT t.id_tarea, t.titulo, t.descripcion, t.puntos, t.fecha_limite
    FROM tarea t
    WHERE t.grupo_id = :grupo_id AND t.estado = 'pendiente' AND t.asignadoA = :usuario_id
    ORDER BY t.fecha_limite ASC
");
$stmt->execute([':grupo_id' => $id_grupo, ':usuario_id' => $usuario_id]);
$tareas_asignadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Obtener historial del colaborador
$stmt = $conn->prepare("
    SELECT h.fecha, h.puntosOtorgados, h.estadoTarea,
           u.nombre AS usuario, t.titulo AS tarea
    FROM historialgrupousuario h
    LEFT JOIN grupousuario gu ON h.grupo_usuario_id = gu.id_grupo_usuario
    LEFT JOIN usuario u ON gu.usuario_id = u.id_usuario
    LEFT JOIN tarea t ON h.tarea_id_tarea = t.id_tarea
    WHERE gu.grupo_id = :grupo_id
    ORDER BY h.fecha DESC, h.id_historialGrupoUsuario DESC
");
$stmt->execute([':grupo_id' => $id_grupo]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($grupo['nombre']) ?> - Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../../assets/css/group.css" />
</head>

<body class="dashboard-body">
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
            <a href="/Taskify/index.php" class="menu-item external">
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
                    <?= htmlspecialchars($grupo['nombre']) ?>
                </h1>
                <p class="page-subtitle mt-1">
                    Categoría: <span id="group-category"><?= strtoupper($grupo['tipo']) ?></span> •
                    <span id="group-members-count"><?= $total_miembros ?>
                        <?= $total_miembros == 1 ? 'miembro' : 'miembros' ?></span>
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 ms-auto">
                <button class="btn btn-danger btn-lg px-4" data-bs-toggle="modal" data-bs-target="#abandonarGrupoModal">
                    <i class="bi bi-box-arrow-left me-1"></i> Abandonar
                </button>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Miembros -->
            <div id="miembros-section" class="content-section active">
                <div class="content-card p-3">
                    <h3><i class="bi bi-people"></i> Miembros</h3>
                    <ul class="list-group">
                        <?php foreach ($miembros as $miembro): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($miembro['nombre']) ?>
                                <?= $miembro['rol'] === 'administrador' ? '(Admin)' : '' ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Tareas -->
            <div id="tareas-section" class="content-section">
                <div class="content-card p-3">
                    <h3><i class="bi bi-list-check"></i> Mis Tareas</h3>
                    <ul class="list-group mt-3">
                        <?php if (empty($tareas_asignadas)): ?>
                            <li class="list-group-item text-muted">No tenés tareas pendientes asignadas.</li>
                        <?php else: ?>
                            <?php foreach ($tareas_asignadas as $t): ?>
                                <li
                                    class="list-group-item d-flex justify-content-between align-items-start flex-column flex-md-row">
                                    <div>
                                        <strong><?= htmlspecialchars($t['titulo']) ?></strong> - <?= $t['puntos'] ?> pts<br>
                                        <small><?= htmlspecialchars($t['descripcion']) ?></small><br>
                                        <small class="text-muted">Fecha límite:
                                            <?= date('d/m/Y', strtotime($t['fecha_limite'])) ?></small>
                                    </div>
                                    <form action="../../administrador/tareas/completar_tarea.php" method="POST"
                                        class="mt-2 mt-md-0">
                                        <input type="hidden" name="id_tarea" value="<?= $t['id_tarea'] ?>">
                                        <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success"
                                            title="Marcar como realizada">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>


            <!-- Recompensas -->
            <div id="recompensas-section" class="content-section">
                <div class="content-card p-3">
                    <h3><i class="bi bi-gift-fill"></i> Recompensas</h3>
                    <p>Aquí se mostrarán las recompensas disponibles.</p>
                </div>
            </div>

            <!-- Historial -->
            <div id="historial-section" class="content-section">
                <div class="content-card p-3">
                    <h3><i class="bi bi-clock-history"></i> Historial del grupo</h3>
                    <?php if (empty($historial)): ?>
                        <p class="text-muted">Todavía no hay actividad registrada.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Acción</th>
                                        <th>Tarea</th>
                                        <th>Fecha</th>
                                        <th>Puntos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $h): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($h['usuario']) ?></td>
                                            <td>
                                                <?php
                                                switch ($h['estadoTarea']) {
                                                    case 0:
                                                        echo "Rechazó la tarea";
                                                        break;
                                                    case 1:
                                                        echo "Marcó como realizada";
                                                        break;
                                                    case 2:
                                                        echo "Aprobó la tarea";
                                                        break;
                                                    case 3:
                                                        echo "Eliminó la tarea";
                                                        break;
                                                    case 4:
                                                        echo "Editó la tarea";
                                                        break;
                                                    case 5:
                                                        echo "Creó la tarea";
                                                        break;
                                                    default:
                                                        echo "Acción desconocida";
                                                }
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($h['tarea'] ?? 'Sin título') ?></td>
                                            <td><?= date('d/m/Y', strtotime($h['fecha'])) ?></td>
                                            <td class="text-center">
                                                <?= ($h['puntosOtorgados'] > 0)
                                                    ? "<span class='badge bg-success'>{$h['puntosOtorgados']} pts</span>"
                                                    : "-" ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


        </div>
    </main>

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

    <!-- Modal: Confirmar abandono -->
    <div class="modal fade" id="abandonarGrupoModal" tabindex="-1" aria-labelledby="abandonarGrupoLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="abandonar_grupo.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="abandonarGrupoLabel">¿Abandonar grupo?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que querés abandonar el grupo
                        <strong><?= htmlspecialchars($grupo['nombre']) ?></strong>? Esta acción no se puede deshacer.
                    </p>
                    <input type="hidden" name="grupo_id" value="<?= $id_grupo ?>">
                    <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Abandonar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/group.js"></script>

</body>

</html>