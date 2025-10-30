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
                <button class="btn btn-danger btn-lg px-4" data-bs-toggle="modal"
                    data-bs-target="#abandonarGrupoModal">
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
                    <h3><i class="bi bi-list-check"></i> Tareas</h3>
                    <p>Aquí se mostrarán las tareas asignadas al grupo.</p>
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
                    <h3><i class="bi bi-clock-history"></i> Historial</h3>
                    <p>Aquí se mostrará el historial de actividad del grupo.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/group.js"></script>

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

</body>

</html>