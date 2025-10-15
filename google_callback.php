<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
session_start();

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope('email');
$client->addScope('profile');

if (!isset($_GET['code'])) {
    echo "No se recibió el código de autorización de Google.";
    exit;
}

try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
} catch (Exception $e) {
    echo "Excepción fetchAccessTokenWithAuthCode: " . htmlspecialchars($e->getMessage());
    exit;
}

// Comprobación de errores en la respuesta
if (isset($token['error'])) {
    echo "Error al obtener token: " . htmlspecialchars($token['error']);
    // para debugging añade:
    if (isset($token['error_description'])) {
        echo "<br>Descripción: " . htmlspecialchars($token['error_description']);
    }
    exit;
}

// Setear token (puede ser array o string)
$client->setAccessToken($token);

// Obtener info del usuario
try {
    $oauth2 = new Google_Service_Oauth2($client);
    $googleUser = $oauth2->userinfo->get();
} catch (Exception $e) {
    echo "Error al obtener datos del usuario: " . htmlspecialchars($e->getMessage());
    exit;
}

// Guardar en sesión
$_SESSION['user_id'] = $googleUser->id;
$_SESSION['name'] = $googleUser->name;
$_SESSION['email'] = $googleUser->email;
$_SESSION['picture'] = $googleUser->picture;

// Redirigir al dashboard
header('Location: dashboard/index.php');
exit;
