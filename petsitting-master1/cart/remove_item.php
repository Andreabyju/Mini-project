<?php
session_start();
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['productId'])) {
        throw new Exception('Product ID is required');
    }

    $productId = $data['productId'];
    unset($_SESSION['cart'][$productId]);

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