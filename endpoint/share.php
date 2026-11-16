<?php
// endpoint/share.php

// 1. Conexión a la base de datos
include __DIR__ . '/../conn/conn.php';

// 2. Comprobar que venga el token
if (empty($_GET['token'])) {
    echo "Enlace inválido.";
    exit;
}
$token = $_GET['token'];

// 3. Buscar el share link y el archivo asociado
$stmt = $conn->prepare("
    SELECT sl.*, f.file_title, f.tbl_file_id, f.file
      FROM share_links sl
      JOIN tbl_file f ON f.tbl_file_id = sl.file_id
     WHERE sl.token = :token
");
$stmt->execute([':token' => $token]);
$share = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$share) {
    echo "Enlace inválido.";
    exit;
}

// 4. Comprobar expiración
if (strtotime($share['expires_at']) < time()) {
    $expired = true;
} else {
    $expired = false;
}

// 5. Lógica de login del receptor
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$expired) {
    $userInput = $_POST['username'] ?? '';
    $passInput = $_POST['password'] ?? '';
    if (
        $userInput === $share['username']
        && password_verify($passInput, $share['password_hash'])
    ) {
        // Credenciales correctas: mostrar enlace de descarga
        $success = true;
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}

// Obtener información del archivo para mostrar
$fileID = $share['file_id'];
$fileTitle = htmlspecialchars($share['file_title']);
$fileName = htmlspecialchars($share['file']);
$downloadUrl = "download-file.php?fileID={$fileID}";

// Obtener extensión para mostrar el ícono apropiado
$extension = pathinfo($fileName, PATHINFO_EXTENSION);
$fileIcon = getFileIcon($extension);

// Función para determinar el ícono según extensión
function getFileIcon($ext)
{
    $ext = strtolower($ext);

    $icons = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel',
        'xlsx' => 'fa-file-excel',
        'ppt' => 'fa-file-powerpoint',
        'pptx' => 'fa-file-powerpoint',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image',
        'txt' => 'fa-file-alt',
        'zip' => 'fa-file-archive',
        'rar' => 'fa-file-archive'
    ];

    return isset($icons[$ext]) ? $icons[$ext] : 'fa-file';
}

// Formatear fecha de expiración
$expiryDate = date('d/m/Y H:i', strtotime($share['expires_at']));

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a Archivo Compartido</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark: #1e293b;
            --gray: #94a3b8;
            --light: #f8fafc;
            --border-radius: 0.5rem;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f1f5f9;
            padding: 1rem;
        }

        .share-container {
            max-width: 500px;
            width: 100%;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: none;
            overflow: hidden;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-footer {
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .file-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .file-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .file-meta {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .form-control {
            border-radius: 0.375rem;
            padding: 0.625rem 0.875rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 0.9375rem;
            transition: all 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        .input-group-text {
            background-color: #f8fafc;
            border-color: rgba(0, 0, 0, 0.1);
            border-right: none;
            color: var(--gray);
        }

        .btn {
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }

        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }

        .text-center {
            text-align: center;
        }

        .alert {
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .alert-warning {
            background-color: #fef3c7;
            border: 1px solid #fde68a;
            color: #d97706;
        }

        .download-card {
            text-align: center;
            padding: 1.5rem;
        }

        .download-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1.5rem;
        }

        .download-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: var(--success);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }

        .download-btn:hover {
            background-color: #059669;
            color: white;
        }

        .expired-icon {
            font-size: 4rem;
            color: var(--warning);
            margin-bottom: 1.5rem;
        }

        @media (max-width: 576px) {
            .file-title {
                font-size: 1.125rem;
            }

            .card-header,
            .card-body,
            .card-footer {
                padding: 1.25rem;
            }
        }
    </style>
</head>

<body>
    <div class="share-container">
        <?php if ($expired): ?>
            <!-- Caso de enlace expirado -->
            <div class="card">
                <div class="download-card">
                    <div class="expired-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h2 class="download-title">El enlace ha expirado</h2>
                    <p class="mb-4">Este enlace ya no está disponible porque ha superado su fecha de expiración.</p>
                    <p class="text-muted">Fecha de expiración: <?= $expiryDate ?></p>
                </div>
            </div>

        <?php elseif ($success): ?>
            <!-- Caso de acceso exitoso -->
            <div class="card">
                <div class="download-card">
                    <div class="download-icon">
                        <i class="fas <?= $fileIcon ?>"></i>
                    </div>
                    <h2 class="download-title"><?= $fileTitle ?></h2>
                    <p class="mb-4">El archivo está listo para descargar</p>
                    <a href="<?= $downloadUrl ?>" class="download-btn">
                        <i class="fas fa-download"></i> Descargar archivo
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Formulario de acceso -->
            <div class="card">
                <div class="card-header text-center">
                    <div class="file-icon">
                        <i class="fas <?= $fileIcon ?>"></i>
                    </div>
                    <h2 class="file-title"><?= $fileTitle ?></h2>
                    <div class="file-meta">
                        <p>Este archivo está protegido con contraseña</p>
                        <small>Disponible hasta: <?= $expiryDate ?></small>
                    </div>
                </div>

                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nombre de usuario</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" id="username" name="username" class="form-control" placeholder="Usuario" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Contraseña" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i> Acceder al archivo
                        </button>
                    </form>
                </div>

                <div class="card-footer text-center">
                    <small class="text-muted">Este es un archivo compartido. Si tienes problemas para acceder, contacta con la persona que te lo compartió.</small>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>