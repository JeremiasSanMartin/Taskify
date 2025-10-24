<?php
// connection.php
// Archivo centralizado de conexión a la base de datos Taskify
// Estos valores se tendran que modificar si la pagina se sube a algun luhar como hostinger
$host = "localhost";      
$user = "root";           
$password = "root";          
$database = "taskify";    // Nombre de la base de datos

try {
    // Crear conexión PDO
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $password);

    // Configurar atributos de error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Si hay error, mostrar mensaje
    die("Error de conexión: " . $e->getMessage());
}
?>