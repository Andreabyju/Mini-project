<?php
session_start();
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['productId']) || !isset($data['action'])) {
        throw new Exception('Invalid request');
    }

    $productId = $data['productId'];
    $action = $data['action'];

    if ($action === 'increase') {
        $_SESSION['cart'][$productId]++;
    } else if ($action === 'decrease') {
        if ($_SESSION['cart'][$productId] > 1) {
            $_SESSION['cart'][$productId]--;
        } else {
            unset($_SESSION['cart'][$productId]);
        }
    }

    echo json_encode([
        'success' => true
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 