<?php
session_start();
require_once '../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tarea   = $_POST['id_tarea'] ?? null;
    $id_grupo   = $_POST['id_grupo'] ?? null;
    $titulo     = $_POST['titulo'] ?? '';
    $descripcion= $_POST['descripcion'] ?? '';
    $puntos     = $_POST['puntos'] ?? 0;
    $fecha_limite = $_POST['fecha_limite'] ?? null;
    $asignadoA  = $_POST['asignadoA'] ?? null;

    if ($id_tarea && $id_grupo && $titulo && $descripcion && $puntos > 0 && $fecha_limite && $asignadoA) {
        try {
            $stmt = $conn->prepare("
                UPDATE tarea
                SET titulo = :titulo,
                    descripcion = :descripcion,
                    puntos = :puntos,
                    fecha_limite = :fecha_limite,
                    asignadoA = :asignadoA
                WHERE id_tarea = :id_tarea AND grupo_id = :grupo_id
            ");
            $stmt->execute([
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':puntos' => $puntos,
                ':fecha_limite' => $fecha_limite,
                ':asignadoA' => $asignadoA,
                ':id_tarea' => $id_tarea,
                ':grupo_id' => $id_grupo
            ]);

            header("Location: ../grupo/ver_grupo.php?id=" . $id_grupo . "&section=tareas");
            exit();
        } catch (PDOException $e) {
            die("Error al editar tarea: " . $e->getMessage());
        }
    } else {
        die("Datos incompletos para editar la tarea.");
    }
} else {
    header("Location: ../grupo/ver_grupo.php");
    exit();
}
