<?php
include('../conn/conn.php');
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM tbl_users WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

$username = $user['username'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fileTitle = trim($_POST['fileTitle']);
    $fileUploader = $username; // Usar el nombre de usuario logueado
    $file = $_FILES['file'];

    // Verificar que el archivo se ha subido correctamente
    if ($file['error'] == UPLOAD_ERR_OK) {
        $fileName = basename($file['name']);
        $targetFilePath = "../file-uploads/" . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            $stmt = $conn->prepare("INSERT INTO tbl_file (file_title, file, file_uploader) VALUES (:fileTitle, :file, :fileUploader)");
            $stmt->bindParam(':fileTitle', $fileTitle);
            $stmt->bindParam(':file', $fileName);
            $stmt->bindParam(':fileUploader', $fileUploader);
            $stmt->execute();
            header("Location: ../index.php");
        } else {
            echo "Error al subir el archivo.";
        }
    } else {
        echo "Error al procesar el archivo.";
    }
}
