<?php
session_start();
require_once '../../includes/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_grupo   = $_POST['id_grupo'] ?? null;
    $titulo     = $_POST['titulo'] ?? '';
    $descripcion= $_POST['descripcion'] ?? '';
    $puntos     = $_POST['puntos'] ?? 0;
    $fecha_limite = $_POST['fecha_limite'] ?? null;
    $asignadoA  = $_POST['asignadoA'] ?? null;

    if ($id_grupo && $titulo && $descripcion && $puntos > 0 && $fecha_limite && $asignadoA) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO tarea (titulo, descripcion, puntos, estado, fecha_limite, asignadoA, grupo_id)
                VALUES (:titulo, :descripcion, :puntos, 'pendiente', :fecha_limite, :asignadoA, :grupo_id)
            ");
            $stmt->execute([
                ':titulo' => $titulo,
                ':descripcion' => $descripcion,
                ':puntos' => $puntos,
                ':fecha_limite' => $fecha_limite,
                ':asignadoA' => $asignadoA,
                ':grupo_id' => $id_grupo
            ]);

            header("Location: ../grupo/ver_grupo.php?id=" . $id_grupo);
            exit();
        } catch (PDOException $e) {
            die("Error al crear tarea: " . $e->getMessage());
        }
    } else {
        die("Datos incompletos para crear la tarea.");
    }
} else {
    header("Location: ../grupo/ver_grupo.php");
    exit();
}
