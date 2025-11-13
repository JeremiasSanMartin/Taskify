<?php
// config/connection.php
// Conexión PDO centralizada (lee credenciales desde db_config.php)
//en db_config.php debe haber datos de este estilo

/*

<?php
// config/db_config.php
// Datos locales o del servidor (NO subir a GitHub)

return [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'taskify'
];

*/

$config = include __DIR__ . '/db_config.php';

$host = $config['host'];
$user = $config['user'];
$password = $config['password'];
$database = $config['database'];

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true 
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}