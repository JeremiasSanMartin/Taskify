<?php
session_start();
require_once '../../includes/connection.php';

$id_recompensa = $_POST['id_recompensa'] ?? null;
$id_grupo = $_POST['id_grupo'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if ($id_recompensa && $id_grupo && $userEmail) {
    try {
        // Verificar que sea administrador
        $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
        $stmt->execute([':email' => $userEmail]);
        $id_usuario = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT id_grupo_usuario, rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
        $stmt->execute([':gid' => $id_grupo, ':uid' => $id_usuario]);
        $guAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$guAdmin || $guAdmin['rol'] !== 'administrador') {
            $_SESSION['mensaje'] = [
                'tipo' => 'danger',
                'texto' => '⚠️ No estás autorizado para eliminar recompensas.'
            ];
            header("Location: ../grupo/ver_grupo.php?id=$id_grupo&section=recompensas");
            exit;
        }

        // Eliminar recompensa
        $stmt = $conn->prepare("DELETE FROM recompensa WHERE id_recompensa = :id");
        $stmt->execute([':id' => $id_recompensa]);

        $_SESSION['mensaje'] = [
            'tipo' => 'success',
            'texto' => '🗑️ Recompensa eliminada correctamente.'
        ];

        // historial (estadoTarea = 12 → recompensa eliminada)
        $stmt = $conn->prepare("INSERT INTO historialgrupousuario 
    (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
    VALUES (CURDATE(), 0, NULL, 12, :gu_id_admin, NULL, :rid)");
        $stmt->execute([
            ':gu_id_admin' => $guAdmin['id_grupo_usuario'],
            ':rid' => $id_recompensa
        ]);
        header("Location: ../grupo/ver_grupo.php?id=$id_grupo&section=recompensas");


        exit;
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = [
            'tipo' => 'danger',
            'texto' => '❌ Error al eliminar recompensa: ' . $e->getMessage()
        ];
        header("Location: ../grupo/ver_grupo.php?id=$id_grupo&section=recompensas");
        exit;
    }
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'danger',
        'texto' => '❌ Datos incompletos para eliminar la recompensa.'
    ];
    header("Location: ../grupo/ver_grupo.php?id=$id_grupo&section=recompensas");
    exit;
}
