<?php
session_start();
require_once __DIR__ . '/../../includes/connection.php';

header('Content-Type: application/json');

$id_grupo = $_GET['id'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if (!$id_grupo || !$userEmail) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Grupo no especificado o sesión incompleta']);
    exit();
}

// Obtener ID del usuario
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
$stmt->execute([':email' => $userEmail]);
$usuario_id = $stmt->fetchColumn();

if (!$usuario_id) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    exit();
}

// Verificar si el usuario actual es admin
$stmt = $conn->prepare("SELECT rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
$stmt->execute([':gid' => $id_grupo, ':uid' => $usuario_id]);
$rol_usuario = $stmt->fetchColumn();
$isAdmin = ($rol_usuario === 'administrador');

// 🔹 Miembros activos
$stmt = $conn->prepare("
    SELECT u.id_usuario, u.nombre, gu.rol
    FROM grupousuario gu
    JOIN usuario u ON gu.usuario_id = u.id_usuario
    WHERE gu.grupo_id = :id AND gu.estado = 1
");
$stmt->execute([':id' => $id_grupo]);
$miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Tareas pendientes asignadas al colaborador
$stmt = $conn->prepare("
    SELECT t.id_tarea, t.titulo, t.descripcion, t.puntos, t.fecha_limite,
           u.nombre AS asignado, t.asignadoA AS asignado_id
    FROM tarea t
    LEFT JOIN usuario u ON t.asignadoA = u.id_usuario
    WHERE t.grupo_id = :grupo_id 
      AND t.estado = 'pendiente'
      AND t.activa = 1
    ORDER BY t.fecha_limite ASC
");
$stmt->execute([':grupo_id' => $id_grupo]);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Tareas realizadas (para admin)
$stmt = $conn->prepare("
    SELECT t.id_tarea, t.titulo, t.descripcion, t.puntos,
           t.fecha_limite, t.fecha_entrega,
           u.nombre AS asignado, t.asignadoA AS asignado_id
    FROM tarea t
    LEFT JOIN usuario u ON t.asignadoA = u.id_usuario
    WHERE t.grupo_id = :grupo_id 
      AND t.estado = 'realizada'
      AND t.activa = 1
    ORDER BY t.fecha_entrega DESC, t.fecha_limite ASC
");
$stmt->execute([':grupo_id' => $id_grupo]);
$tareas_realizadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Historial del grupo
$stmt = $conn->prepare("
    SELECT h.id_historialGrupoUsuario, h.fecha, h.puntosOtorgados, h.puntosCanjeados, h.estadoTarea,
           u.nombre AS usuario, t.titulo AS tarea, r.nombre AS recompensa
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

// 🔹 Recompensas del grupo
$stmt = $conn->prepare("
    SELECT id_recompensa, nombre AS titulo, descripcion, costo_puntos AS costo, disponibilidad
    FROM recompensa
    WHERE grupo_id = :grupo_id
    ORDER BY id_recompensa DESC
");
$stmt->execute([':grupo_id' => $id_grupo]);
$recompensas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Puntaje del colaborador
$stmt = $conn->prepare("
    SELECT id_grupo_usuario, puntos
    FROM grupousuario
    WHERE grupo_id = :gid AND usuario_id = :uid AND estado = 1
");
$stmt->execute([':gid' => $id_grupo, ':uid' => $usuario_id]);
$grupoUsuario = $stmt->fetch(PDO::FETCH_ASSOC);
$puntos_actuales = $grupoUsuario['puntos'] ?? 0;

// 🔹 Respuesta JSON
echo json_encode([
    'success' => true,
    'miembros' => $miembros,
    'tareas' => $tareas,
    'tareas_realizadas' => $tareas_realizadas,
    'historial' => $historial,
    'recompensas' => $recompensas,
    'puntos' => $puntos_actuales,
    'isAdmin' => $isAdmin,
    'usuarioId' => $usuario_id
]);
