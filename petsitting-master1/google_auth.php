<?php
session_start();
require_once "connect.php";

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['email'])) {
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Create new user if doesn't exist
            $stmt = $conn->prepare("INSERT INTO users (email, name) VALUES (:email, :name)");
            $stmt->execute([
                'email' => $data['email'],
                'name' => $data['name']
            ]);
            $user_id = $conn->lastInsertId();
        } else {
            $user_id = $user['user_id'];
        }

        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['email'] = $data['email'];
        $_SESSION['username'] = $data['name'];

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data received']);
} 