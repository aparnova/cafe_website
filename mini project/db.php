<?php
$host = 'localhost';
$dbname = 'westleys_resto_cafe';
$username = 'root'; // Your MySQL username
$password = ''; // Your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}