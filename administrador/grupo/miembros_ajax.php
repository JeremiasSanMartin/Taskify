<?php
session_start();
require_once __DIR__ . '/../../includes/connection.php';

if (!isset($_SESSION['email']) || !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

$id_grupo = $_GET['id'];

// Consultar miembros activos del grupo
$stmt = $conn->prepare("
    SELECT u.id_usuario, u.nombre, gu.rol
    FROM grupousuario gu
    JOIN usuario u ON gu.usuario_id = u.id_usuario
    WHERE gu.grupo_id = :grupo AND gu.estado = 1
");
$stmt->execute([':grupo' => $id_grupo]);
$miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($miembros);
