<?php
session_start();
require_once '../../includes/connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$id_tarea = $_POST['id_tarea'] ?? null;
$id_grupo = $_POST['id_grupo'] ?? null;
$titulo = $_POST['titulo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$puntos = $_POST['puntos'] ?? 0;
$fecha_limite = $_POST['fecha_limite'] ?? null;
$asignadoA = $_POST['asignadoA'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

// Normalizar asignadoA: '' => NULL
if ($asignadoA === '' || $asignadoA === '0') {
    $asignadoA = null;
}

// Validación básica sin exigir asignadoA
if ($id_tarea && $id_grupo && $titulo && $descripcion && is_numeric($puntos) && (int) $puntos > 0 && $fecha_limite && $userEmail) {
    try {
        // validar admin del grupo
        $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
        $stmt->execute([':email' => $userEmail]);
        $id_admin = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT id_grupo_usuario, rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
        $stmt->execute([':gid' => $id_grupo, ':uid' => $id_admin]);
        $guAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$guAdmin || $guAdmin['rol'] !== 'administrador') {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        $conn->beginTransaction();

        // Actualizar tarea (asignadoA puede ser NULL)
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
            ':puntos' => (int) $puntos,
            ':fecha_limite' => $fecha_limite,
            ':asignadoA' => $asignadoA, // puede ser NULL
            ':id_tarea' => $id_tarea,
            ':grupo_id' => $id_grupo
        ]);

        // historial (estadoTarea = 4 → tarea editada)
        $stmt = $conn->prepare("
            INSERT INTO historialgrupousuario
                (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
            VALUES (CURDATE(), 0, NULL, 4, :gu_id_admin, :tid, NULL)
        ");
        $stmt->execute([
            ':gu_id_admin' => $guAdmin['id_grupo_usuario'],
            ':tid' => $id_tarea
        ]);

        // nombre del asignado si existe
        $nombre_asignado = null;
        if (!is_null($asignadoA)) {
            $stmt = $conn->prepare("SELECT nombre FROM usuario WHERE id_usuario = :id");
            $stmt->execute([':id' => $asignadoA]);
            $nombre_asignado = $stmt->fetchColumn() ?: null;
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'id_tarea' => $id_tarea,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'puntos' => (int) $puntos,
            'fecha_limite' => $fecha_limite,
            'asignado' => $nombre_asignado, // null → “Sin asignar”
            'asignado_id' => $asignadoA        // puede ser null
        ]);
        exit;
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Error al editar tarea: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos para editar la tarea.']);
    exit;
}
