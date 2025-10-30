<?php
session_start();
require_once '../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tarea = $_POST['id_tarea'] ?? null;
    $id_grupo = $_POST['id_grupo'] ?? null;

    if ($id_tarea && $id_grupo) {
        try {
            $stmt = $conn->prepare("DELETE FROM tarea WHERE id_tarea = :id_tarea AND grupo_id = :id_grupo");
            $stmt->execute([
                ':id_tarea' => $id_tarea,
                ':id_grupo' => $id_grupo
            ]);

            header("Location: ../grupo/ver_grupo.php?id=" . $id_grupo);
            exit();
        } catch (PDOException $e) {
            die("Error al eliminar tarea: " . $e->getMessage());
        }
    } else {
        die("Datos incompletos para eliminar la tarea.");
    }
} else {
    header("Location: ../grupo/ver_grupo.php");
    exit();
}