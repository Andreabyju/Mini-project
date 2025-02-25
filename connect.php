<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $host = "localhost";
    $dbname = "petstore";
    $username = "admin";
    $password = "1234";

    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connection successful");
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}
?>