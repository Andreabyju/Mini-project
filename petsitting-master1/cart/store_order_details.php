<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store order confirmation details in session
    $_SESSION['order_confirmation'] = [
        'order_id' => $_POST['order_id'],
        'total' => $_POST['total'],
        'date' => date('Y-m-d H:i:s'),
        'name' => $_POST['name']
    ];
    
    // Clear the cart
    $_SESSION['cart'] = [];
    
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 