<?php
session_start();
require_once __DIR__ . '/../../includes/connection.php';

header('Content-Type: application/json');

$id_grupo = $_GET['id_grupo'] ?? null;
if (!$id_grupo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Grupo no especificado']);
    exit();
}

$stmt = $conn->prepare("
    SELECT u.id_usuario, 
           CASE 
             WHEN gu.rol = 'administrador' THEN CONCAT(u.nombre, ' (admin)')
             ELSE u.nombre
           END AS nombre,
           gu.rol
    FROM grupousuario gu
    JOIN usuario u ON gu.usuario_id = u.id_usuario
    WHERE gu.grupo_id = :id AND gu.estado = 1
");
$stmt->execute([':id' => $id_grupo]);
$miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($miembros);

