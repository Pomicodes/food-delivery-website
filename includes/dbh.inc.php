<?php 

$host = "127.0.0.1";
$port = "3306";
$dbname = "fooddelievery";
$dbusrn = "root";
$dbpwd = "";  // Default XAMPP password is empty

// Correct DSN format
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbusrn, $dbpwd);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: echo "Connected successfully";
} catch(PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
