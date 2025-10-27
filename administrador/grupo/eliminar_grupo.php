<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['email']) || !isset($_POST['id_grupo'])) {
    header("Location: ../dashboard/index.php");
    exit();
}

$email = $_SESSION['email'];
$id_grupo = $_POST['id_grupo'];

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
    echo "<p class='text-danger'>No tenés permisos para eliminar este grupo.</p>";
    exit();
}

// Eliminar grupo y sus relaciones
$conn->prepare("DELETE FROM grupousuario WHERE grupo_id = :id")->execute([':id' => $id_grupo]);
$conn->prepare("DELETE FROM tarea WHERE grupo_id = :id")->execute([':id' => $id_grupo]);
$conn->prepare("DELETE FROM grupo WHERE id_grupo = :id")->execute([':id' => $id_grupo]);

header("Location: ../../dashboard/index.php");
exit();
?>