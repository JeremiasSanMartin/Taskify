<?php
session_start();
require_once '../../includes/connection.php';

header('Content-Type: application/json; charset=utf-8');

$id_tarea = $_POST['id_tarea'] ?? null;
$id_grupo = $_POST['id_grupo'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if (!$id_tarea || !$id_grupo || !$userEmail) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// admin actual
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
$stmt->execute([':email' => $userEmail]);
$id_admin = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT id_grupo_usuario, rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
$stmt->execute([':gid' => $id_grupo, ':uid' => $id_admin]);
$guAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$guAdmin || $guAdmin['rol'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// datos de la tarea
$stmt = $conn->prepare("SELECT asignadoA FROM tarea WHERE id_tarea = :tid AND grupo_id = :gid AND estado = 'realizada'");
$stmt->execute([':tid' => $id_tarea, ':gid' => $id_grupo]);
$tareaData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tareaData) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => "Tarea no encontrada o no está en estado 'realizada'"]);
    exit;
}

$usuarioAsignado = $tareaData['asignadoA'];

$stmt = $conn->prepare("SELECT id_grupo_usuario FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
$stmt->execute([':gid' => $id_grupo, ':uid' => $usuarioAsignado]);
$guAsignadoId = $stmt->fetchColumn();

if (!$guAsignadoId) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'El asignado no pertenece al grupo']);
    exit;
}

try {
    $conn->beginTransaction();

    // devolver a pendiente
    $conn->prepare("UPDATE tarea SET estado = 'pendiente', fecha_entrega = NULL WHERE id_tarea = :tid AND grupo_id = :gid")
        ->execute([':tid' => $id_tarea, ':gid' => $id_grupo]);

    // historial del administrador (rechazó la tarea)
    $conn->prepare("INSERT INTO historialgrupousuario (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
                VALUES (CURDATE(), 0, NULL, 0, :gu_id_admin, :tid, NULL)")
        ->execute([
            ':gu_id_admin' => $guAdmin['id_grupo_usuario'],
            ':tid' => $id_tarea
        ]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'id_tarea' => $id_tarea,
        'estado' => 'rechazada'
    ]);
} catch (PDOException $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
