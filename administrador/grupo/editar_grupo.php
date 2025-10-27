<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['email']) || !isset($_POST['id_grupo'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

$email = $_SESSION['email'];
$id_grupo = $_POST['id_grupo'];
$nombre = trim($_POST['nombre']);
$tipo = strtolower(trim($_POST['tipo']));

// Verificar que el usuario sea administrador del grupo
$stmt = $conn->prepare("
    SELECT gu.rol
    FROM grupousuario gu
    JOIN usuario u ON gu.usuario_id = u.id_usuario
    WHERE gu.grupo_id = :id AND u.email = :email
");
$stmt->execute([':id' => $id_grupo, ':email' => $email]);
$rol = $stmt->fetchColumn();

if ($rol !== 'administrador') {
    echo "<p class='text-danger'>No tenés permisos para editar este grupo.</p>";
    exit();
}

// Actualizar grupo
$stmt = $conn->prepare("UPDATE grupo SET nombre = :nombre, tipo = :tipo WHERE id_grupo = :id");
$stmt->execute([':nombre' => $nombre, ':tipo' => $tipo, ':id' => $id_grupo]);

header("Location: ver_grupo.php?id=" . $id_grupo);
exit();
?>