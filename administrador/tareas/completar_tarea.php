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

// 1. Obtener id_usuario desde el email
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
$stmt->execute([':email' => $userEmail]);
$id_usuario = $stmt->fetchColumn();

if (!$id_usuario) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    exit;
}

// 2. Obtener rol y id_grupo_usuario
$stmt = $conn->prepare("SELECT id_grupo_usuario, rol 
                        FROM grupousuario 
                        WHERE grupo_id = :gid AND usuario_id = :uid");
$stmt->execute([':gid' => $id_grupo, ':uid' => $id_usuario]);
$gu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gu) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Usuario no pertenece al grupo']);
    exit;
}

$grupo_usuario_id = $gu['id_grupo_usuario'];
$rol = $gu['rol'];

try {
    // Obtener asignadoA y puntos de la tarea
    $stmt = $conn->prepare("SELECT asignadoA, puntos FROM tarea WHERE id_tarea = :tid AND grupo_id = :gid");
    $stmt->execute([':tid' => $id_tarea, ':gid' => $id_grupo]);
    $tareaData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tareaData) {
        echo json_encode(['success' => false, 'error' => 'Tarea no encontrada']);
        exit;
    }

    $usuarioAsignado = $tareaData['asignadoA'];
    $puntosTarea = (int) $tareaData['puntos'];

    if ($rol === 'administrador') {
        // Admin aprueba directamente
        $stmt = $conn->prepare("
            UPDATE tarea
            SET estado = 'aprobada', fecha_entrega = NOW()
            WHERE id_tarea = :tid AND grupo_id = :gid
        ");
        $stmt->execute([':tid' => $id_tarea, ':gid' => $id_grupo]);

        // Insertar en historial con estadoTarea = 2 (aprobada)
        $stmt = $conn->prepare("
            INSERT INTO historialgrupousuario
            (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
            VALUES (NOW(), :puntos, NULL, 2, :grupo_usuario_id, :tarea_id, NULL)
        ");
        $stmt->execute([
            ':puntos' => $puntosTarea,
            ':grupo_usuario_id' => $grupo_usuario_id,
            ':tarea_id' => $id_tarea
        ]);

        echo json_encode(['success' => true, 'estado' => 'aprobada', 'id_tarea' => $id_tarea]);
    } else {
        // Colaborador: marca como realizada y se asigna automáticamente
        $stmt = $conn->prepare("
            UPDATE tarea
            SET estado = 'realizada', fecha_entrega = NOW(), asignadoA = :uid
            WHERE id_tarea = :tid AND grupo_id = :gid
        ");
        $stmt->execute([
            ':uid' => $id_usuario,
            ':tid' => $id_tarea,
            ':gid' => $id_grupo
        ]);


        // Insertar en historial con estadoTarea = 1 (realizada)
        $stmt = $conn->prepare("
            INSERT INTO historialgrupousuario
            (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
            VALUES (NOW(), 0, NULL, 1, :grupo_usuario_id, :tarea_id, NULL)
        ");
        $stmt->execute([
            ':grupo_usuario_id' => $grupo_usuario_id,
            ':tarea_id' => $id_tarea
        ]);

        echo json_encode(['success' => true, 'estado' => 'realizada', 'id_tarea' => $id_tarea]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}