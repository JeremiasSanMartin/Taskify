<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Verificar sesión
if (!isset($_SESSION['name']) || !isset($_SESSION['email'])) {
    header("Location: ../index.html");
    exit();
}

$userName = htmlspecialchars($_SESSION['name']);
$userEmail = htmlspecialchars($_SESSION['email']);
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
    <link rel="stylesheet" href="dashboard.css">
    <!-- Overrides para group -->
    <link rel="stylesheet" href="group.css">
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
            <a href="index.php" class="menu-item external" data-external="true">
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
                <a href="../logout.php" class="logout-btn" title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <header class="dashboard-header">
            <div class="header-left">
                <h1 class="page-title"><i class="bi bi-people-fill"></i> Grupo Demo</h1>
                <p class="page-subtitle">Categoría: <span id="group-category">FAMILIA</span> • <span
                        id="group-members-count">4 miembros</span></p>
            </div>
        </header>

        <div class="dashboard-content">

            <!-- Miembros -->
            <div id="miembros-section" class="content-section active">
                <div class="content-card p-3">
                    <h3><i class="bi bi-people"></i> Miembros</h3>
                    <ul id="member-list" class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Jeremías (Admin)
                            <button class="btn btn-sm btn-outline-danger remove-member-btn admin-only"
                                title="Echar del grupo">
                                <i class="bi bi-person-x"></i>
                            </button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Mía
                            <button class="btn btn-sm btn-outline-danger remove-member-btn admin-only"
                                title="Echar del grupo">
                                <i class="bi bi-person-x"></i>
                            </button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Carlos
                            <button class="btn btn-sm btn-outline-danger remove-member-btn admin-only"
                                title="Echar del grupo">
                                <i class="bi bi-person-x"></i>
                            </button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Pepe
                            <button class="btn btn-sm btn-outline-danger remove-member-btn admin-only"
                                title="Echar del grupo">
                                <i class="bi bi-person-x"></i>
                            </button>
                        </li>
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
    <script src="group.js"></script>
</body>

</html>