<?php
session_start();
require_once __DIR__ . "/../../includes/connection.php";
header('Content-Type: application/json');

$id_grupo = $_POST['id_grupo'] ?? null;
$nombre = $_POST['nombre'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$userEmail = $_SESSION['email'] ?? null;

if (!$id_grupo || !$nombre || !$tipo || !$userEmail) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // validar que el usuario sea admin del grupo
    $stmt = $conn->prepare("SELECT u.id_usuario, gu.rol 
        FROM usuario u 
        JOIN grupousuario gu ON u.id_usuario = gu.usuario_id 
        WHERE u.email = :email AND gu.grupo_id = :gid");
    $stmt->execute([':email' => $userEmail, ':gid' => $id_grupo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['rol'] !== 'administrador') {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE grupo SET nombre = :nombre, tipo = :tipo, descripcion = :desc WHERE id_grupo = :id");
    $stmt->execute([
        ':nombre' => $nombre,
        ':tipo' => strtolower($tipo),
        ':desc' => $descripcion,
        ':id' => $id_grupo
    ]);

    echo json_encode([
        'success' => true,
        'nombre' => $nombre,
        'tipo' => strtolower($tipo),
        'descripcion' => $descripcion
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
