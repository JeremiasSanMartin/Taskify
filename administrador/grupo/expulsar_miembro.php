<?php
session_start();
require_once '../../includes/connection.php';

if (!isset($_SESSION['email']) || !isset($_POST['grupo_id']) || !isset($_POST['usuario_id'])) {
    echo "Datos incompletos.";
    exit();
}

$email = $_SESSION['email'];
$id_grupo = $_POST['grupo_id'];
$id_usuario_expulsado = $_POST['usuario_id'];

// Verificar que el usuario actual sea admin del grupo
$stmt = $conn->prepare("
    SELECT gu.rol
    FROM grupousuario gu
    JOIN usuario u ON gu.usuario_id = u.id_usuario
    WHERE gu.grupo_id = :grupo AND u.email = :email AND gu.estado = 1
");
$stmt->execute([':grupo' => $id_grupo, ':email' => $email]);
$rol = $stmt->fetchColumn();

if ($rol !== 'administrador') {
    echo "No tenés permisos para expulsar miembros.";
    exit();
}

// Marcar como inactivo
$stmt = $conn->prepare("
    UPDATE grupousuario
    SET estado = 0
    WHERE grupo_id = :grupo AND usuario_id = :usuario
");
$stmt->execute([':grupo' => $id_grupo, ':usuario' => $id_usuario_expulsado]);

header("Location: ver_grupo.php?id=" . urlencode($id_grupo) . "&expulsion=ok");
exit();
?>
