<?php
$servername = "localhost";
$username = "root";
$password = "";
$db = "gestion-archivos";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$db;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Failed " . $e->getMessage();
}
?>
