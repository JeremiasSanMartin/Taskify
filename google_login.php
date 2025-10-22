<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);       // Tomado del .env
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']); // Tomado del .env
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);   // Tomado del .env
$client->addScope('email');
$client->addScope('profile');
$client->setAccessType('offline'); // opcional si necesitás refresh token
$client->setPrompt('select_account consent');

// Crear URL de autorización
$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;
