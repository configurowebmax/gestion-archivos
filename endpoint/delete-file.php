<?php
include('../conn/conn.php');

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["file"])) {
    $fileID = $_GET["file"];

    // Get the file name from the database
    $sqlGetFileName = "SELECT file FROM tbl_file WHERE tbl_file_id = :fileID";
    $stmtGetFileName = $conn->prepare($sqlGetFileName);
    $stmtGetFileName->bindParam(':fileID', $fileID);
    $stmtGetFileName->execute();
    $fileName = $stmtGetFileName->fetchColumn();


    $uploadDirectory = "../file-uploads/";
    $filePath = $uploadDirectory . $fileName;

    if (file_exists($filePath) && unlink($filePath)) {
        // File in directory deleted successfully

        // Delete the file record from the database
        $sqlDeleteFile = "DELETE FROM tbl_file WHERE tbl_file_id = :fileID";
        $stmtDeleteFile = $conn->prepare($sqlDeleteFile);
        $stmtDeleteFile->bindParam(':fileID', $fileID);

        if ($stmtDeleteFile->execute()) {
            // File record deleted successfully
            echo "
            <script>
                alert('Archivo eliminaro correctamente!');
                window.location.href = '../index.php';
            </script>
            ";
            exit;
        } else {
            echo "
            <script>
                alert('Error al eliminar el registro del archivo de la base de datos.');
                window.location.href = '../index.php';
            </script>
            ";
        }
    } else {
        echo "
        <script>
            alert('Error eliminando el archivo.');
            window.location.href = '../index.php';
        </script>
        ";
    }
} else {
    echo "
    <script>
        alert('Solicitud no válida');
        window.location.href = '../index.php';
    </script>
    ";
}
