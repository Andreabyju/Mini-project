<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once "../connect.php";
require_once "../functions/payment_functions.php";  // Make sure this file exists

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: add_to_cart.php');
    exit;
}

// Get cart items for display
$cartItems = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    $stmt = $conn->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total
    foreach ($cartItems as $item) {
        $total += $item['price'] * $_SESSION['cart'][$item['id']];
    }
}

// Handle form submission
$orderPlaced = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Validate inputs with more comprehensive validation
    if (empty($_POST['first_name'])) {
        $errors['first_name'] = 'First name is required';
    } elseif (!preg_match("/^[a-zA-Z ]{2,30}$/", $_POST['first_name'])) {
        $errors['first_name'] = 'First name must contain only letters and be 2-30 characters long';
    }
    
    if (empty($_POST['last_name'])) {
        $errors['last_name'] = 'Last name is required';
    } elseif (!preg_match("/^[a-zA-Z ]{2,30}$/", $_POST['last_name'])) {
        $errors['last_name'] = 'Last name must contain only letters and be 2-30 characters long';
    }
    
    if (empty($_POST['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (!empty($_POST['phone']) && !preg_match("/^[0-9]{10,15}$/", preg_replace("/[^0-9]/", "", $_POST['phone']))) {
        $errors['phone'] = 'Please enter a valid phone number';
    }
    
    if (empty($_POST['address'])) {
        $errors['address'] = 'Address is required';
    } elseif (strlen($_POST['address']) < 5) {
        $errors['address'] = 'Please enter a complete address';
    }
    
    if (empty($_POST['city'])) {
        $errors['city'] = 'City is required';
    } elseif (!preg_match("/^[a-zA-Z ]{2,50}$/", $_POST['city'])) {
        $errors['city'] = 'Please enter a valid city name';
    }
    
    if (empty($_POST['zip'])) {
        $errors['zip'] = 'Zip code is required';
    } elseif (!preg_match("/^[0-9]{5}(-[0-9]{4})?$/", $_POST['zip'])) {
        $errors['zip'] = 'Please enter a valid zip code (e.g., 12345 or 12345-6789)';
    }
    
    if (empty($_POST['payment_method'])) {
        $errors['payment_method'] = 'Payment method is required';
    }
    
    // Validate credit card details if payment method is credit card
    if ($_POST['payment_method'] === 'credit_card') {
        if (empty($_POST['card_name'])) {
            $errors['card_name'] = 'Name on card is required';
        } elseif (!preg_match("/^[a-zA-Z ]{2,50}$/", $_POST['card_name'])) {
            $errors['card_name'] = 'Please enter a valid name as it appears on your card';
        }
        
        if (empty($_POST['card_number'])) {
            $errors['card_number'] = 'Card number is required';
        } else {
            // Remove spaces and validate
            $cardNumber = preg_replace('/\s+/', '', $_POST['card_number']);
            if (!preg_match("/^[0-9]{13,19}$/", $cardNumber)) {
                $errors['card_number'] = 'Please enter a valid card number';
            } else {
                // Luhn algorithm for credit card validation
                $sum = 0;
                $length = strlen($cardNumber);
                for ($i = 0; $i < $length; $i++) {
                    $digit = (int)$cardNumber[$length - $i - 1];
                    if ($i % 2 == 1) {
                        $digit *= 2;
                        if ($digit > 9) {
                            $digit -= 9;
                        }
                    }
                    $sum += $digit;
                }
                if ($sum % 10 != 0) {
                    $errors['card_number'] = 'Please enter a valid card number';
                }
            }
        }
        
        if (empty($_POST['card_expiry'])) {
            $errors['card_expiry'] = 'Expiration date is required';
        } else {
            // Validate MM/YY format and check if not expired
            if (!preg_match("/^(0[1-9]|1[0-2])\/([0-9]{2})$/", $_POST['card_expiry'], $matches)) {
                $errors['card_expiry'] = 'Please enter a valid expiration date (MM/YY)';
            } else {
                $expMonth = $matches[1];
                $expYear = $matches[2];
                $expYear = "20" . $expYear; // Convert to 4-digit year
                
                $currentMonth = date('m');
                $currentYear = date('Y');
                
                if ($expYear < $currentYear || ($expYear == $currentYear && $expMonth < $currentMonth)) {
                    $errors['card_expiry'] = 'Your card has expired';
                }
            }
        }
        
        if (empty($_POST['card_cvv'])) {
            $errors['card_cvv'] = 'CVV is required';
        } elseif (!preg_match("/^[0-9]{3,4}$/", $_POST['card_cvv'])) {
            $errors['card_cvv'] = 'Please enter a valid CVV (3 or 4 digits)';
        }
    }
    
    // Check terms and conditions
    if (!isset($_POST['terms'])) {
        $errors['terms'] = 'You must agree to the terms and conditions';
    }
    
    // If no errors, process order
    if (empty($errors)) {
        try {
            // Get the current user's ID from the session
            $user_id = $_SESSION['user_id'];
            
            // Start transaction
            $conn->beginTransaction();
            
            // Insert into orders table
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, order_date, shipping_address, 
                shipping_city, shipping_state, shipping_zip, status, payment_method) 
                VALUES (?, ?, NOW(), ?, ?, ?, ?, 'pending', ?)");
                
            $stmt->execute([
                $user_id,
                $grandTotal,
                $_POST['address'],
                $_POST['city'],
                $_POST['state'],
                $_POST['zip'],
                $_POST['payment_method']
            ]);
            
            $order_id = $conn->lastInsertId();
            
            // Insert order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)");
                
            foreach ($cartItems as $item) {
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $_SESSION['cart'][$item['id']],
                    $item['price']
                ]);
            }
            
            // Commit transaction
            $conn->commit();
            
            // Store order confirmation in session
            $_SESSION['order_confirmation'] = [
                'order_id' => $order_id,
                'total' => $grandTotal,
                'date' => date('Y-m-d H:i:s'),
                'name' => $_POST['first_name'] . ' ' . $_POST['last_name']
            ];
            
            // Clear cart after successful order
            $_SESSION['cart'] = [];
            
            // Redirect to thank you page
            header('Location: order_confirmation.php');
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $errors['general'] = 'An error occurred while processing your order: ' . $e->getMessage();
        }
    }
}

