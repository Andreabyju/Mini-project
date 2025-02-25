<?php
$servername = "localhost";
$username = "root"; // Using root user to create database
$password = ""; // Your root password here

try {
    // Create connection without database
    $conn = new mysqli($servername, $username, $password);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS petstore";
    if ($conn->query($sql) === TRUE) {
        echo "Database created successfully<br>";
    } else {
        echo "Error creating database: " . $conn->error . "<br>";
    }
    
    $conn->close();
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 