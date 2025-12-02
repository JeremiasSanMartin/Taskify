<?php
session_start();
require_once __DIR__ . "/../../includes/connection.php";

$id_grupo = $_GET['id'] ?? null;
if (!$id_grupo) {
    echo json_encode(['success' => false, 'error' => 'Falta el ID del grupo']);
    exit;
}

// Datos del grupo
$stmt = $conn->prepare("SELECT nombre, tipo, descripcion, codigo_invitacion FROM grupo WHERE id_grupo = :id");
$stmt->execute([':id' => $id_grupo]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo json_encode(['success' => false, 'error' => 'El grupo no existe']);
    exit;
}

// Miembros activos
$stmt = $conn->prepare("SELECT COUNT(*) FROM grupousuario WHERE grupo_id = :id AND estado = 1");
$stmt->execute([':id' => $id_grupo]);
$miembros_activos = (int) $stmt->fetchColumn();

// Expulsados/inactivos (opcional)
$stmt = $conn->prepare("SELECT COUNT(*) FROM grupousuario WHERE grupo_id = :id AND estado = 0");
$stmt->execute([':id' => $id_grupo]);
$miembros_inactivos = (int) $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'nombre' => $grupo['nombre'] ?? '',
    'tipo' => $grupo['tipo'] ?? '',
    'descripcion' => $grupo['descripcion'] ?? '',
    'codigo' => $grupo['codigo_invitacion'] ?? '',
    'miembros' => $miembros_activos,
    'expulsados' => $miembros_inactivos
]);
