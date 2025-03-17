<?php
session_start();
header('Content-Type: application/json');

try {
    // Get the POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['productId'])) {
        throw new Exception('Product ID is required');
    }

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add/update product in cart
    $productId = $data['productId'];
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]++;
    } else {
        $_SESSION['cart'][$productId] = 1;
    }

    // Calculate total items in cart
    $totalItems = array_sum($_SESSION['cart']);

    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart',
        'cartTotal' => $totalItems
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 