<?php
// config.php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_coffeeshop_new";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
