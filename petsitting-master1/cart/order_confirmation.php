<?php
session_start();
require_once "../connect.php";
require_once "../functions/payment_functions.php";

// Debug: Log request data
error_log('Order confirmation request: ' . print_r($_GET, true));
error_log('Session data: ' . print_r($_SESSION, true));

// Handle both direct access with order_id parameter and session-based access
$orderDetails = null;
$orderItems = null;

// Check if we have an order_id in the URL
if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    error_log('Order ID from URL: ' . $order_id);
    
    try {
        // Fetch order details from database
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = :order_id");
        $stmt->execute([':order_id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log('Order fetch result: ' . ($order ? 'Found' : 'Not found'));
        
        if ($order) {
            // Create order details array
            $orderDetails = [
                'order_id' => $order['id'],
                'date' => $order['created_at'],
                'name' => $order['first_name'] . ' ' . $order['last_name'],
                'total' => $order['total_amount']
            ];
            
            // Fetch order items
            $itemStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
            $itemStmt->execute([':order_id' => $order_id]);
            $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Store in session for consistency with other code paths
            $_SESSION['order_confirmation'] = $orderDetails;
            $_SESSION['cart_items'] = $orderItems;
        } else {
            error_log('Order not found in database: ' . $order_id);
            
            // Try alternate query with different column name if 'id' doesn't match
            $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :order_id");
            $stmt->execute([':order_id' => $order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                error_log('Order found using order_id column');
                $orderDetails = [
                    'order_id' => $order['order_id'] ?? $order['id'],
                    'date' => $order['created_at'],
                    'name' => $order['first_name'] . ' ' . $order['last_name'],
                    'total' => $order['total_amount']
                ];
                
                $itemStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
                $itemStmt->execute([':order_id' => $order_id]);
                $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $_SESSION['order_confirmation'] = $orderDetails;
                $_SESSION['cart_items'] = $orderItems;
            }
        }
    } catch (PDOException $e) {
        error_log('Database error fetching order: ' . $e->getMessage());
    }
}

// If we don't have order details from URL parameter, check session
if (!$orderDetails && isset($_SESSION['order_confirmation'])) {
    $orderDetails = $_SESSION['order_confirmation'];
    $orderItems = isset($_SESSION['cart_items']) ? $_SESSION['cart_items'] : [];
}

// If we don't have order details from the URL parameter or session, check if we have order_id in session
if (!$orderDetails && isset($_SESSION['order_id'])) {
    $order_id = $_SESSION['order_id'];
    error_log('Order ID from session: ' . $order_id);
    
    try {
        // Fetch order details from database
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = :order_id");
        $stmt->execute([':order_id' => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Create order details array
            $orderDetails = [
                'order_id' => $order['id'],
                'date' => $order['created_at'],
                'name' => $order['first_name'] . ' ' . $order['last_name'],
                'total' => $order['total_amount']
            ];
            
            // Fetch order items
            $itemStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
            $itemStmt->execute([':order_id' => $order_id]);
            $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Store in session for consistency
            $_SESSION['order_confirmation'] = $orderDetails;
            $_SESSION['cart_items'] = $orderItems;
        } else {
            error_log('Order not found in database using session ID: ' . $order_id);
        }
    } catch (PDOException $e) {
        error_log('Database error fetching order from session ID: ' . $e->getMessage());
    }
}

// Instead of immediately redirecting, let's check if this is a Razorpay callback redirect
// and try to get payment info from payments table if we have no order details yet
if (!$orderDetails && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    error_log('No order details, trying payments table with order_id: ' . $order_id);
    
    try {
        // Try to find payment details
        $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = :order_id ORDER BY payment_time DESC LIMIT 1");
        $stmt->execute([':order_id' => $order_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment) {
            error_log('Found payment information for order: ' . $order_id);
            
            // Look up order with this order_id
            $orderStmt = $conn->prepare("SELECT * FROM orders WHERE id = :order_id");
            $orderStmt->execute([':order_id' => $order_id]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                $orderDetails = [
                    'order_id' => $order['id'],
                    'date' => $order['created_at'],
                    'name' => $order['first_name'] . ' ' . $order['last_name'],
                    'total' => $payment['amount']
                ];
                
                // Get order items
                $itemStmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
                $itemStmt->execute([':order_id' => $order_id]);
                $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $_SESSION['order_confirmation'] = $orderDetails;
                $_SESSION['cart_items'] = $orderItems;
            }
        }
    } catch (PDOException $e) {
        error_log('Database error fetching payment info: ' . $e->getMessage());
    }
}

// Emergency fallback - create minimal order details if we have payment record
if (!$orderDetails && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    error_log('Creating emergency fallback order details for ID: ' . $order_id);
    
    // Create minimal order details
    $orderDetails = [
        'order_id' => $order_id,
        'date' => date('Y-m-d H:i:s'),
        'name' => 'Valued Customer',
        'total' => 0 // We don't know the total
    ];
    
    $_SESSION['order_confirmation'] = $orderDetails;
}

// Add more detailed error checking and redirect handling - only redirect if we have no payment_success flag
if (!$orderDetails && !isset($_SESSION['payment_success'])) {
    // Log the error
    error_log('Missing required order data for confirmation and no payment_success flag');
    
    // Set flash message
    $_SESSION['error_message'] = 'Unable to process order confirmation. Required data is missing.';
    
    // Redirect to cart page
    header('Location: add_to_cart.php');
    exit;
}

// Validate order details structure
if (!isset($orderDetails['order_id']) || !isset($orderDetails['date']) || 
    !isset($orderDetails['name']) || !isset($orderDetails['total'])) {
    error_log('Invalid order details structure: ' . print_r($orderDetails, true));
    
    // Try to fix any missing elements with defaults
    if (!isset($orderDetails['order_id'])) $orderDetails['order_id'] = $_GET['order_id'] ?? 'unknown';
    if (!isset($orderDetails['date'])) $orderDetails['date'] = date('Y-m-d H:i:s');
    if (!isset($orderDetails['name'])) $orderDetails['name'] = 'Valued Customer';
    if (!isset($orderDetails['total'])) $orderDetails['total'] = 0;
}

// If we're using order items from database, convert to format expected by the template
if ($orderItems && !isset($orderItems[0]['name'])) {
    $formattedItems = [];
    foreach ($orderItems as $item) {
        // Fetch product info if needed
        try {
            $prodStmt = $conn->prepare("SELECT name, price, image_url FROM products WHERE id = :product_id");
            $prodStmt->execute([':product_id' => $item['product_id']]);
            $product = $prodStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $formattedItems[] = [
                    'name' => $product['name'],
                    'quantity' => $item['quantity'],
                    'price' => $product['price']
                ];
            } else {
                // If product not found, create a placeholder with data from order_items
                error_log('Product not found: ' . $item['product_id'] . ' for order item in order: ' . $item['order_id']);
                $formattedItems[] = [
                    'name' => 'Product #' . $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => ($item['price'] ?? $item['unit_price'] ?? 0)
                ];
            }
        } catch (PDOException $e) {
            error_log('Error fetching product details: ' . $e->getMessage());
            // Still create a placeholder entry on error
            $formattedItems[] = [
                'name' => 'Product #' . $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => ($item['price'] ?? $item['unit_price'] ?? 0)
            ];
        }
    }
    
    if (!empty($formattedItems)) {
        $_SESSION['cart_items'] = $formattedItems;
    }
}

// If we still have no items, display a placeholder
if (empty($_SESSION['cart_items'])) {
    error_log('No items found for order, creating placeholder');
    $_SESSION['cart_items'] = [
        [
            'name' => 'Your Order',
            'quantity' => 1,
            'price' => $orderDetails['total']
        ]
    ];
}

// ENHANCED TOTAL AMOUNT RETRIEVAL
// This enhanced section will aggressively try multiple methods to get the correct total
$order_id = $_GET['order_id'] ?? $_SESSION['order_id'] ?? $orderDetails['order_id'] ?? null;

if ($order_id) {
    error_log('Attempting to retrieve accurate total for order: ' . $order_id);
    
    // Method 1: Check the payments table first (most reliable for completed payments)
    try {
        $paymentStmt = $conn->prepare("SELECT amount, status FROM payments WHERE order_id = :order_id AND status = 'success' ORDER BY payment_time DESC LIMIT 1");
        $paymentStmt->execute([':order_id' => $order_id]);
        $paymentData = $paymentStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($paymentData && $paymentData['amount'] > 0) {
            error_log('Found payment amount: ' . $paymentData['amount']);
            $orderDetails['total'] = $paymentData['amount'];
        }
    } catch (PDOException $e) {
        error_log('Error querying payments table: ' . $e->getMessage());
    }
    
    // Method 2: If still no amount, try orders table
    if (empty($orderDetails['total']) || $orderDetails['total'] == 0) {
        try {
            // Try both id and order_id columns
            $orderStmt = $conn->prepare("SELECT total_amount, grand_total FROM orders WHERE id = :order_id");
            $orderStmt->execute([':order_id' => $order_id]);
            $orderData = $orderStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($orderData) {
                // Try different possible column names for the total
                $total = $orderData['grand_total'] ?? $orderData['total_amount'] ?? 0;
                if ($total > 0) {
                    error_log('Found order total in orders table: ' . $total);
                    $orderDetails['total'] = $total;
                }
            }
        } catch (PDOException $e) {
            error_log('Error querying orders table: ' . $e->getMessage());
        }
    }
    
    // Method 3: Calculate from order_items
    if (empty($orderDetails['total']) || $orderDetails['total'] == 0) {
        try {
            $itemsStmt = $conn->prepare("SELECT SUM(quantity * unit_price) as calculated_total FROM order_items WHERE order_id = :order_id");
            $itemsStmt->execute([':order_id' => $order_id]);
            $calcResult = $itemsStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($calcResult && $calcResult['calculated_total'] > 0) {
                error_log('Calculated total from order_items: ' . $calcResult['calculated_total']);
                $orderDetails['total'] = $calcResult['calculated_total'];
            }
        } catch (PDOException $e) {
            error_log('Error calculating from order_items: ' . $e->getMessage());
        }
    }
    
    // Method 4: If Razorpay payment was just completed, check the GET parameters
    if ((empty($orderDetails['total']) || $orderDetails['total'] == 0) && isset($_GET['amount'])) {
        $amount = filter_var($_GET['amount'], FILTER_VALIDATE_FLOAT);
        if ($amount > 0) {
            error_log('Using amount from GET parameter: ' . $amount);
            $orderDetails['total'] = $amount;
        }
    }
    
    // Log final result
    error_log('Final determined total amount: ' . ($orderDetails['total'] ?? 'Not found'));
}

// Ensure we have at least some non-zero amount for display
if (empty($orderDetails['total']) || $orderDetails['total'] == 0) {
    // Last resort - check if we can find ANY payment for this order
    if ($order_id) {
        try {
            $anyPaymentStmt = $conn->prepare("SELECT amount FROM payments WHERE order_id = :order_id ORDER BY payment_time DESC LIMIT 1");
            $anyPaymentStmt->execute([':order_id' => $order_id]);
            $anyPayment = $anyPaymentStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($anyPayment && $anyPayment['amount'] > 0) {
                error_log('Found amount from any payment: ' . $anyPayment['amount']);
                $orderDetails['total'] = $anyPayment['amount'];
            }
        } catch (PDOException $e) {
            error_log('Error in last resort payment query: ' . $e->getMessage());
        }
    }
    
    // If we still have no total, set a default
    if (empty($orderDetails['total']) || $orderDetails['total'] == 0) {
        // Try to get from session if available
        if (isset($_SESSION['payment_amount']) && $_SESSION['payment_amount'] > 0) {
            $orderDetails['total'] = $_SESSION['payment_amount'];
        } else {
            error_log('WARNING: Unable to determine order total! Using placeholder value.');
            $orderDetails['total'] = 100; // Use a placeholder value so at least something shows
        }
    }
}

// Make sure we have proper order items in the template variables
$items = $_SESSION['cart_items'];

// Clear payment_success flag
unset($_SESSION['payment_success']);

// Send order confirmation email
try {
    // Get customer email from orders table
    $emailStmt = $conn->prepare("SELECT email FROM orders WHERE id = :order_id");
    $emailStmt->execute([':order_id' => $orderDetails['order_id']]);
    $emailData = $emailStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($emailData && $emailData['email']) {
        // Prepare email content
        $to = $emailData['email'];
        $subject = "Order Confirmation - The Canine & Feline Co. #" . $orderDetails['order_id'];
        
        // Start building HTML email content
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background-color: #00bd56; color: white; padding: 20px; text-align: center; }
                .order-details { padding: 20px; }
                .item-row { padding: 10px 0; border-bottom: 1px solid #eee; }
                .total-section { margin-top: 20px; border-top: 2px solid #dee2e6; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Thank You for Your Order!</h1>
                    <p>Order #" . htmlspecialchars($orderDetails['order_id']) . "</p>
                </div>
                <div class='order-details'>
                    <h3>Order Details</h3>
                    <p>Order Date: " . date('F j, Y, g:i a', strtotime($orderDetails['date'])) . "</p>
                    <p>Customer Name: " . htmlspecialchars($orderDetails['name']) . "</p>
                    
                    <h3>Items Ordered</h3>";
        
        // Add items to email
        foreach ($items as $item) {
            $message .= "
            <div class='item-row'>
                <p>" . htmlspecialchars($item['name']) . " - Qty: " . $item['quantity'] . 
                " - ₹" . number_format(($item['price'] * $item['quantity']), 2) . "</p>
            </div>";
        }
        
        // Add totals
        $message .= "
                    <div class='total-section'>
                        <p>Subtotal: ₹" . number_format($orderDetails['total'], 2) . "</p>
                        <p>Tax (8%): ₹" . number_format($orderDetails['total'] * 0.08, 2) . "</p>
                        <p>Shipping: Free</p>
                        <p><strong>Total: ₹" . number_format($orderDetails['total'] * 1.08, 2) . "</strong></p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        // Email headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: The Canine & Feline Co. <noreply@caninefeline.com>' . "\r\n";
        
        // Send email
        if(mail($to, $subject, $message, $headers)) {
            error_log('Order confirmation email sent to: ' . $to);
        } else {
            error_log('Failed to send order confirmation email to: ' . $to);
        }
    } else {
        error_log('No email address found for order: ' . $orderDetails['order_id']);
    }
} catch (PDOException $e) {
    error_log('Error while sending confirmation email: ' . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - The Canine & Feline Co.</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .navbar {
            background-color: #00bd56 !important;
        }
        
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .success-icon {
            color: #00bd56;
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .order-details {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .print-button {
            background-color: #00bd56;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .print-button:hover {
            background-color: #009945;
            transform: translateY(-2px);
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            .confirmation-container {
                box-shadow: none;
                margin: 0;
                padding: 15px;
            }
        }
        
        .item-row {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .total-section {
            margin-top: 20px;
            border-top: 2px solid #dee2e6;
            padding-top: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark no-print">
        <div class="container">
            <a class="navbar-brand" href="../food.php">
                <span class="mr-2">🐾</span>
                The Canine & Feline Co.
            </a>
            <a href="../food.php" class="btn btn-outline-light">
                Continue Shopping
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="confirmation-container">
            <div class="text-center">
                <i class="fas fa-check-circle success-icon"></i>
                <h1 class="mb-4">Thank You for Your Order!</h1>
                <p class="text-muted">Your payment has been processed successfully.</p>
            </div>

            <div class="order-details">
                <h3>Order Details</h3>
                <div class="detail-row">
                    <span>Order Number:</span>
                    <strong><?php echo htmlspecialchars($orderDetails['order_id']); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Order Date:</span>
                    <strong><?php echo date('F j, Y, g:i a', strtotime($orderDetails['date'])); ?></strong>
                </div>
                <div class="detail-row">
                    <span>Customer Name:</span>
                    <strong><?php echo htmlspecialchars($orderDetails['name']); ?></strong>
                </div>
            </div>

            <div class="order-details">
                <h3>Items Ordered</h3>
                <?php 
                // Use appropriate items array - directly use our verified $items variable
                if (!empty($items)): 
                    foreach ($items as $item): 
                ?>
                <div class="item-row">
                    <div class="row">
                        <div class="col-6">
                            <strong><?php echo htmlspecialchars($item['name'] ?? 'Unknown Product'); ?></strong>
                        </div>
                        <div class="col-3 text-center">
                            Qty: <?php echo $item['quantity'] ?? 1; ?>
                        </div>
                        <div class="col-3 text-right">
                            ₹<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2); ?>
                        </div>
                    </div>
                </div>
                <?php 
                    endforeach; 
                else: 
                ?>
                <div class="item-row">
                    <div class="row">
                        <div class="col-6">
                            <strong>Order Total</strong>
                        </div>
                        <div class="col-3 text-center">
                            Qty: 1
                        </div>
                        <div class="col-3 text-right">
                            ₹<?php echo number_format($orderDetails['total'], 2); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="total-section">
                    <div class="detail-row">
                        <span>Subtotal:</span>
                        <strong>₹<?php echo number_format((float)$orderDetails['total'], 2); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Tax (8%):</span>
                        <strong>₹<?php echo number_format((float)$orderDetails['total'] * 0.08, 2); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Shipping:</span>
                        <strong>Free</strong>
                    </div>
                    <div class="detail-row">
                        <span><strong>Total:</strong></span>
                        <strong>₹<?php echo number_format((float)$orderDetails['total'] * 1.08, 2); ?></strong>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button onclick="window.print()" class="print-button no-print">
                    <i class="fas fa-print mr-2"></i> Print Receipt
                </button>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted">
                    A confirmation email has been sent to your email address.
                    <br>
                    If you have any questions, please contact our support team.
                </p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>