<?php
session_start();
require_once '../../includes/connection.php';
header('Content-Type: application/json');

$id_recompensa = $_POST['id_recompensa'] ?? null;
$id_grupo = $_POST['id_grupo'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if (!$id_recompensa || !$id_grupo || !$userEmail) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    // Obtener id_usuario
    $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
    $stmt->execute([':email' => $userEmail]);
    $id_usuario = $stmt->fetchColumn();

    if (!$id_usuario) {
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }

    // Obtener id_grupo_usuario y puntaje actual
    $stmt = $conn->prepare("
        SELECT id_grupo_usuario, puntos 
        FROM grupousuario 
        WHERE grupo_id = :gid AND usuario_id = :uid AND estado = 1
    ");
    $stmt->execute([':gid' => $id_grupo, ':uid' => $id_usuario]);
    $grupoUsuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$grupoUsuario) {
        echo json_encode(['success' => false, 'error' => 'No pertenece al grupo']);
        exit;
    }

    $id_grupo_usuario = $grupoUsuario['id_grupo_usuario'];
    $puntaje_actual = (int) $grupoUsuario['puntos'];

    // Obtener datos de la recompensa
    $stmt = $conn->prepare("
        SELECT costo_puntos, disponibilidad 
        FROM recompensa 
        WHERE id_recompensa = :rid AND grupo_id = :gid
    ");
    $stmt->execute([':rid' => $id_recompensa, ':gid' => $id_grupo]);
    $recompensa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$recompensa) {
        echo json_encode(['success' => false, 'error' => 'Recompensa no encontrada']);
        exit;
    }

    $costo = (int) $recompensa['costo_puntos'];
    $stock = (int) $recompensa['disponibilidad'];

    // Validaciones
    if ($stock <= 0) {
        echo json_encode(['success' => false, 'error' => 'Recompensa agotada']);
        exit;
    }
    if ($puntaje_actual < $costo) {
        echo json_encode(['success' => false, 'error' => 'No tienes puntos suficientes']);
        exit;
    }

    // Actualizar stock
    $nuevo_stock = $stock - 1;
    $stmt = $conn->prepare("UPDATE recompensa SET disponibilidad = :stock WHERE id_recompensa = :rid");
    $stmt->execute([':stock' => $nuevo_stock, ':rid' => $id_recompensa]);

    // Actualizar puntaje
    $nuevo_puntaje = $puntaje_actual - $costo;
    $stmt = $conn->prepare("UPDATE grupousuario SET puntos = :puntos WHERE id_grupo_usuario = :gu_id");
    $stmt->execute([':puntos' => $nuevo_puntaje, ':gu_id' => $id_grupo_usuario]);

    echo json_encode([
        'success' => true,
        'id_recompensa' => $id_recompensa,
        'nuevo_stock' => $nuevo_stock,
        'puntos_restantes' => $nuevo_puntaje
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error en el servidor: ' . $e->getMessage()]);
}
