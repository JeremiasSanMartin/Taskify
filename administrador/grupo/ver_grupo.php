<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Verificar sesión
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

// Obtener el ID del usuario desde el email
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
$stmt->execute([':email' => $userEmail]);
$usuario_id = $stmt->fetchColumn();

if (!$usuario_id) {
    echo "<p class='text-danger'>Usuario no encontrado.</p>";
    exit();
}

//obtener rol del usuario en este grupo
$stmt = $conn->prepare("SELECT rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
$stmt->execute([':gid' => $id_grupo, ':uid' => $usuario_id]);
$rol = $stmt->fetchColumn();

// Obtener datos del grupo
$stmt = $conn->prepare("SELECT nombre, tipo, codigo_invitacion, descripcion FROM grupo WHERE id_grupo = :id");
$stmt->execute([':id' => $id_grupo]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo "<p class='text-danger'>El grupo no existe.</p>";
    exit();
}

// Obtener cantidad de miembros
$stmt = $conn->prepare("SELECT COUNT(*) FROM grupousuario WHERE grupo_id = :id AND estado = 1");
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

//obtener tareas
$userName = htmlspecialchars($_SESSION['nombre']);

$tareas = [];

if ($id_grupo) {
    try {
        $stmt = $conn->prepare("
        SELECT t.id_tarea, t.titulo, t.descripcion, t.puntos, t.fecha_limite,
               u.nombre AS asignado
        FROM tarea t
        LEFT JOIN usuario u ON t.asignadoA = u.id_usuario
        WHERE t.grupo_id = :grupo_id AND t.estado = 'pendiente'
        ORDER BY t.fecha_limite ASC
        ");
        $stmt->bindParam(':grupo_id', $id_grupo, PDO::PARAM_INT);
        $stmt->execute();
        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<p>Error al obtener tareas: " . $e->getMessage() . "</p>";
    }
}

//tareas realizadas
$tareas_realizadas = [];

if ($id_grupo) {
    try {
        $stmt = $conn->prepare("
        SELECT t.id_tarea, t.titulo, t.descripcion, t.puntos, 
                t.fecha_limite, t.fecha_entrega, 
                u.nombre AS asignado
        FROM tarea t
        LEFT JOIN usuario u ON t.asignadoA = u.id_usuario
        WHERE t.grupo_id = :grupo_id AND t.estado = 'realizada'
        ORDER BY t.fecha_entrega DESC, t.fecha_limite ASC
        ");
        $stmt->bindParam(':grupo_id', $id_grupo, PDO::PARAM_INT);
        $stmt->execute();
        $tareas_realizadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<p class='text-danger'>Error al obtener tareas realizadas: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

//ver historial
$stmt = $conn->prepare("
    SELECT 
        h.id_historialGrupoUsuario,
        h.fecha,
        h.puntosOtorgados,
        h.puntosCanjeados,
        h.estadoTarea,
        u.nombre AS usuario,
        t.titulo AS tarea,
        r.nombre AS recompensa
    FROM historialgrupousuario h
    LEFT JOIN grupousuario gu ON h.grupo_usuario_id = gu.id_grupo_usuario
    LEFT JOIN usuario u ON gu.usuario_id = u.id_usuario
    LEFT JOIN tarea t ON h.tarea_id_tarea = t.id_tarea
    LEFT JOIN recompensa r ON h.recompensa_id_recompensa = r.id_recompensa
    WHERE gu.grupo_id = :grupo_id
    ORDER BY h.fecha DESC, h.id_historialGrupoUsuario DESC
");
$stmt->execute([':grupo_id' => $id_grupo]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

//obtener recompensas 
$recompensas = [];

try {
    $stmt = $conn->prepare("
        SELECT id_recompensa, nombre AS titulo, descripcion, costo_puntos AS costo, disponibilidad
        FROM recompensa
        WHERE grupo_id = :grupo_id
        ORDER BY id_recompensa DESC
    ");
    $stmt->execute([':grupo_id' => $id_grupo]);
    $recompensas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['mensaje'] = [
        'tipo' => 'danger',
        'texto' => '❌ Error al cargar recompensas: ' . $e->getMessage()
    ];
}

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
                <span id="group-sidebar-title"><?= htmlspecialchars($grupo['nombre']) ?></span>
            </div>
        </div>

        <!-- Menú del grupo -->
        <div class="sidebar-menu">
            <a href="../../index.php" class="menu-item external" data-external="true">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Volver al Dashboard</span>
            </a>
            <a href="#" class="menu-item" data-section="miembros">
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
            <a href="#" class="menu-item" data-section="aprobar-tareas">
                <i class="bi bi-check2-square"></i>
                <span>Aprobar Tareas</span>
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
            <!-- Columna izquierda: info del grupo -->
            <div class="d-flex flex-column">
                <h1 class="page-title mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill"></i>
                    <?= htmlspecialchars($grupo['nombre']) ?>
                </h1>
                <p class="page-subtitle mt-1 mb-0">
                    Categoría: <span id="group-category"><?= strtoupper($grupo['tipo']) ?></span> •
                    <span id="group-members-count"><?= $total_miembros ?>
                        <?= $total_miembros == 1 ? 'miembro' : 'miembros' ?></span>
                </p>
                <?php if (!empty($grupo['descripcion'])): ?>
                    <p class="group-description mt-2 text-muted">
                        <?= nl2br(htmlspecialchars($grupo['descripcion'])) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- ALERTAS -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['mensaje']['tipo'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['mensaje']['texto'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?>

            <!-- Columna centro: código de invitación -->
            <div class="d-flex justify-content-center flex-grow-1">
                <button id="btnCopiarCodigo" class="badge bg-light text-dark border border-secondary px-4 py-2 fs-6"
                    style="cursor: pointer;" data-codigo="<?= htmlspecialchars($grupo['codigo_invitacion']) ?>">
                    <i class="bi bi-link-45deg me-1"></i>
                    Código de invitación: <strong><?= htmlspecialchars($grupo['codigo_invitacion']) ?></strong>
                </button>
            </div>

            <!-- Columna derecha: botones -->
            <div class="d-flex align-items-center gap-2">
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
                        <button id="btn-create-task" class="btn-primary" data-bs-toggle="modal"
                            data-bs-target="#crearTareaModal">
                            <i class="bi bi-plus-circle"></i> Crear Tarea
                        </button>
                    </div>
                    <ul id="task-list" class="list-group mt-3">
                        <?php if (empty($tareas)): ?>
                            <li class="list-group-item">No hay tareas pendientes en este grupo.</li>
                        <?php else: ?>
                            <?php foreach ($tareas as $tarea): ?>
                                <li
                                    class="list-group-item d-flex justify-content-between align-items-start flex-column flex-md-row">
                                    <div>
                                        <strong><?= htmlspecialchars($tarea['titulo']) ?></strong> -
                                        <?= htmlspecialchars($tarea['puntos']) ?> pts<br>
                                        <small><?= htmlspecialchars($tarea['descripcion']) ?></small><br>
                                        <small class="text-muted">Fecha límite:
                                            <?= date('d/m/Y', strtotime($tarea['fecha_limite'])) ?>
                                        </small><br>
                                        <small class="text-muted">
                                            Asignado a: <?= htmlspecialchars($tarea['asignado'] ?? 'Sin asignar') ?>
                                        </small>
                                    </div>
                                    <div class="task-actions mt-2 mt-md-0">
                                        <button class="btn btn-sm btn-outline-primary admin-only me-1" data-bs-toggle="modal"
                                            data-bs-target="#editarTareaModal" data-id="<?= $tarea['id_tarea'] ?>"
                                            data-titulo="<?= htmlspecialchars($tarea['titulo']) ?>"
                                            data-descripcion="<?= htmlspecialchars($tarea['descripcion']) ?>"
                                            data-puntos="<?= $tarea['puntos'] ?>" data-fecha="<?= $tarea['fecha_limite'] ?>"
                                            data-asignado="<?= $tarea['asignado'] ?? '' ?>" title="Modificar">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger admin-only" data-bs-toggle="modal"
                                            data-bs-target="#eliminarTareaModal" data-id="<?= $tarea['id_tarea'] ?>"
                                            data-titulo="<?= htmlspecialchars($tarea['titulo']) ?>" title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success complete-task-btn"
                                            data-id="<?= $tarea['id_tarea'] ?>" title="Completada">
                                            <i class="bi bi-check-circle"></i>
                                        </button>

                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <!-- Recompensas -->
            <div id="recompensas-section" class="content-section">
                <div class="content-card p-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3><i class="bi bi-gift-fill"></i> Recompensas</h3>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearRecompensa">
                            <i class="bi bi-plus-circle"></i> Crear Recompensa
                        </button>
                    </div>
                    <!-- Listado de recompensas -->
                    <ul id="reward-list" class="list-group mt-4">
                        <?php foreach ($recompensas as $r): ?>
                            <?php if ($r['disponibilidad'] == -1)
                                continue; // ocultar eliminadas ?>

                            <li class="list-group-item d-flex justify-content-between align-items-center <?= $r['disponibilidad'] == 0 ? 'text-muted bg-light' : '' ?>"
                                data-id="<?= $r['id_recompensa'] ?>">
                                <div>
                                    <strong><?= htmlspecialchars($r['titulo']) ?></strong> -
                                    <span class="points"><?= $r['costo'] ?> pts</span>

                                    <?php if (!empty($r['descripcion'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($r['descripcion']) ?></small>
                                    <?php endif; ?>

                                    <?php if ($r['disponibilidad'] > 0): ?>
                                        <br><small class="text-muted">Stock: <?= $r['disponibilidad'] ?></small>
                                    <?php else: ?>
                                        <br><span class="badge bg-secondary">No disponible</span>
                                    <?php endif; ?>
                                </div>

                                <div class="reward-actions">
                                    <!-- Modificar recompensa (siempre activo) -->
                                    <button class="btn btn-sm btn-outline-primary admin-only me-1" title="Modificar"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarRecompensa"
                                        data-id="<?= $r['id_recompensa'] ?>"
                                        data-nombre="<?= htmlspecialchars($r['titulo']) ?>" data-costo="<?= $r['costo'] ?>"
                                        data-descripcion="<?= htmlspecialchars($r['descripcion'] ?? '') ?>"
                                        data-disponibilidad="<?= $r['disponibilidad'] ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- Eliminar recompensa (solo si está activa) -->
                                    <button class="btn btn-sm btn-outline-danger admin-only" title="Eliminar"
                                        data-id="<?= $r['id_recompensa'] ?>"
                                        data-nombre="<?= htmlspecialchars($r['titulo']) ?>" data-bs-toggle="modal"
                                        data-bs-target="#modalEliminarRecompensa" <?= $r['disponibilidad'] == 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (empty($recompensas)): ?>
                        <p class="text-muted mt-3">Todavía no hay recompensas creadas.</p>
                    <?php endif; ?>
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
                                                    case 10:
                                                        echo "Creó la recompensa";
                                                        break;
                                                    case 11:
                                                        echo "Editó la recompensa";
                                                        break;
                                                    case 12:
                                                        echo "Eliminó la recompensa";
                                                        break;
                                                    default:
                                                        echo "Acción desconocida";
                                                }
                                                ?>
                                            </td>
                                            <td><?php
                                            if (!empty($h['recompensa'])) {
                                                echo htmlspecialchars($h['recompensa']);
                                            } elseif (!empty($h['tarea'])) {
                                                echo htmlspecialchars($h['tarea']);
                                            } else {
                                                echo 'Sin título';
                                            }
                                            ?></td>
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


            <!-- Aprobar Tareas (solo admin) -->
            <div id="aprobar-tareas-section" class="content-section">
                <div class="content-card p-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3><i class="bi bi-check2-square"></i> Aprobar Tareas</h3>
                        <span class="text-muted">
                            <?php if (!empty($tareas_realizadas)): ?>
                                <?= count($tareas_realizadas) ?> pendientes de aprobación
                            <?php else: ?>
                                Sin tareas para aprobar
                            <?php endif; ?>
                        </span>
                    </div>

                    <ul id="approve-task-list" class="list-group mt-3">
                        <?php if (empty($tareas_realizadas)): ?>
                            <li class="list-group-item text-muted">No hay tareas marcadas como realizadas.</li>
                        <?php else: ?>
                            <?php foreach ($tareas_realizadas as $t): ?>
                                <li
                                    class="list-group-item d-flex justify-content-between align-items-start flex-column flex-md-row">
                                    <div>
                                        <strong><?= htmlspecialchars($t['titulo']) ?></strong> -
                                        <?= htmlspecialchars($t['puntos']) ?> pts<br>
                                        <small><?= htmlspecialchars($t['descripcion']) ?></small><br>
                                        <small class="text-muted">
                                            Asignado a: <?= htmlspecialchars($t['asignado'] ?? 'Desconocido') ?>
                                        </small><br>
                                        <?php if (!empty($t['fecha_entrega'])): ?>
                                            <small class="text-muted">Entregada:
                                                <?= date('d/m/Y', strtotime($t['fecha_entrega'])) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">Fecha límite:
                                                <?= date('d/m/Y', strtotime($t['fecha_limite'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-actions mt-2 mt-md-0">
                                        <div class="task-actions mt-2 mt-md-0">
                                            <!-- Botón Aprobar -->
                                            <form action="../tareas/aprobar_tarea.php" method="POST" class="d-inline">
                                                <input type="hidden" name="id_tarea" value="<?= $t['id_tarea'] ?>">
                                                <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Aprobar">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>

                                            <!-- Botón Rechazar -->
                                            <form action="../tareas/rechazar_tarea.php" method="POST" class="d-inline ms-2">
                                                <input type="hidden" name="id_tarea" value="<?= $t['id_tarea'] ?>">
                                                <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Rechazar">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
            <form action="../tareas/crear_tarea.php" method="POST" class="modal-content">
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
                            <option value="">Seleccionar miembro</option>
                            <?php foreach ($miembros as $miembro): ?>
                                <?php if ($miembro['rol'] !== 'administrador'): ?>
                                    <option value="<?= $miembro['id_usuario'] ?>">
                                        <?= htmlspecialchars($miembro['nombre']) ?>
                                    </option>
                                <?php endif; ?>
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
            <form action="../tareas/eliminar_tarea.php" method="POST" class="modal-content">
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
            <form action="../tareas/editar_tarea.php" method="POST" class="modal-content">
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
                            <option value="">Seleccionar miembro</option>
                            <?php foreach ($miembros as $miembro): ?>
                                <?php if ($miembro['rol'] !== 'administrador'): ?>
                                    <option value="<?= $miembro['id_usuario'] ?>">
                                        <?= htmlspecialchars($miembro['nombre']) ?>
                                    </option>
                                <?php endif; ?>
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