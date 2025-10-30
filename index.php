<?php
session_start();

// Evitar que el navegador guarde en caché
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Si no hay sesión activa, redirigir al login
if (!isset($_SESSION['nombre']) || !isset($_SESSION['email'])) {
    header("Location: index.html");
    exit();
}

$userName = htmlspecialchars($_SESSION['nombre']);
$userEmail = htmlspecialchars($_SESSION['email']);

require_once 'includes/connection.php';

$usuario_id = $_SESSION['id_usuario'] ?? null;
$grupos_propios = [];

if ($userEmail) {
    $stmt = $conn->prepare("
        SELECT g.id_grupo, g.nombre, g.tipo,
               (SELECT COUNT(*) FROM grupousuario WHERE grupo_id = g.id_grupo) AS miembros
        FROM grupo g
        JOIN grupousuario gu ON gu.grupo_id = g.id_grupo
        JOIN usuario u ON gu.usuario_id = u.id_usuario
        WHERE u.email = :email AND gu.rol = 'administrador'
    ");
    $stmt->execute([':email' => $userEmail]);
    $grupos_propios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$grupos_participa = [];

if ($userEmail) {
    $stmt = $conn->prepare("
        SELECT g.id_grupo, g.nombre, g.tipo,
               (SELECT COUNT(*) FROM grupousuario WHERE grupo_id = g.id_grupo AND estado = 1) AS miembros
        FROM grupo g
        JOIN grupousuario gu ON gu.grupo_id = g.id_grupo
        JOIN usuario u ON gu.usuario_id = u.id_usuario
        WHERE u.email = :email AND gu.estado = 1 AND gu.rol != 'administrador'
    ");
    $stmt->execute([':email' => $userEmail]);
    $grupos_participa = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TASKIFY</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body class="dashboard-body">

    <?php if (isset($_SESSION['grupo_creado']) && $_SESSION['grupo_creado'] === true): ?>
        <div class="alert alert-success text-center">
            ¡Grupo creado con éxito! Código de invitación:
            <strong><?php echo $_SESSION['codigo_invitacion']; ?></strong>
        </div>
        <?php
        unset($_SESSION['grupo_creado']);
        unset($_SESSION['codigo_invitacion']);
        ?>
    <?php endif; ?>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="bi bi-star-fill"></i>
                <span>TASKIFY</span>
            </div>
        </div>

        <!-- Sidebar menu -->
        <div class="sidebar-menu">
            <a href="#" class="menu-item active" data-section="grupos">
                <i class="bi bi-people-fill"></i>
                <span>Mis Grupos</span>
            </a>
            <a href="#" class="menu-item" data-section="notificaciones">
                <i class="bi bi-bell-fill"></i>
                <span>Notificaciones</span>
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
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Gestiona tus grupos y colaboraciones</p>
            </div>
            <!-- Header buttons -->
            <div class="header-right">
                <a href="colaborador/grupo/unirse_grupo.php" class="btn btn-secondary">
                    <i class="bi bi-person-plus"></i> Unirse a Grupo
                </a>
                <a href="administrador/grupo/crear_grupo.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    Crear Grupo
                </a>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Groups Section -->
            <div id="grupos-section" class="content-section active">
                <!-- My Groups (Owner) -->
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">Mis Grupos (Propietario)</h3>
                        <a href="#" class="card-action">Gestionar todos</a>
                    </div>
                    <div class="groups-grid">
                        <?php if (empty($grupos_propios)): ?>
                            <p class="text-muted">Aún no has creado ningún grupo.</p>
                        <?php else: ?>
                            <?php foreach ($grupos_propios as $grupo): ?>
                                <?php
                                $tipo = strtolower($grupo['tipo']); // familiar, laboral, personal
                                $icono = match ($tipo) {
                                    'familiar' => 'bi-house-heart-fill',
                                    'laboral' => 'bi-briefcase-fill',
                                    'personal' => 'bi-person-fill',
                                    default => 'bi-people-fill'
                                };
                                $categoria = ucfirst($tipo);
                                $miembros = $grupo['miembros'] === 1 ? 'Solo yo' : $grupo['miembros'] . ' miembros';
                                ?>
                                <a href="administrador/grupo/ver_grupo.php?id=<?php echo $grupo['id_grupo']; ?>"
                                    class="group-card <?php echo $tipo; ?>" data-group-id="<?php echo $grupo['id_grupo']; ?>">
                                    <div class="group-icon">
                                        <i class="bi <?php echo $icono; ?>"></i>
                                    </div>
                                    <div class="group-info">
                                        <div class="group-name"><?php echo htmlspecialchars($grupo['nombre']); ?></div>
                                        <div class="group-category"><?php echo $categoria; ?></div>
                                        <div class="group-members"><?php echo $miembros; ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Groups I Belong To -->
                    <div class="content-card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Grupos donde Participo</h3>
                        </div>
                        <div class="groups-grid">
                            <?php foreach ($grupos_participa as $grupo): ?>
                                <div class="group-card <?= strtolower($grupo['tipo']) ?>"
                                    data-group-id="<?= $grupo['id_grupo'] ?>">
                                    <div class="group-icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div class="group-info">
                                        <div class="group-name"><?= htmlspecialchars($grupo['nombre']) ?></div>
                                        <div class="group-category"><?= ucfirst($grupo['tipo']) ?></div>
                                        <div class="group-members"><?= $grupo['miembros'] ?> miembros</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Notifications Section -->
                    <div id="notificaciones-section" class="content-section">
                        <div class="content-card">
                            <div class="card-header">
                                <h3 class="card-title">Notificaciones Recientes</h3>
                                <a href="#" class="card-action">Ver todas</a>
                            </div>
                            <div class="notifications-list">
                                <div class="notification-item unread">
                                    <div class="notification-icon">
                                        <i class="bi bi-person-plus-fill"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-text">María se unió al grupo "Equipo Marketing"</div>
                                        <div class="notification-time">Hace 5 minutos</div>
                                    </div>
                                </div>

                                <div class="notification-item unread">
                                    <div class="notification-icon">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-text">Carlos completó "Diseño de interfaz" en Proyecto
                                            Alpha
                                        </div>
                                        <div class="notification-time">Hace 1 hora</div>
                                    </div>
                                </div>

                                <div class="notification-item">
                                    <div class="notification-icon">
                                        <i class="bi bi-plus-circle-fill"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-text">Nueva tarea asignada en "Mi Familia": Comprar
                                            víveres
                                        </div>
                                        <div class="notification-time">Hace 2 horas</div>
                                    </div>
                                </div>
                                <!-- Removed the last 2 notifications about points and family meeting -->
                            </div>
                        </div>
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
                    <a href="auth/logout.php" class="btn btn-danger">Cerrar sesión</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>

</html>