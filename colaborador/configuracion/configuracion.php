<?php
session_start();
require_once __DIR__ . "/../../includes/connection.php";

header('Content-Type: application/json');

$id_grupo = $_GET['id'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if (!$id_grupo || !$userEmail) {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros']);
    exit;
}

// Obtener ID del usuario
$stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
$stmt->execute([':email' => $userEmail]);
$usuario_id = $stmt->fetchColumn();

if (!$usuario_id) {
    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    exit;
}

// Verificar que el usuario sea colaborador activo
$stmt = $conn->prepare("
    SELECT rol, estado FROM grupousuario
    WHERE grupo_id = :gid AND usuario_id = :uid
");
$stmt->execute([':gid' => $id_grupo, ':uid' => $usuario_id]);
$gu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$gu || $gu['rol'] !== 'colaborador' || (int)$gu['estado'] !== 1) {
    echo json_encode(['success' => false, 'error' => 'Acceso restringido']);
    exit;
}

// Datos del grupo
$stmt = $conn->prepare("
    SELECT nombre, tipo, descripcion, codigo_invitacion
    FROM grupo WHERE id_grupo = :id
");
$stmt->execute([':id' => $id_grupo]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo json_encode(['success' => false, 'error' => 'Grupo no encontrado']);
    exit;
}

echo json_encode([
    'success' => true,
    'nombre' => $grupo['nombre'] ?? '',
    'tipo' => $grupo['tipo'] ?? '',
    'descripcion' => $grupo['descripcion'] ?? '',
    'codigo' => $grupo['codigo_invitacion'] ?? ''
]);
