<?php
session_start();
require_once '../../includes/connection.php';

$error = null;
$codigo = $_POST['codigo_invitacion'] ?? null;
$userEmail = $_SESSION['email'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$codigo || !$userEmail) {
        $error = "Ingresá el código de invitación.";
    } else {
        // Buscar grupo por código
        $stmt = $conn->prepare("SELECT id_grupo FROM grupo WHERE codigo_invitacion = :codigo");
        $stmt->execute([':codigo' => $codigo]);
        $grupo_id = $stmt->fetchColumn();

        if (!$grupo_id) {
            $error = "Código inválido.";
        } else {
            // Obtener ID del usuario
            $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
            $stmt->execute([':email' => $userEmail]);
            $usuario_id = $stmt->fetchColumn();

            // Verificar si ya está en el grupo
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM grupousuario
                WHERE grupo_id = :gid AND usuario_id = :uid
            ");
            $stmt->execute([':gid' => $grupo_id, ':uid' => $usuario_id]);
            $ya_esta = $stmt->fetchColumn();

            if ($ya_esta) {
                // Actualizar rol y estado si ya está en el grupo
                $stmt = $conn->prepare("
                    UPDATE grupousuario
                    SET rol = 'colaborador', estado = 1
                    WHERE usuario_id = :uid AND grupo_id = :gid
                ");
                $stmt->execute([':uid' => $usuario_id, ':gid' => $grupo_id]);
            
                header("Location: ver_grupo.php?id=$grupo_id");
                exit();
            } else {
                // Insertar como colaborador
                $stmt = $conn->prepare("
                    INSERT INTO grupousuario (usuario_id, grupo_id, rol, fecha_ingreso, estado, puntos)
                    VALUES (:uid, :gid, 'colaborador', NOW(), 1, 0)
                ");
                $stmt->execute([':uid' => $usuario_id, ':gid' => $grupo_id]);

                header("Location: /Taskify/colaborador/grupo/ver_grupo.php?id=$grupo_id");
                exit();
            }
        }
    }
}
?>

<!-- HTML del formulario -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Unirse a Grupo - TASKIFY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>


<body>
    <main class="main-content">
        <div class="hero-section d-flex justify-content-center align-items-center">
            <div class="login-card">
                <h3 class="hero-title">Unirse a un Grupo</h3>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3 text-start">
                        <label for="codigo" class="form-label">Código de invitación</label>
                        <input type="text" class="form-control" id="codigo" name="codigo_invitacion" required>
                    </div>

                    <button type="submit" class="google-login-btn w-100">Unirme</button>
                </form>
            </div>
        </div>

    </main>
</body>

</html>