<?php
session_start();
require_once '../../includes/connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tarea = $_POST['id_tarea'] ?? null;
    $id_grupo = $_POST['id_grupo'] ?? null;
    $userEmail = $_SESSION['email'] ?? null;

    if ($id_tarea && $id_grupo && $userEmail) {
        try {
            // Obtener usuario administrador
            $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
            $stmt->execute([':email' => $userEmail]);
            $id_admin = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT id_grupo_usuario, rol 
                                    FROM grupousuario 
                                    WHERE grupo_id = :gid AND usuario_id = :uid");
            $stmt->execute([':gid' => $id_grupo, ':uid' => $id_admin]);
            $guAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$guAdmin || $guAdmin['rol'] !== 'administrador') {
                echo json_encode(['success' => false, 'error' => 'No autorizado']);
                exit;
            }

            $conn->beginTransaction();

            // Registrar en historial con estadoTarea = 3 (eliminada/inactivada)
            $stmt = $conn->prepare("INSERT INTO historialgrupousuario 
                (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
                VALUES (CURDATE(), 0, NULL, 3, :gu_id_admin, :tid, NULL)");
            $stmt->execute([
                ':gu_id_admin' => $guAdmin['id_grupo_usuario'],
                ':tid' => $id_tarea
            ]);

            // Inactivar la tarea en vez de borrarla
            $stmt = $conn->prepare("UPDATE tarea 
                                    SET activa = 0 
                                    WHERE id_tarea = :id_tarea AND grupo_id = :id_grupo");
            $stmt->execute([
                ':id_tarea' => $id_tarea,
                ':id_grupo' => $id_grupo
            ]);

            $conn->commit();

            echo json_encode(['success' => true, 'id_tarea' => $id_tarea, 'estado' => 'inactiva']);
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'error' => 'Error al inactivar tarea: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos para inactivar la tarea.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}
