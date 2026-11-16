<?php
session_start();

// 1. Incluye la conexión con PDO ($conn)
include __DIR__ . '/conn/conn.php';

// 2. Verifica sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 3. (Opcional) Incluye tu cabecera
// Si tu "topbar.php" en realidad es assets/header.php:


// ——————————————————————————————————
// LÓGICA DE GENERACIÓN DE ENLACE SHARE
// ——————————————————————————————————
$shareSuccess = false;
$shareLink = '';
$expiresAt = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file_id    = intval($_POST['file_id']);
    $share_user = trim($_POST['share_username']);
    $share_pass = $_POST['share_password'];

    // Generar token
    $token = bin2hex(random_bytes(16));
    $hash  = password_hash($share_pass, PASSWORD_DEFAULT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 day'));

    // Guarda en la tabla share_links
    $stmt = $conn->prepare("
        INSERT INTO share_links
          (file_id, token, username, password_hash, expires_at)
        VALUES
          (:file_id, :token, :user, :hash, :expires)
    ");
    $stmt->execute([
        ':file_id' => $file_id,
        ':token'   => $token,
        ':user'    => $share_user,
        ':hash'    => $hash,
        ':expires' => $expires_at,
    ]);

    // Construye el enlace apuntando al endpoint/share.php
    $base  = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://')
        . $_SERVER['HTTP_HOST']
        . dirname($_SERVER['REQUEST_URI']);
    $link  = "{$base}/endpoint/share.php?token={$token}";

    $shareSuccess = true;
    $shareLink = $link;
    $expiresAt = $expires_at;
}

// Mostrar el formulario por GET
$file_id = intval($_GET['file_id'] ?? 0);
$stmt    = $conn->prepare("SELECT * FROM tbl_file WHERE tbl_file_id = :id");
$stmt->execute([':id' => $file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$file) {
    echo "Archivo no encontrado.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compartir Archivo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --secondary-color: #f8f9fa;
            --text-color: #212529;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: #f5f7fa !important;
            color: var(--text-color);
        }

        .share-container {
            max-width: 700px;
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 2rem;
            margin: 3rem auto;
        }

        .file-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .file-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-right: 1rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            flex-grow: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .form-control {
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            transition: var(--transition);
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
            border-color: var(--primary-color);
        }

        .input-group-text {
            background-color: var(--secondary-color);
            border-radius: var(--border-radius) 0 0 var(--border-radius);
            border: 1px solid #ddd;
            border-right: none;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: #e9ecef;
            border-color: #e9ecef;
            color: #212529;
        }

        .btn-secondary:hover {
            background-color: #dde2e6;
            border-color: #dde2e6;
        }

        .share-success {
            background-color: #d1e7dd;
            color: #0f5132;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .share-link {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 1rem;
            word-break: break-all;
        }

        .copy-btn {
            cursor: pointer;
            color: var(--primary-color);
        }

        .copy-btn:hover {
            color: var(--primary-hover);
        }

        @media (max-width: 768px) {
            .share-container {
                margin: 1rem;
                padding: 1.5rem;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="share-container">
        <?php if ($shareSuccess): ?>
            <div class="share-success">
                <h4><i class="fas fa-check-circle me-2"></i>Enlace generado correctamente</h4>
                <p class="mb-1">Expira en: <?= date('d/m/Y H:i', strtotime($expiresAt)) ?></p>
            </div>
            <div class="share-link">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>Enlace de acceso:</strong>
                    <span class="copy-btn" onclick="copyToClipboard('<?= $shareLink ?>')">
                        <i class="fas fa-copy"></i> Copiar
                    </span>
                </div>
                <a href="<?= $shareLink ?>" target="_blank" class="text-break"><?= $shareLink ?></a>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <a href="index.php" class="btn btn-primary">Volver a Archivos</a>
            </div>
        <?php else: ?>
            <div class="file-header">
                <div class="file-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h1>Compartir: <?= htmlspecialchars($file['file_title']) ?></h1>
            </div>

            <form method="post">
                <input type="hidden" name="file_id" value="<?= $file_id ?>">

                <div class="mb-3">
                    <label for="share_username" class="form-label">Usuario para compartir</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" id="share_username" name="share_username" class="form-control" placeholder="Nombre de usuario" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="share_password" class="form-label">Contraseña de acceso</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" id="share_password" name="share_password" class="form-control" placeholder="Contraseña segura" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between button-group">
                    <a href="index.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-share-alt me-1"></i> Generar enlace
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Enlace copiado al portapapeles');
            }).catch(err => {
                console.error('Error al copiar: ', err);
            });
        }
    </script>
</body>

</html>