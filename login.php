<?php
session_start();
include("./conn/conn.php");

// Verificar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enteredUsername = trim($_POST["username"]);
    $enteredPassword = trim($_POST["password"]);

    // Sanitizar entradas
    $enteredUsername = filter_var($enteredUsername, FILTER_SANITIZE_STRING);

    // Preparar la consulta para seleccionar el hash de la contraseña
    $stmt = $conn->prepare("SELECT user_id, password FROM tbl_users WHERE username = :username");
    $stmt->bindParam(':username', $enteredUsername, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($enteredPassword, $user['password'])) {
        // Iniciar sesión y redirigir al usuario
        $_SESSION['user_id'] = $user['user_id'];
        header("Location: index.php");
        exit();
    } else {
        $errorMessage = "Usuario o contraseña incorrecta";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <?php include('assets/head.php'); ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3a86ff;
            --bg-color: #f8f9fa;
            --card-color: #ffffff;
            --text-color: #333;
            --text-muted: #6c757d;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background-color: var(--card-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
        }

        .login-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .login-title {
            font-weight: 600;
            font-size: 1.5rem;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 400;
        }

        .form-floating {
            margin-bottom: 1.25rem;
        }

        .form-control {
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            height: auto;
            font-size: 0.95rem;
            border: 1px solid #dee2e6;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(58, 134, 255, 0.15);
            border-color: var(--primary-color);
        }

        .form-floating>label {
            padding: 0.75rem 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem 1rem;
            font-weight: 500;
            border-radius: var(--border-radius);
            width: 100%;
            margin-top: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #2970e6;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            border: none;
        }

        .input-group-text {
            background-color: transparent;
            border-left: none;
            cursor: pointer;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            z-index: 10;
            background: none;
            border: none;
            opacity: 0.7;
        }

        .toggle-password:hover {
            opacity: 1;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo {
            height: 48px;
            width: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-card">
            <div class="logo-container">
                <img src="assets/img/php-icon.png" alt="Logo" class="logo">
            </div>
            <div class="login-header">
                <h1 class="login-title">Bienvenido</h1>
                <p class="login-subtitle">Sistema de Gestión de Productos</p>
            </div>

            <?php if (isset($errorMessage)) : ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form action="#" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Usuario" required>
                    <label for="username"><i class="fas fa-user me-2"></i>Usuario</label>
                </div>
                <div class="form-floating mb-3 password-container">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                    <label for="password"><i class="fas fa-lock me-2"></i>Contraseña</label>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-primary">
                    Iniciar sesión
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>