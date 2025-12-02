<?php
session_start();
require_once '../../includes/connection.php';

$grupo_id = $_POST['grupo_id'] ?? null;
$usuario_id = $_POST['usuario_id'] ?? null;

if (!$grupo_id || !$usuario_id) {
    header("Location: ../../index.php?error=datos_incompletos");
    exit();
}

// Marcar como inactivo (estado = 0)
$stmt = $conn->prepare("
    UPDATE grupousuario
    SET estado = 0
    WHERE grupo_id = :grupo AND usuario_id = :usuario
");
$stmt->execute([
    ':grupo' => $grupo_id,
    ':usuario' => $usuario_id
]);

header("Location: ../../index.php?mensaje=grupo_abandonado");
exit();
