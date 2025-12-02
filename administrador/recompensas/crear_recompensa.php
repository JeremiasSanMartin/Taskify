<?php
session_start();
require_once '../../includes/connection.php';

header('Content-Type: application/json');

$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$costo_puntos = intval($_POST['costo_puntos'] ?? 0);
$disponibilidad = intval($_POST['disponibilidad'] ?? 0); // stock inicial
$id_grupo = $_POST['id_grupo'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if ($nombre && $costo_puntos > 0 && $id_grupo && $userEmail) {
    try {
        // verificar que sea administrador
        $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
        $stmt->execute([':email' => $userEmail]);
        $id_usuario = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT id_grupo_usuario, rol 
                                FROM grupousuario 
                                WHERE grupo_id = :gid AND usuario_id = :uid");
        $stmt->execute([':gid' => $id_grupo, ':uid' => $id_usuario]);
        $guAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$guAdmin || $guAdmin['rol'] !== 'administrador') {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        // insertar recompensa con stock
        $stmt = $conn->prepare("INSERT INTO recompensa 
            (nombre, descripcion, costo_puntos, grupo_id, disponibilidad)
            VALUES (:nombre, :descripcion, :costo_puntos, :grupo_id, :disponibilidad)");
        $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':costo_puntos' => $costo_puntos,
            ':grupo_id' => $id_grupo,
            ':disponibilidad' => $disponibilidad
        ]);

        $id_recompensa = $conn->lastInsertId();

        echo json_encode([
            'success' => true,
            'id_recompensa' => $id_recompensa,
            'titulo' => $nombre,
            'descripcion' => $descripcion,
            'costo' => $costo_puntos,
            'disponibilidad' => $disponibilidad
        ]);

        // historial (estadoTarea = 10 → recompensa creada)
        $stmt = $conn->prepare("INSERT INTO historialgrupousuario 
            (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
            VALUES (CURDATE(), 0, NULL, 10, :gu_id_admin, NULL, :rid)");
        $stmt->execute([
            ':gu_id_admin' => $guAdmin['id_grupo_usuario'],
            ':rid' => $id_recompensa
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al crear recompensa: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Datos incompletos'
    ]);
    exit;
}
