<?php
session_start();
require_once '../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tarea = $_POST['id_tarea'] ?? null;
    $id_grupo = $_POST['id_grupo'] ?? null;
    $userEmail = $_SESSION['email'] ?? null;

    if ($id_tarea && $id_grupo && $userEmail) {
        try {
            // obtener admin actual
            $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
            $stmt->execute([':email' => $userEmail]);
            $id_admin = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT id_grupo_usuario, rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
            $stmt->execute([':gid' => $id_grupo, ':uid' => $id_admin]);
            $guAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$guAdmin || $guAdmin['rol'] !== 'administrador') {
                die("No autorizado");
            }

            $conn->beginTransaction();

            // eliminar tarea
            $stmt = $conn->prepare("DELETE FROM tarea WHERE id_tarea = :id_tarea AND grupo_id = :id_grupo");
            $stmt->execute([':id_tarea' => $id_tarea, ':id_grupo' => $id_grupo]);

            // historial (estadoTarea = 3 → eliminada)
            $stmt = $conn->prepare("INSERT INTO historialgrupousuario 
                (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
                VALUES (CURDATE(), 0, NULL, 3, :gu_id_admin, :tid, NULL)");
            $stmt->execute([
                ':gu_id_admin' => $guAdmin['id_grupo_usuario'],
                ':tid' => $id_tarea
            ]);

            $conn->commit();

            header("Location: ../grupo/ver_grupo.php?id=" . $id_grupo . "&section=tareas");
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            die("Error al eliminar tarea: " . $e->getMessage());
        }
    } else {
        die("Datos incompletos para eliminar la tarea.");
    }
} else {
    header("Location: ../grupo/ver_grupo.php");
    exit();
}
