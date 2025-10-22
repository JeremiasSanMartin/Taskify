<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
    $email = $_SESSION['email'];

    // Validación básica
    if (empty($nombre) || empty($fecha_nacimiento)) {
        $error = "Por favor, completá todos los campos.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $nombre)) {
        $error = "El nombre solo puede contener letras y espacios.";
    } else {
        // Insertar usando stored procedure
        $stmt = $conn->prepare("CALL sp_insert_usuario(:nombre, :email, :fecha_nacimiento)");
        $stmt->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':fecha_nacimiento' => $fecha_nacimiento
        ]);

        $_SESSION['nombre'] = $nombre;
        $_SESSION['fecha_nacimiento'] = $fecha_nacimiento;

        header("Location: dashboard/index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Perfil - TASKIFY</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet"> <!-- Usamos tu hoja de estilos existente -->
</head>

<body>
    <div class="hero-section d-flex justify-content-center align-items-center">
        <div class="login-card">
            <div class="hero-logo">
                <i class="bi bi-star-fill"></i>
            </div>
            <h3 class="hero-title">Completar Perfil</h3>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3 text-start">
                    <label for="nombre" class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                        value="<?php echo htmlspecialchars($_SESSION['nombre_google'] ?? ''); ?>" required>
                </div>

                <div class="mb-4 text-start">
                    <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                </div>

                <button type="submit" class="google-login-btn w-100">Guardar y continuar</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>