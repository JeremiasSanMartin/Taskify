<?php
session_start();
require_once '../../includes/connection.php';

$id_tarea = $_POST['id_tarea'] ?? null;
$id_grupo = $_POST['id_grupo'] ?? null;
$userEmail = $_SESSION['email'] ?? null;
if (!$id_tarea || !$id_grupo || !$userEmail) die("Datos incompletos");

// admin actual
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
$stmt->execute([':email' => $userEmail]);
$id_admin = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT id_grupo_usuario, rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
$stmt->execute([':gid' => $id_grupo, ':uid' => $id_admin]);
$guAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guAdmin || $guAdmin['rol'] !== 'administrador') die("No autorizado");

// datos de la tarea
$stmt = $conn->prepare("SELECT asignadoA FROM tarea WHERE id_tarea = :tid AND grupo_id = :gid AND estado = 'realizada'");
$stmt->execute([':tid' => $id_tarea, ':gid' => $id_grupo]);
$tareaData = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tareaData) die("Tarea no encontrada o no está en estado 'realizada'");

$usuarioAsignado = $tareaData['asignadoA'];

$stmt = $conn->prepare("SELECT id_grupo_usuario FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
$stmt->execute([':gid' => $id_grupo, ':uid' => $usuarioAsignado]);
$guAsignadoId = $stmt->fetchColumn();
if (!$guAsignadoId) die("El asignado no pertenece al grupo");

try {
    $conn->beginTransaction();

    // devolver a pendiente
    $conn->prepare("UPDATE tarea SET estado = 'pendiente', fecha_entrega = NULL WHERE id_tarea = :tid AND grupo_id = :gid")
         ->execute([':tid' => $id_tarea, ':gid' => $id_grupo]);

    // historial (devuelta a pendiente)
    $conn->prepare("INSERT INTO historialgrupousuario (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
                    VALUES (CURDATE(), 0, NULL, 0, :gu_id, :tid, NULL)")
         ->execute([':gu_id' => $guAsignadoId, ':tid' => $id_tarea]);

    $conn->commit();

    header("Location: ../grupo/ver_grupo.php?id=$id_grupo&section=aprobar-tareas");
    exit;
} catch (PDOException $e) {
    $conn->rollBack();
    die("Error: " . $e->getMessage());
}
