<?php
$servername = "localhost";
$username = "admin";
$password = "1234";

try {
    // Create connection without selecting a database
    $conn = mysqli_connect($servername, $username, $password);
    
    // Check connection
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS petstore";
    if (mysqli_query($conn, $sql)) {
        echo "Database created successfully";
    } else {
        echo "Error creating database: " . mysqli_error($conn);
    }

    // Close connection
    mysqli_close($conn);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 