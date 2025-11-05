<?php
session_start();
require_once '../../includes/connection.php';

$id_recompensa = $_POST['id_recompensa'] ?? null;
$nombre = $_POST['nombre'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$costo_puntos = $_POST['costo_puntos'] ?? 0;
$id_grupo = $_POST['id_grupo'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if ($id_recompensa && $nombre && $costo_puntos > 0 && $id_grupo && $userEmail) {
    try {
        // Verificar que sea administrador
        $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
        $stmt->execute([':email' => $userEmail]);
        $id_usuario = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT rol FROM grupousuario WHERE grupo_id = :gid AND usuario_id = :uid");
        $stmt->execute([':gid' => $id_grupo, ':uid' => $id_usuario]);
        $rol = $stmt->fetchColumn();

        if ($rol !== 'administrador') {
            $_SESSION['mensaje'] = [
                'tipo' => 'danger',
                'texto' => '⚠️ No estás autorizado para editar recompensas.'
            ];
            header("Location: ../grupo/ver_grupo.php?id=$id_grupo&accion=recompensas");
            exit;
        }

        // Actualizar recompensa
        $stmt = $conn->prepare("UPDATE recompensa
                                SET nombre = :nombre, descripcion = :descripcion, costo_puntos = :costo_puntos
                                WHERE id_recompensa = :id");
        $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':costo_puntos' => $costo_puntos,
            ':id' => $id_recompensa
        ]);

        $_SESSION['mensaje'] = [
            'tipo' => 'success',
            'texto' => '✅ Recompensa actualizada correctamente.'
        ];

        // historial (estadoTarea = 11 → recompensa editada)
        $stmt = $conn->prepare("INSERT INTO historialgrupousuario 
    (fecha, puntosOtorgados, puntosCanjeados, estadoTarea, grupo_usuario_id, tarea_id_tarea, recompensa_id_recompensa)
    VALUES (CURDATE(), 0, NULL, 11, :gu_id_admin, NULL, :rid)");
        $stmt->execute([
            ':gu_id_admin' => $guAdmin['id_grupo_usuario'],
            ':rid' => $id_recompensa
        ]);
        header("Location: ../grupo/ver_grupo.php?id=$id_grupo&accion=recompensas");
        exit;
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = [
            'tipo' => 'danger',
            'texto' => '❌ Error al editar recompensa: ' . $e->getMessage()
        ];
        header("Location: ../grupo/ver_grupo.php?id=$id_grupo&acccion=recompensas");
        exit;
    }
} else {
    $_SESSION['mensaje'] = [
        'tipo' => 'danger',
        'texto' => '❌ Datos incompletos para editar la recompensa.'
    ];
    header("Location: ../grupo/ver_grupo.php?id=$id_grupo&accion=recompensas");
    exit;
}
