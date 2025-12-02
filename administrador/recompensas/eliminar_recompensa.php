<?php
session_start();
require_once '../../includes/connection.php';
header('Content-Type: application/json');

$id_recompensa = isset($_POST['id_recompensa']) ? intval($_POST['id_recompensa']) : null;
$id_grupo = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : null;
$nombreRecompensa = $_POST['nombre_recompensa'] ?? '';
$userEmail = $_SESSION['email'] ?? null;

if (!$id_recompensa || !$id_grupo || !$userEmail) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // Usuario
    $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
    $stmt->execute([':email' => $userEmail]);
    $id_usuario = intval($stmt->fetchColumn());
    if (!$id_usuario) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }

    // Rol admin en el grupo
    $stmt = $conn->prepare("SELECT id_grupo_usuario, rol 
                            FROM grupousuario 
                            WHERE grupo_id = :gid AND usuario_id = :uid");
    $stmt->execute([':gid' => $id_grupo, ':uid' => $id_usuario]);
    $guAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$guAdmin || $guAdmin['rol'] !== 'administrador') {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    // Verificar que la recompensa exista en ese grupo
    $stmt = $conn->prepare("SELECT id_recompensa 
                            FROM recompensa 
                            WHERE id_recompensa = :id AND grupo_id = :gid");
    $stmt->execute([':id' => $id_recompensa, ':gid' => $id_grupo]);
    $exists = intval($stmt->fetchColumn());
    if (!$exists) {
        echo json_encode(['success' => false, 'error' => 'La recompensa no existe en este grupo']);
        exit;
    }

    // Transacción
    $conn->beginTransaction();

    // 1) Insertar en historial (estado 12 = eliminación lógica)
    $h = $conn->prepare("
        INSERT INTO historialgrupousuario 
        (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
        VALUES (CURDATE(), 0, 0, 12, :gu_id, NULL, :rid)
    ");
    $h->execute([
        ':gu_id' => $guAdmin['id_grupo_usuario'],
        ':rid' => $id_recompensa
    ]);

    // 2) Marcar recompensa como eliminada (disponibilidad = -1)
    $upd = $conn->prepare("UPDATE recompensa 
                           SET disponibilidad = -1 
                           WHERE id_recompensa = :id AND grupo_id = :gid");
    $upd->execute([':id' => $id_recompensa, ':gid' => $id_grupo]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'id_recompensa' => (string) $id_recompensa,
        'disponibilidad' => -1
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Error al eliminar recompensa: ' . $e->getMessage()]);
}
