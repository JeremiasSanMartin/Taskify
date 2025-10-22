<?php
session_start();
require_once 'connection.php';

// Verificar sesión
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit;
}

// Capturar datos del formulario
$nombre = $_POST['nombre'] ?? '';
$edad = $_POST['edad'] ?? '';
$email = $_SESSION['email'];

// Insertar en la tabla usuario
try {
    $stmt = $conn->prepare("INSERT INTO usuario (nombre, email, edad) VALUES (:nombre, :email, :edad)");
    $stmt->execute([
        ':nombre' => $nombre,
        ':email' => $email,
        ':edad' => $edad
    ]);

    // Marcar al usuario como registrado y redirigir al dashboard
    $_SESSION['nombre'] = $nombre;
    $_SESSION['edad'] = $edad;
    header("Location: dashboard/index.php");
    exit;

} catch (PDOException $e) {
    die("Error al guardar datos: " . $e->getMessage());
}
