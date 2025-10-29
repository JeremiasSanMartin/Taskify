<?php
session_start();
require_once '../../includes/connection.php';

if (!isset($_SESSION['email'])) {
    header("Location: ../../index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
    $codigo_invitacion = strtoupper(substr(md5(uniqid()), 0, 6)); // Código aleatorio
    $fecha_creacion = date('Y-m-d');
    $usuario_id = $_SESSION['id_usuario'] ?? null;

    if (!$usuario_id && isset($_SESSION['email'])) {
        // Buscar el ID del usuario por email
        $stmt = $conn->prepare("SELECT id_usuario FROM usuario WHERE email = :email");
        $stmt->execute([':email' => $_SESSION['email']]);
        $usuario_id = $stmt->fetchColumn();

        // Se lo guarda en sesión para futuras acciones
        $_SESSION['id_usuario'] = $usuario_id;
    }

    // Validación básica
    if (empty($nombre) || empty($tipo) || empty($descripcion)) {
        $error = "Por favor, completá todos los campos.";
    } elseif (!in_array($tipo, ['familiar', 'personal', 'laboral'])) {
        $error = "El tipo de grupo no es válido.";
    } elseif (!$usuario_id) {
        $error = "No se pudo identificar al usuario.";
    } else {
        try {
            $stmt = $conn->prepare("CALL sp_crear_grupo(:nombre, :tipo, :descripcion, :codigo_invitacion, :usuario_id, :fecha_creacion)");
            $stmt->execute([
                ':nombre' => $nombre,
                ':tipo' => $tipo,
                ':descripcion' => $descripcion,
                ':codigo_invitacion' => $codigo_invitacion,
                ':usuario_id' => $usuario_id,
                ':fecha_creacion' => $fecha_creacion
            ]);

            $_SESSION['grupo_creado'] = true;
            $_SESSION['codigo_invitacion'] = $codigo_invitacion;

            header("Location: ../../index.php");
            exit;
        } catch (PDOException $e) {
            $error = "Error al crear el grupo. Intentá nuevamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Crear Grupo - TASKIFY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <div class="hero-section d-flex justify-content-center align-items-center">
        <div class="login-card">
            <h3 class="hero-title">Crear nuevo grupo</h3>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3 text-start">
                    <label for="nombre" class="form-label">Nombre del grupo</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                </div>

                <div class="mb-3 text-start">
                    <label for="tipo" class="form-label">Tipo de grupo</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="">Seleccionar tipo</option>
                        <option value="familiar">Familiar</option>
                        <option value="laboral">Laboral</option>
                        <option value="personal">Personal</option>
                    </select>
                </div>

                <div class="mb-4 text-start">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                </div>

                <button type="submit" class="google-login-btn w-100">Crear grupo</button>
            </form>
        </div>
    </div>
</body>

</html>