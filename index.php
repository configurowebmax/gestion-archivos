<?php
session_start();
include('./conn/conn.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener la información del usuario logueado
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, role FROM tbl_users WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Si no se encuentra el usuario, destruir la sesión y redirigir al login
    session_destroy();
    header("Location: login.php");
    exit();
}

$username = $user['username'];
$role = $user['role'];

// Construir la consulta SQL según el rol del usuario
if ($role == 'admin') {
    $stmt = $conn->prepare("SELECT * FROM tbl_file");
} else {
    $stmt = $conn->prepare("SELECT * FROM tbl_file WHERE file_uploader = :username");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
}

$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar un token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Archivos</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Data Table -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">

    <link rel="icon" type="image/x-icon" href="assets/img/php-icon.png">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --secondary: #e0e7ff;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --border-radius: 0.5rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
        }

        body {
            background-color: #f1f5f9 !important;
            color: var(--dark);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .app-container {
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 1rem;
            width: 100%;
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }

        .app-title {
            font-weight: 600;
            font-size: 1.5rem;
            color: var(--dark);
            margin: 0;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: none;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            border-top: 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--gray);
            font-weight: 500;
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .table tbody td,
        .table tbody th {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            color: var(--dark);
            font-size: 0.9375rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table tbody tr:last-child td,
        .table tbody tr:last-child th {
            border-bottom: none;
        }

        .btn {
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: var(--primary-light);
            border-color: var(--primary-light);
        }

        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }

        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }

        .btn-info {
            background-color: var(--info);
            border-color: var(--info);
            color: white;
        }

        .btn-warning {
            background-color: var(--warning);
            border-color: var(--warning);
            color: white;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.25rem;
        }

        .action-btn {
            width: 2rem;
            height: 2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            color: white;
            margin: 0 0.125rem;
        }

        .modal-content {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.125rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: var(--dark);
        }

        .form-control {
            padding: 0.625rem 0.875rem;
            border-radius: 0.375rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            font-size: 0.9375rem;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
        }

        .form-control-file {
            padding: 0.5rem 0;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: var(--dark);
            font-size: 0.875rem;
            padding: 1rem 1.5rem;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            padding-top: 1rem;
        }

        .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .page-link {
            color: var(--primary);
        }

        .page-link:hover {
            color: var(--primary-light);
        }

        .badge {
            padding: 0.35em 0.65em;
            font-weight: 500;
            border-radius: 0.25rem;
        }

        .dataTables_wrapper input[type=search] {
            border-radius: 0.375rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 0.375rem 0.75rem;
        }

        .dataTables_wrapper select {
            border-radius: 0.375rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 0.375rem 0.75rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
        }

        .user-name {
            font-weight: 500;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        @media (max-width: 768px) {
            .app-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .action-btn {
                width: 1.75rem;
                height: 1.75rem;
                font-size: 0.75rem;
            }

            .table-responsive {
                padding: 0;
            }

            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_info,
            .dataTables_wrapper .dataTables_processing,
            .dataTables_wrapper .dataTables_paginate {
                padding: 0.75rem 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- Modifica la sección del user-info en tu app-header -->
        <div class="app-header">
            <h1 class="app-title">Gestión de Archivos</h1>
            <div class="dropdown">
                <div class="user-info d-flex align-items-center" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <?= substr($username, 0, 1) ?>
                    </div>
                    <div class="ms-2 me-2">
                        <div class="user-name"><?= htmlspecialchars($username) ?></div>
                        <div class="user-role"><?= htmlspecialchars($role) ?></div>
                    </div>
                    <i class="fas fa-chevron-down text-muted small"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width: 200px; border-radius: var(--border-radius);">
                    <li class="dropdown-item-text">
                        <div class="d-flex align-items-center py-1">
                            <div class="user-avatar me-2">
                                <?= substr($username, 0, 1) ?>
                            </div>
                            <div>
                                <div class="fw-medium"><?= htmlspecialchars($username) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($role) ?></div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item py-2" href="#"><i class="fas fa-user me-2 text-muted"></i> Mi perfil</a></li>
                    <li><a class="dropdown-item py-2" href="#"><i class="fas fa-cog me-2 text-muted"></i> Configuración</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item py-2 text-danger" href="./endpoint/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar sesión</a></li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Mis Archivos</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFileModal">
                    <i class="fas fa-plus me-1"></i> Nuevo Archivo
                </button>
            </div>
            <div class="table-responsive">
                <table class="table" id="fileTable">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Título</th>
                            <th scope="col">Archivo</th>
                            <th scope="col">Usuario</th>
                            <th scope="col">Fecha</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row):
                            $fileID       = htmlspecialchars($row['tbl_file_id']);
                            $fileTitle    = htmlspecialchars($row['file_title']);
                            $file         = htmlspecialchars($row['file']);
                            $fileUploader = htmlspecialchars($row['file_uploader']);
                            $dateUploaded = htmlspecialchars($row['date_uploaded']);
                            $dateFormatted = date('d/m/Y', strtotime($dateUploaded));
                        ?>
                            <tr class="fileList">
                                <th id="fileID-<?= $fileID ?>"><?= $fileID ?></th>
                                <td id="fileTitle-<?= $fileID ?>"><?= $fileTitle ?></td>
                                <td id="file-<?= $fileID ?>">
                                    <span class="text-truncate d-inline-block" style="max-width: 150px;">
                                        <?= $file ?>
                                    </span>
                                </td>
                                <td id="fileUploader-<?= $fileID ?>"><?= $fileUploader ?></td>
                                <td id="dateUploaded-<?= $fileID ?>"><?= $dateFormatted ?></td>
                                <td class="text-end">
                                    <button class="btn btn-success btn-sm action-btn" onclick="downloadFile(<?= $fileID ?>)" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-info btn-sm action-btn" onclick="updateFile(<?= $fileID ?>)" title="Editar">
                                        <i class="fas fa-pencil"></i>
                                    </button>
                                    <button class="btn btn-primary btn-sm action-btn" onclick="shareFile(<?= $fileID ?>)" title="Compartir">
                                        <i class="fas fa-share-alt"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm action-btn" onclick="deleteFile(<?= $fileID ?>)" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para Añadir Archivo -->
    <div class="modal fade" id="addFileModal" tabindex="-1" aria-labelledby="addFile" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFile">Añadir Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form action="./endpoint/add-file.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="fileTitle" class="form-label">Título del Archivo</label>
                            <input type="text" class="form-control" id="fileTitle" name="fileTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="file" class="form-label">Seleccionar Archivo</label>
                            <input type="file" class="form-control" id="file" name="file" required>
                        </div>
                        <div class="mb-3">
                            <label for="fileUploader" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="fileUploader" name="fileUploader" value="<?php echo htmlspecialchars($username); ?>" readonly>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Archivo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Actualizar Archivo -->
    <div class="modal fade" id="updateFileModal" tabindex="-1" aria-labelledby="updateFile" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateFile">Actualizar Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form action="./endpoint/update-file.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" class="form-control" id="updateFileID" name="fileID">
                        <div class="mb-3">
                            <label for="updateFileTitle" class="form-label">Título del Archivo</label>
                            <input type="text" class="form-control" id="updateFileTitle" name="fileTitle" required>
                        </div>
                        <div class="mb-3">
                            <label for="updateFile" class="form-label">Reemplazar Archivo (opcional)</label>
                            <input type="file" class="form-control" id="updateFile" name="file">
                        </div>
                        <div class="mb-3">
                            <label for="updateFileUploader" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="updateFileUploader" name="fileUploader" readonly>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Data Tables JS -->
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

    <!-- Script JS -->
    <script src="./assets/script.js"></script>

    <script>
        $(document).ready(function() {
            $('#fileTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json',
                },
                responsive: true,
                pageLength: 10,
                lengthMenu: [
                    [5, 10, 25, 50, -1],
                    [5, 10, 25, 50, "Todos"]
                ],
                columnDefs: [{
                        orderable: false,
                        targets: 5
                    } // Desactiva ordenación en columna de acciones
                ],
                order: [
                    [0, 'desc']
                ], // Ordenar por ID descendente por defecto
            });
        });

        // Redirige a la página de generación de enlace de compartir
        function shareFile(fileID) {
            window.location.href = `share_file.php?file_id=${fileID}`;
        }
    </script>

</body>

</html>