// Calculate tax (assume 8%)
$tax = $total * 0.08;
$grandTotal = $total + $tax;

// Razorpay Key ID
$razorpay_key_id = 'rzp_test_CzoNYGf1d0sXyX'; // Your actual Razorpay Key ID
$razorpay_amount = $grandTotal * 100; // Amount in paise
$razorpay_currency = 'INR';
$razorpay_order_id = 'ORD' . time(); // Generate a unique order ID

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - The Canine & Feline Co.</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        /* Update the navbar background color */
        .navbar {
            background-color: #00bd56 !important; /* Using the same green color from your theme */
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .checkout-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        
        .checkout-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #00bd56;
            display: inline-block;
        }
        
        .form-group label {
            font-weight: 500;
            font-size: 14px;
        }
        
        .order-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        
        .cart-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .payment-options label {
            display: block;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-options input[type="radio"]:checked + label {
            border-color: #00bd56;
            background-color: rgba(0, 189, 86, 0.1);
        }
        
        .payment-options input[type="radio"] {
            display: none;
        }
        
        .payment-icon {
            font-size: 24px;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        .checkout-btn {
            background-color: #00bd56;
            color: white;
            font-size: 16px;
            font-weight: 600;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .checkout-btn:hover {
            background-color: #009945;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .back-to-cart {
            color: #6c757d;
            display: inline-block;
            margin-top: 20px;
        }
        
        .back-to-cart:hover {
            color: #343a40;
            text-decoration: none;
        }
        
        .form-control:focus {
            border-color: #00bd56;
            box-shadow: 0 0 0 0.2rem rgba(0, 189, 86, 0.25);
        }
        
        /* Card input styling */
        .card-element {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 10px;
            background-color: white;
        }
        
        .invalid-feedback {
            display: block;
        }
        
        /* Add styles for error highlighting */
        .is-invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 80%;
            margin-top: 0.25rem;
        }
        
        .is-valid {
            border-color: #28a745;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .form-control.is-valid:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        /* Transition effects for validation states */
        .form-control {
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, background-color 0.15s ease-in-out;
        }
        
        /* Credit card icon positioning */
        .card-number-container {
            position: relative;
        }
        
        /* Success text for valid fields */
        .valid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 80%;
            color: #28a745;
        }
        
        .form-control.is-valid ~ .valid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../food.php">
                <span class="mr-2">🐾</span>
                The Canine & Feline Co.
            </a>
            <a href="add_to_cart.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Cart
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="checkout-container">
            <div class="checkout-header">
                <h1>Checkout</h1>
                <p class="text-muted">Complete your purchase by providing your details below</p>
            </div>
            
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="confirm_payment.php" id="checkout-form" novalidate>
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Billing Information -->
                        <div class="mb-5">
                            <h3 class="section-title">Billing Information</h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input type="text" name="first_name" id="first_name" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                        <?php if (isset($errors['first_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" name="last_name" id="last_name" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                        <?php if (isset($errors['last_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number (optional)</label>
                                <input type="tel" name="phone" id="phone" class="form-control" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Street Address</label>
                                <input type="text" name="address" id="address" class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                                <?php if (isset($errors['address'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" name="city" id="city" class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                                        <?php if (isset($errors['city'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['city']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="state">State</label>
                                        <input type="text" name="state" id="state" class="form-control" value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="zip">Zip Code</label>
                                        <input type="text" name="zip" id="zip" class="form-control <?php echo isset($errors['zip']) ? 'is-invalid' : ''; ?>" value="<?php echo isset($_POST['zip']) ? htmlspecialchars($_POST['zip']) : ''; ?>">
                                        <?php if (isset($errors['zip'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['zip']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input <?php echo isset($errors['terms']) ? 'is-invalid' : ''; ?>" id="terms" name="terms">
                                <label class="custom-control-label" for="terms">
                                    I agree to the <a href="#" data-toggle="modal" data-target="#termsModal">Terms and Conditions</a>
                                </label>
                                <?php if (isset($errors['terms'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['terms']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Order Summary -->
                        <div class="order-summary">
                            <h3 class="section-title">Order Summary</h3>
                            
                            <div class="cart-items">
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="cart-item">
                                        <div class="d-flex">
                                            <img src="../uploads/<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="mr-3">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <p class="text-muted mb-0">Qty: <?php echo $_SESSION['cart'][$item['id']]; ?></p>
                                                <p class="mb-0">₹<?php echo number_format($item['price'] * $_SESSION['cart'][$item['id']], 2); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>₹<?php echo number_format($total, 2); ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span>Free</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax (8%):</span>
                                    <span>₹<?php echo number_format($tax, 2); ?></span>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between mb-4">
                                    <strong>Total:</strong>
                                    <strong>₹<?php echo number_format($grandTotal, 2); ?></strong>
                                </div>
                                
                                <!-- Add this hidden field to track payment method -->
                                <input type="hidden" name="payment_completed" id="payment_completed" value="0">

                                <!-- Update your Place Order button -->
                                <button type="button" id="place-order-btn" class="checkout-btn btn-block" onclick="processPayment()">
                                    <i class="fas fa-lock mr-2"></i> PLACE ORDER
                                </button>
                                
                                <a href="add_to_cart.php" class="back-to-cart d-block text-center">
                                    <i class="fas fa-arrow-left mr-2"></i> Return to Cart
                                </a>
                            </div>
                            
                            <div class="mt-4 text-center">
                                <p class="mb-2"><i class="fas fa-shield-alt mr-1"></i> Secure Checkout</p>
                                <div>
                                    <i class="fab fa-cc-visa mx-1" style="font-size: 24px; color: #1A1F71;"></i>
                                    <i class="fab fa-cc-mastercard mx-1" style="font-size: 24px; color: #EB001B;"></i>
                                    <i class="fab fa-cc-amex mx-1" style="font-size: 24px; color: #006FCF;"></i>
                                    <i class="fab fa-cc-paypal mx-1" style="font-size: 24px; color: #003087;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6>1. General Terms</h6>
                    <p>By placing an order with The Canine & Feline Co., you agree to these terms and conditions.</p>
                    
                    <h6>2. Pricing and Payment</h6>
                    <p>All prices are in USD. Payment is required at the time of order. We accept various payment methods as shown during checkout.</p>
                    
                    <h6>3. Shipping and Delivery</h6>
                    <p>Orders are processed within 1-2 business days. Shipping times vary by location. Free shipping is available for all orders.</p>
                    
                    <h6>4. Returns and Refunds</h6>
                    <p>If you're not satisfied with your purchase, you may return it within 30 days for a full refund. Certain items may not be eligible for return.</p>
                    
                    <h6>5. Privacy Policy</h6>
                    <p>We respect your privacy and will only use your information to process your order and provide customer service.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Confirmation Page -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Placed Successfully!</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 64px;"></i>
                    <h4 class="mt-3">Thank You For Your Order!</h4>
                    <p>Your order has been placed successfully. You will receive a confirmation email shortly.</p>
                    <p>Order Number: <strong>ORD-<?php echo time(); ?></strong></p>
                </div>
                <div class="modal-footer">
                    <a href="../food.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript with enhanced validation -->
     <body>
    <!-- Load jQuery before your script -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Your custom script -->
    <script>
        $(document).ready(function() {
            console.log("jQuery is loaded:", $.fn.jquery);

            // Disable the Place Order button initially
            $("#place-order-btn").prop('disabled', true);
            
            // Function to validate all required fields
            function validateForm() {
                let isValid = true;
                
                // Required fields validation
                const requiredFields = [
                    'first_name',
                    'last_name',
                    'email',
                    'address',
                    'city',
                    'zip'
                ];
                
                requiredFields.forEach(field => {
                    const value = $(`#${field}`).val().trim();
                    if (!value) {
                        isValid = false;
                        showError(field, `${field.replace('_', ' ').toUpperCase()} is required`);
                    }
                });
                
                // Email validation
                const email = $('#email').val().trim();
                if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    isValid = false;
                    showError('email', 'Please enter a valid email address');
                }
                
                // Terms and conditions validation
                if (!$('#terms').is(':checked')) {
                    isValid = false;
                    showError('terms', 'You must agree to the terms and conditions');
                }
                
                // Enable/disable Place Order button based on validation
                $("#place-order-btn").prop('disabled', !isValid);
                
                return isValid;
            }
            
            // Add event listeners for all form fields
            $('input, select').on('change keyup', validateForm);
            $('#terms').on('change', validateForm);
            
            // Modify the processPayment function
            function processPayment() {
                if (!validateForm()) {
                    // Show error message
                    alert('Please fill in all required fields and accept the terms and conditions.');
                    
                    // Scroll to the first error
                    const firstError = $('.is-invalid').first();
                    if (firstError.length) {
                        $('html, body').animate({
                            scrollTop: firstError.offset().top - 100
                        }, 500);
                    }
                    return false;
                }
                
                // Continue with payment processing
                $("#place-order-btn").prop('disabled', true).text('Processing...');
                
                // Get form data
                const formData = new FormData(document.getElementById('checkout-form'));
                formData.append('amount', <?php echo $grandTotal; ?>);
                
                // Create order
                $.ajax({
                    url: 'create_order.php',
                    method: 'POST',
                    data: {
                        amount: <?php echo $grandTotal; ?>,
                        first_name: $('#first_name').val(),
                        last_name: $('#last_name').val(),
                        email: $('#email').val(),
                        phone: $('#phone').val(),
                        address: $('#address').val(),
                        city: $('#city').val(),
                        state: $('#state').val(),
                        zip: $('#zip').val()
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            var options = {
                                "key": "rzp_test_CzoNYGf1d0sXyX",
                                "amount": response.amount,
                                "currency": "INR",
                                "name": "The Canine & Feline Co.",
                                "description": "Pet Food Order Payment",
                                "order_id": response.order_id,
                                "handler": function (paymentResponse) {
                                    // Verify payment
                                    $.ajax({
                                        url: 'verify_payment.php',
                                        method: 'POST',
                                        data: {
                                            razorpay_payment_id: paymentResponse.razorpay_payment_id,
                                            razorpay_order_id: paymentResponse.razorpay_order_id,
                                            razorpay_signature: paymentResponse.razorpay_signature,
                                            database_order_id: response.database_order_id,
                                            amount: response.amount
                                        },
                                        success: function(verificationResponse) {
                                            if (verificationResponse.status === 'success') {
                                                window.location.href = 'order_confirmation.php?order_id=' + response.database_order_id;
                                            } else {
                                                alert('Payment verification failed: ' + verificationResponse.message);
                                                $("#place-order-btn").prop('disabled', false).text('Place Order');
                                            }
                                        },
                                        error: function() {
                                            alert('Payment verification failed. Please contact support.');
                                            $("#place-order-btn").prop('disabled', false).text('Place Order');
                                        }
                                    });
                                },
                                "prefill": {
                                    "name": $('#first_name').val() + ' ' + $('#last_name').val(),
                                    "email": $('#email').val(),
                                    "contact": $('#phone').val()
                                },
                                "theme": {
                                    "color": "#00bd56"
                                }
                            };
                            
                            var rzp1 = new Razorpay(options);
                            rzp1.open();
                        } else {
                            alert('Could not create order. Please try again.');
                            $("#place-order-btn").prop('disabled', false).text('Place Order');
                        }
                    },
                    error: function() {
                        alert('Could not create order. Please try again.');
                        $("#place-order-btn").prop('disabled', false).text('Place Order');
                    }
                });
            }
            
            // Update the click handler for the place order button
            $("#place-order-btn").click(function(e) {
                e.preventDefault();
                if (validateForm()) {
                    processPayment();
                }
            });
        });
    </script>
</body>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Toggle credit card details based on payment method selection
        $('input[name="payment_method"]').change(function() {
            if ($(this).val() === 'credit_card') {
                $('#credit-card-details').removeClass('d-none');
            } else {
                $('#credit-card-details').addClass('d-none');
            }
        });
        
        // Format credit card number with spaces
        $('#card_number').on('input', function() {
            var val = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            var formatted = val.match(/.{1,4}/g);
            if (formatted) {
                $(this).val(formatted.join(' '));
            }
        });
        
        // Format expiration date (MM/YY)
        $('#card_expiry').on('input', function() {
            var val = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            if (val.length > 2) {
                $(this).val(val.substring(0, 2) + '/' + val.substring(2, 4));
            }
        });
        
        // Client-side validation
        $('#checkout-form').on('submit', function(e) {
            let isValid = true;
            
            // Validate first name
            const firstName = $('#first_name').val().trim();
            if (!firstName) {
                isValid = false;
                showError('first_name', 'First name is required');
            } else if (!/^[a-zA-Z ]{2,30}$/.test(firstName)) {
                isValid = false;
                showError('first_name', 'First name must contain only letters and be 2-30 characters');
            } else {
                removeError('first_name');
            }
            
            // Validate last name
            const lastName = $('#last_name').val().trim();
            if (!lastName) {
                isValid = false;
                showError('last_name', 'Last name is required');
            } else if (!/^[a-zA-Z ]{2,30}$/.test(lastName)) {
                isValid = false;
                showError('last_name', 'Last name must contain only letters and be 2-30 characters');
            } else {
                removeError('last_name');
            }
            
            // Validate email
            const email = $('#email').val().trim();
            if (!email) {
                isValid = false;
                showError('email', 'Email is required');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                isValid = false;
                showError('email', 'Please enter a valid email address');
            } else {
                removeError('email');
            }
            
            // Validate phone (optional)
            const phone = $('#phone').val().trim();
            if (phone && !/^[\d\s\-\(\)]{10,15}$/.test(phone)) {
                isValid = false;
                showError('phone', 'Please enter a valid phone number');
            } else {
                removeError('phone');
            }
            
            // Validate address
            const address = $('#address').val().trim();
            if (!address) {
                isValid = false;
                showError('address', 'Address is required');
            } else if (address.length < 5) {
                isValid = false;
                showError('address', 'Please enter a complete address');
            } else {
                removeError('address');
            }
            
            // Validate city
            const city = $('#city').val().trim();
            if (!city) {
                isValid = false;
                showError('city', 'City is required');
            } else if (!/^[a-zA-Z ]{2,50}$/.test(city)) {
                isValid = false;
                showError('city', 'Please enter a valid city name');
            } else {
                removeError('city');
            }
            
            // Validate zip
            const zip = $('#zip').val().trim();
            if (!zip) {
                isValid = false;
                showError('zip', 'Zip code is required');
            } else if (!/^\d{5}(-\d{4})?$/.test(zip)) {
                isValid = false;
                showError('zip', 'Please enter a valid zip code (e.g., 12345 or 12345-6789)');
            } else {
                removeError('zip');
            }
            
            // Validate payment method
            const paymentMethod = $('input[name="payment_method"]:checked').val();
            if (!paymentMethod) {
                isValid = false;
                showError('payment_method', 'Please select a payment method');
            } else {
                removeError('payment_method');
            }
            
            // Validate credit card details if payment method is credit card
            if (paymentMethod === 'credit_card') {
                // Validate card name
                const cardName = $('#card_name').val().trim();
                if (!cardName) {
                    isValid = false;
                    showError('card_name', 'Name on card is required');
                } else if (!/^[a-zA-Z ]{2,50}$/.test(cardName)) {
                    isValid = false;
                    showError('card_name', 'Please enter a valid name as it appears on your card');
                } else {
                    removeError('card_name');
                }
                
                // Validate card number
                const cardNumber = $('#card_number').val().replace(/\s+/g, '');
                if (!cardNumber) {
                    isValid = false;
                    showError('card_number', 'Card number is required');
                } else if (!/^\d{13,19}$/.test(cardNumber)) {
                    isValid = false;
                    showError('card_number', 'Please enter a valid card number');
                } else if (!luhnCheck(cardNumber)) {
                    isValid = false;
                    showError('card_number', 'Please enter a valid card number');
                } else {
                    removeError('card_number');
                }
                
                // Validate expiration date
                const expiry = $('#card_expiry').val().trim();
                if (!expiry) {
                    isValid = false;
                    showError('card_expiry', 'Expiration date is required');
                } else if (!/^(0[1-9]|1[0-2])\/(\d{2})$/.test(expiry)) {
                    isValid = false;
                    showError('card_expiry', 'Please enter a valid expiration date (MM/YY)');
                } else {
                    // Check if card is expired
                    const parts = expiry.split('/');
                    const expMonth = parseInt(parts[0], 10);
                    const expYear = parseInt('20' + parts[1], 10);
                    
                    const currentDate = new Date();
                    const currentMonth = currentDate.getMonth() + 1; // JavaScript months are 0-based
                    const currentYear = currentDate.getFullYear();
                    
                    if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
                        isValid = false;
                        showError('card_expiry', 'Your card has expired');
                    } else {
                        removeError('card_expiry');
                    }
                }
                
                // Validate CVV
                const cvv = $('#card_cvv').val().trim();
                if (!cvv) {
                    isValid = false;
                    showError('card_cvv', 'CVV is required');
                } else if (!/^\d{3,4}$/.test(cvv)) {
                    isValid = false;
                    showError('card_cvv', 'Please enter a valid CVV (3 or 4 digits)');
                } else {
                    removeError('card_cvv');
                }
            }
            
            // Validate terms and conditions
            if (!$('#terms').is(':checked')) {
                isValid = false;
                showError('terms', 'You must agree to the terms and conditions');
            } else {
                removeError('terms');
            }
            
            // Prevent form submission if validation fails
            if (!isValid) {
                e.preventDefault();
                // Scroll to the first error
                const firstError = $('.is-invalid').first();
                if (firstError.length) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 500);
                }
            }
        });
        
        // Function to show error message
        function showError(fieldId, message) {
            const field = $('#' + fieldId);
            field.addClass('is-invalid');
            
            // Remove any existing error message
            const existingError = field.next('.invalid-feedback');
            if (existingError.length) {
                existingError.text(message);
            } else {
                field.after('<div class="invalid-feedback">' + message + '</div>');
            }
        }
        
        // Function to remove error message
        function removeError(fieldId) {
            const field = $('#' + fieldId);
            field.removeClass('is-invalid');
            field.next('.invalid-feedback').remove();
        }
        
        // Luhn algorithm for credit card validation
        function luhnCheck(cardNumber) {
            let sum = 0;
            let shouldDouble = false;
            
            // Start from the rightmost digit and process each digit
            for (let i = cardNumber.length - 1; i >= 0; i--) {
                let digit = parseInt(cardNumber.charAt(i));
                
                if (shouldDouble) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }
                
                sum += digit;
                shouldDouble = !shouldDouble;
            }
            
            return (sum % 10) === 0;
        }
        
        // Real-time validation
        $('.form-control').on('blur', function() {
            const fieldId = $(this).attr('id');
            const value = $(this).val().trim();
            
            switch(fieldId) {
                case 'first_name':
                    if (!value) {
                        showError(fieldId, 'First name is required');
                    } else if (!/^[a-zA-Z ]{2,30}$/.test(value)) {
                        showError(fieldId, 'First name must contain only letters and be 2-30 characters');
                    } else {
                        removeError(fieldId);
                    }
                    break;
                    
                case 'email':
                    if (!value) {
                        showError(fieldId, 'Email is required');
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        showError(fieldId, 'Please enter a valid email address');
                    } else {
                        removeError(fieldId);
                    }
                    break;
                    
                // Add similar validations for other fields
            }
        });
        
        <?php if ($orderPlaced): ?>
        // Show confirmation modal if order was placed
        $('#confirmationModal').modal('show');
        <?php endif; ?>
        
        // LIVE VALIDATION - Add these event listeners for real-time validation
        
        // First Name live validation
        $('#first_name').on('input', function() {
            const value = $(this).val().trim();
            if (!value) {
                showError('first_name', 'First name is required');
            } else if (!/^[a-zA-Z ]{2,30}$/.test(value)) {
                showError('first_name', 'First name must contain only letters');
            } else {
                removeError('first_name');
            }
        });
        
        // Last Name live validation
        $('#last_name').on('input', function() {
            const value = $(this).val().trim();
            if (!value) {
                showError('last_name', 'Last name is required');
            } else if (!/^[a-zA-Z ]{2,30}$/.test(value)) {
                showError('last_name', 'Last name must contain only letters');
            } else {
                removeError('last_name');
            }
        });
        
        // Email live validation
        $('#email').on('input', function() {
            const value = $(this).val().trim();
            if (!value) {
                showError('email', 'Email is required');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                showError('email', 'Please enter a valid email address');
            } else {
                removeError('email');
            }
        });
        
        // Phone live validation
        $('#phone').on('input', function() {
            const value = $(this).val().trim();
            if (value && !/^[\d\s\-\(\)]{10,15}$/.test(value)) {
                showError('phone', 'Please enter a valid phone number');
            } else {
                removeError('phone');
            }
        });
        
        // Address live validation
        $('#address').on('input', function() {
            const value = $(this).val().trim();
            if (!value) {
                showError('address', 'Address is required');
            } else if (value.length < 5) {
                showError('address', 'Please enter a complete address');
            } else {
                removeError('address');
            }
        });
        
        // City live validation
        $('#city').on('input', function() {
            const value = $(this).val().trim();
            if (!value) {
                showError('city', 'City is required');
            } else if (!/^[a-zA-Z ]{2,50}$/.test(value)) {
                showError('city', 'Please enter a valid city name');
            } else {
                removeError('city');
            }
        });
        
        // State live validation
        $('#state').on('input', function() {
            const value = $(this).val().trim();
            if (!value) {
                showError('state', 'State is required');
            } else if (!/^[a-zA-Z ]{2,30}$/.test(value)) {
                showError('state', 'Please enter a valid state name');
            } else {
                removeError('state');
            }
        });
        
        // Zip code live validation
        $('#zip').on('input', function() {
            const value = $(this).val().trim();
            if (!value) {
                showError('zip', 'Zip code is required');
            } else if (!/^\d{5}(-\d{4})?$/.test(value)) {
                showError('zip', 'Please enter a valid zip code (e.g., 12345 or 12345-6789)');
            } else {
                removeError('zip');
            }
        });
        
        // Live validation for payment method selection
        $('input[name="payment_method"]').on('change', function() {
            removeError('payment_method');
            
            // Show/hide credit card details
            if ($(this).val() === 'credit_card') {
                $('#credit-card-details').removeClass('d-none');
            } else {
                $('#credit-card-details').addClass('d-none');
            }
        });
        
        // Card name live validation
        $('#card_name').on('input', function() {
            const value = $(this).val().trim();
            if (!value) {
                showError('card_name', 'Name on card is required');
            } else if (!/^[a-zA-Z ]{2,50}$/.test(value)) {
                showError('card_name', 'Please enter a valid name as it appears on your card');
            } else {
                removeError('card_name');
            }
        });
        
        // Card number live validation with formatting
        $('#card_number').on('input', function() {
            let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            
            // Format the card number with spaces
            if (value) {
                const formatted = value.match(/.{1,4}/g);
                if (formatted) {
                    $(this).val(formatted.join(' '));
                }
            }
            
            // Validate the card number
            if (!value) {
                showError('card_number', 'Card number is required');
            } else if (!/^\d{13,19}$/.test(value)) {
                showError('card_number', 'Please enter a valid card number');
            } else if (!luhnCheck(value)) {
                showError('card_number', 'Invalid card number. Please check and try again.');
            } else {
                removeError('card_number');
                
                // Show card type icon based on the first digits
                const cardType = getCardType(value);
                if (cardType) {
                    $('#card-type-icon').html(`<i class="fab fa-cc-${cardType.toLowerCase()} fa-lg text-primary ml-2"></i>`);
                } else {
                    $('#card-type-icon').html('');
                }
            }
        });
        
        // Card expiry live validation with formatting
        $('#card_expiry').on('input', function() {
            let value = $(this).val().replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            
            // Format the expiry date as MM/YY
            if (value.length > 0) {
                if (value.length <= 2) {
                    $(this).val(value);
                } else {
                    $(this).val(value.substring(0, 2) + '/' + value.substring(2, 4));
                }
            }
            
            // Get the formatted value
            value = $(this).val().trim();
            
            // Validate the expiry date
            if (!value) {
                showError('card_expiry', 'Expiration date is required');
            } else if (!/^(0[1-9]|1[0-2])\/(\d{2})$/.test(value)) {
                showError('card_expiry', 'Please enter a valid date (MM/YY)');
            } else {
                // Check if card is expired
                const parts = value.split('/');
                const expMonth = parseInt(parts[0], 10);
                const expYear = parseInt('20' + parts[1], 10);
                
                const currentDate = new Date();
                const currentMonth = currentDate.getMonth() + 1; // JavaScript months are 0-based
                const currentYear = currentDate.getFullYear();
                
                if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
                    showError('card_expiry', 'Your card has expired');
                } else {
                    removeError('card_expiry');
                }
            }
        });
        
        // CVV live validation
        $('#card_cvv').on('input', function() {
            const value = $(this).val().trim();
            // Only allow digits and limit to 4 characters
            $(this).val(value.replace(/[^\d]/g, '').substring(0, 4));
            
            if (!value) {
                showError('card_cvv', 'CVV is required');
            } else if (!/^\d{3,4}$/.test(value)) {
                showError('card_cvv', 'Please enter a valid CVV (3 or 4 digits)');
            } else {
                removeError('card_cvv');
            }
        });
        
        // Terms checkbox live validation
        $('#terms').on('change', function() {
            if (!$(this).is(':checked')) {
                showError('terms', 'You must agree to the terms and conditions');
            } else {
                removeError('terms');
            }
        });
        
        // Helper function to determine card type
        function getCardType(cardNumber) {
            // Remove all non-digits
            cardNumber = cardNumber.replace(/\D/g, '');
            
            // Visa
            if (cardNumber.match(/^4[0-9]{12}(?:[0-9]{3})?$/)) {
                return 'visa';
            }
            
            // Mastercard
            if (cardNumber.match(/^5[1-5][0-9]{14}$/)) {
                return 'mastercard';
            }
            
            // American Express
            if (cardNumber.match(/^3[47][0-9]{13}$/)) {
                return 'amex';
            }
            
            // Discover
            if (cardNumber.match(/^6(?:011|5[0-9]{2})[0-9]{12}$/)) {
                return 'discover';
            }
            
            return null;
        }
        
        // Function to show error message - updated for better visual feedback
        function showError(fieldId, message) {
            const field = $('#' + fieldId);
            field.addClass('is-invalid');
            
            // Add a small red border and background to highlight the field
            field.css({
                'border-color': '#dc3545',
                'background-color': 'rgba(220, 53, 69, 0.05)'
            });
            
            // Remove any existing error message
            const existingError = field.next('.invalid-feedback');
            if (existingError.length) {
                existingError.text(message);
            } else {
                field.after('<div class="invalid-feedback">' + message + '</div>');
            }
        }
        
        // Function to remove error message - updated for better visual feedback
        function removeError(fieldId) {
            const field = $('#' + fieldId);
            field.removeClass('is-invalid');
            
            // Remove the red border and background
            field.css({
                'border-color': '',
                'background-color': ''
            });
            
            // Add a green border for valid fields
            field.addClass('is-valid');
            field.css({
                'border-color': '#28a745',
                'background-color': 'rgba(40, 167, 69, 0.05)'
            });
            
            field.next('.invalid-feedback').remove();
        }
    });
    </script>
    
    <!-- Add these hidden fields to your form to capture Razorpay response -->
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature">

    <!-- Add this JavaScript function at the bottom of your page -->
    <script>
    function goToPaymentConfirmation() {
        // Basic validation for required fields
        let isValid = true;
        
        // Check required fields
        const requiredFields = ['first_name', 'last_name', 'email', 'address', 'city', 'zip'];
        requiredFields.forEach(field => {
            if (!$('#' + field).val().trim()) {
                $('#' + field).addClass('is-invalid');
                isValid = false;
            } else {
                $('#' + field).removeClass('is-invalid');
            }
        });
        
        // Check if payment method is selected
        if (!$('input[name="payment_method"]:checked').val()) {
            $('input[name="payment_method"]').first().parent().addClass('is-invalid');
            isValid = false;
        }
        
        // Check terms
        if (!$('#terms').is(':checked')) {
            $('#terms').addClass('is-invalid');
            isValid = false;
        }
        
        if (isValid) {
            // Set payment completed flag
            $('#payment_completed').val('1');
            
            // For debugging
            console.log('Submitting form to confirm_payment.php');
            
            // Submit the form directly
            $('#checkout-form').submit();
        } else {
            // Scroll to the first error
            const firstError = $('.is-invalid').first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }
        }
    }
    </script>
</body>
</html> 