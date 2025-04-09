<?php
session_start();
require_once "../connect.php";

    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

// Fetch cart items from database
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - The Canine & Feline Co.</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .cart-item {
            border: 1px solid #eee;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .cart-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            background-color: #00bd56;
            color: white;
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:hover {
            background-color: #009945;
        }

        .remove-btn {
            color: #dc3545;
            cursor: pointer;
        }

        .cart-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .empty-cart {
            text-align: center;
            padding: 50px 0;
        }

        .empty-cart i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .checkout-btn {
            background-color: #00bd56;
            color: white;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 5px;
            border: none;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .checkout-btn:hover {
            background-color: #009945;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../food.php">
                <span class="mr-2">🐾</span>
                The Canine & Feline Co.
            </a>
            <a href="../food.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left mr-2"></i>
                Continue Shopping
            </a>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4">Your Shopping Cart</h2>

        <?php if (!empty($cartItems)): ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($cartItems as $item): 
                        $itemTotal = $item['price'] * $_SESSION['cart'][$item['id']];
                    ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="../uploads/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="img-fluid">
                                </div>
                                <div class="col-md-4">
                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="text-muted"><?php echo htmlspecialchars($item['category']); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <div class="quantity-control">
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')">-</button>
                                        <span><?php echo $_SESSION['cart'][$item['id']]; ?></span>
                                        <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')">+</button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <span class="price">₹<?php echo number_format($itemTotal, 2); ?></span>
                                </div>
                                <div class="col-md-1">
                                    <i class="fas fa-trash remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4>Order Summary</h4>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Subtotal</span>
                            <span>₹<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total</strong>
                            <strong>₹<?php echo number_format($total, 2); ?></strong>
                        </div>
                        <a href="checkout.php" class="btn checkout-btn">
                            <i class="fas fa-lock mr-2"></i> PROCEED TO CHECKOUT
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="../food.php" class="btn btn-success mt-3">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function updateQuantity(productId, action) {
        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                productId: productId,
                action: action
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating cart');
        });
    }

    function removeItem(productId) {
        if(confirm('Are you sure you want to remove this item?')) {
            fetch('remove_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    productId: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error removing item');
            });
        }
    }
    </script>
</body>
</html> 