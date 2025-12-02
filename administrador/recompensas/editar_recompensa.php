<?php
session_start();
require_once '../../includes/connection.php';
header('Content-Type: application/json');

$id_recompensa = $_POST['id_recompensa'] ?? null;
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$costo_puntos = intval($_POST['costo_puntos'] ?? 0);
$disponibilidad = intval($_POST['disponibilidad'] ?? 0);
$id_grupo = $_POST['id_grupo'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if ($id_recompensa && $nombre && $costo_puntos > 0 && $id_grupo && $userEmail) {
    try {
        // verificar admin
        $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
        $stmt->execute([':email' => $userEmail]);
        $id_usuario = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT id_grupo_usuario, rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
        $stmt->execute([':gid' => $id_grupo, ':uid' => $id_usuario]);
        $guAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$guAdmin || $guAdmin['rol'] !== 'administrador') {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        // actualizar recompensa
        $stmt = $conn->prepare("UPDATE recompensa 
    SET nombre = :nombre, descripcion = :descripcion, costo_puntos = :costo_puntos, disponibilidad = :disponibilidad
    WHERE id_recompensa = :id AND grupo_id = :grupo_id");
        $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':costo_puntos' => $costo_puntos,
            ':disponibilidad' => $disponibilidad,
            ':id' => $id_recompensa,
            ':grupo_id' => $id_grupo
        ]);

        // insertar en historial (estado 11 = edición)
        $h = $conn->prepare("
    INSERT INTO historialgrupousuario 
    (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
    VALUES (CURDATE(), 0, 0, 11, :gu_id, NULL, :rid)
");
        $h->execute([
            ':gu_id' => $guAdmin['id_grupo_usuario'],
            ':rid' => $id_recompensa
        ]);

        echo json_encode([
            'success' => true,
            'id_recompensa' => $id_recompensa,
            'titulo' => $nombre,
            'descripcion' => $descripcion,
            'costo' => $costo_puntos,
            'disponibilidad' => $disponibilidad
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al editar recompensa: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
}
