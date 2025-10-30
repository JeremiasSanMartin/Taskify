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
$stmt = $conn->prepare("SELECT asignadoA, puntos FROM tarea WHERE id_tarea = :tid AND grupo_id = :gid AND estado = 'realizada'");
$stmt->execute([':tid' => $id_tarea, ':gid' => $id_grupo]);
$tareaData = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tareaData) die("Tarea no encontrada o no está en estado 'realizada'");

$usuarioAsignado = $tareaData['asignadoA'];
$puntos = (int)$tareaData['puntos'];

$stmt = $conn->prepare("SELECT id_grupo_usuario FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
$stmt->execute([':gid' => $id_grupo, ':uid' => $usuarioAsignado]);
$guAsignadoId = $stmt->fetchColumn();
if (!$guAsignadoId) die("El asignado no pertenece al grupo");

try {
    $conn->beginTransaction();

    // aprobar
    $conn->prepare("UPDATE tarea SET estado = 'aprobada', fecha_entrega = IFNULL(fecha_entrega, CURDATE()) WHERE id_tarea = :tid AND grupo_id = :gid")
         ->execute([':tid' => $id_tarea, ':gid' => $id_grupo]);

    // historial al colaborador
    $conn->prepare("INSERT INTO historialgrupousuario (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
                    VALUES (CURDATE(), :puntos, NULL, 2, :gu_id, :tid, NULL)")
         ->execute([':puntos' => $puntos, ':gu_id' => $guAsignadoId, ':tid' => $id_tarea]);

    // sumar puntos al colaborador
    $conn->prepare("UPDATE grupousuario SET puntos = puntos + :puntos WHERE id_grupo_usuario = :gu_id")
         ->execute([':puntos' => $puntos, ':gu_id' => $guAsignadoId]);

    $conn->commit();

    header("Location: ../grupo/ver_grupo.php?id=$id_grupo&section=aprobar-tareas");
    exit;
} catch (PDOException $e) {
    $conn->rollBack();
    die("Error: " . $e->getMessage());
}
