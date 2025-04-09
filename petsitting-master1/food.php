<?php
session_start(); // Only one session_start() at the very beginning
require_once "connect.php";

// Fetch products from database and group by category
try {
    // Fetch cat food products
    $catFoodStmt = $conn->prepare("SELECT * FROM products WHERE category = 'Cat Food'");
    $catFoodStmt->execute();
    $catFood = $catFoodStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch dog food products
    $dogFoodStmt = $conn->prepare("SELECT * FROM products WHERE category = 'Dog Food'");
    $dogFoodStmt->execute();
    $dogFood = $dogFoodStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Pet Sitting - Free Bootstrap 4 Template by Colorlib</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="https://fonts.googleapis.com/css?family=Montserrat:200,300,400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="css/jquery.timepicker.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .position-relative {
            position: relative;
        }
        
        .badge {
            position: absolute;
            top: -10px;
            right: -10px;
            padding: 3px 6px;
            border-radius: 50%;
            background-color: #dc3545;
            color: white;
            font-size: 0.75rem;
        }
    </style>
  </head>
  <body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
      <div class="container">
        <a class="navbar-brand" href="index.html" style="font-weight: 400;"><span class="flaticon-pawprint-1 mr-2"></span>The Canine & Feline Co.</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="fa fa-bars"></span> Menu
        </button>
        <div class="collapse navbar-collapse" id="ftco-nav">
          <ul class="navbar-nav ml-auto align-items-center">
          <li class="nav-item">
    <form class="form-inline my-2 my-lg-0" onsubmit="return searchProducts(event);">
        <input class="form-control rounded-pill mr-2" type="search" placeholder="Search" aria-label="Search" id="searchInput" style="width: 300px; height: 28px;">
        <button class="btn btn-outline-success rounded-circle my-2 my-sm-0" type="submit" style="padding: 0; border: none;">
            <i class="fas fa-search" style="color: white;"></i>
        </button>
              </form>
            </li>
            <li class="nav-item"><a href="hhh2.php" class="nav-link"><i class="fa-solid fa-house fa-lg" style="color: white;"></i></a></li>
            <li class="nav-item">
						<a href="cart/add_to_cart.php" class="nav-link cart-link">
							<div class="cart-icon-container">
								<i class="fa-solid fa-cart-shopping fa-lg" style="color: white;"></i>
								<span id="cart-counter" class="cart-badge">
									<?php 
										echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : '0';
									?>
								</span>
							</div>
						</a>
					</li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- END nav -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Food Products - The Canine & Feline Co.</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 200px;
            object-fit: cover;
        }
        
        .category-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .category-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #00bd56;
        }
        
        .price {
            color: #00bd56;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .add-to-cart-btn {
            background-color: #00bd56;
            border: none;
            transition: background-color 0.3s;
        }
        
        .add-to-cart-btn:hover {
            background-color: #009945;
        }

        .cart-icon-container {
            position: relative;
            display: inline-block;
        }

        .cart-badge {
            position: absolute;
            top: -8px;          /* Adjusted to sit higher */
            right: -8px;        /* Adjusted to sit more to the right */
            background-color: #ff0000;
            color: white;
            font-size: 11px;    /* Slightly smaller font */
            width: 18px;        /* Fixed width */
            height: 18px;       /* Fixed height - same as width for perfect circle */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .cart-link {
            padding: 0.5rem 1rem;
            position: relative;
            display: inline-block;
        }
    </style>
</head>
<body>

<!-- Include your website's navigation here -->

<div class="container my-5">
    <!-- Cat Food Section -->
    <h2 class="category-title">Cat Food Products</h2>
    <div class="row mb-5">
        <?php if (!empty($catFood)): ?>
            <?php foreach($catFood as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="product-card">
                        <img src="uploads/<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image w-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="price">₹<?php echo number_format($product['price'], 2); ?></span>
                                <button class="btn btn-primary add-to-cart-btn" 
                                        onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <p>No cat food products available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Dog Food Section -->
    <h2 class="category-title">Dog Food Products</h2>
    <div class="row">
        <?php if (!empty($dogFood)): ?>
            <?php foreach($dogFood as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="product-card">
                        <img src="uploads/<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image w-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="price">₹<?php echo number_format($product['price'], 2); ?></span>
                                <button class="btn btn-primary add-to-cart-btn" 
                                        onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <p>No dog food products available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add to Cart JavaScript -->
<script>
function addToCart(productId) {
    // Log the productId to check if it's being passed correctly
    console.log('Adding product ID:', productId);
    
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            productId: productId,
            quantity: 1
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Response:', data); // Log the response
        if(data.success) {
            // Update cart counter
            const cartCounter = document.getElementById('cart-counter');
            cartCounter.textContent = data.cartTotal;
            alert('Product added to cart successfully!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product to cart');
    });
}

// Search functionality
function searchProducts(event) {
    event.preventDefault();
    const searchInput = document.getElementById('searchInput').value.trim();
    
    if (searchInput === '') {
        alert('Please enter a search term');
        return false;
    }
    
    // Show loading state
    const container = document.querySelector('.container.my-5');
    container.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-3">Searching...</p></div>';
    
    fetch(`search.php?query=${encodeURIComponent(searchInput)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Search response:', data); // For debugging
            if (data.success) {
                displaySearchResults(data.results, searchInput);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error searching for products');
        });
    
    return false;
}

function displaySearchResults(results, searchQuery) {
    const container = document.querySelector('.container.my-5');
    
    // Clear existing content
    container.innerHTML = '';
    
    // Add search results title
    const titleElement = document.createElement('h2');
    titleElement.className = 'category-title';
    titleElement.textContent = `Search Results for "${searchQuery}"`;
    container.appendChild(titleElement);
    
    if (results.length === 0) {
        const noResults = document.createElement('div');
        noResults.className = 'text-center mt-4';
        noResults.innerHTML = `
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <p class="lead">No products found matching your search "${searchQuery}".</p>
            <button class="btn btn-primary mt-3" onclick="window.location.reload()">
                <i class="fas fa-arrow-left mr-2"></i>Back to All Products
            </button>
        `;
        container.appendChild(noResults);
        return;
    }
    
    // Create row for products
    const row = document.createElement('div');
    row.className = 'row';
    container.appendChild(row);
    
    // Add each product to the results
    results.forEach(product => {
        const productCol = document.createElement('div');
        productCol.className = 'col-md-4 mb-4';
        
        productCol.innerHTML = `
            <div class="product-card">
                <img src="uploads/${product.image_url}" 
                     alt="${product.name}" 
                     class="product-image w-100">
                <div class="card-body">
                    <h5 class="card-title">${product.name}</h5>
                    <p class="card-text">${product.description}</p>
                    <p class="text-muted small">Category: ${product.category}</p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="price">₹${parseFloat(product.price).toFixed(2)}</span>
                        <button class="btn btn-primary add-to-cart-btn" 
                                onclick="addToCart(${product.id})">
                            <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        row.appendChild(productCol);
    });
    
    // Add a button to go back to all products
    const backButtonContainer = document.createElement('div');
    backButtonContainer.className = 'text-center mt-4';
    backButtonContainer.innerHTML = `
        <button class="btn btn-primary" onclick="window.location.reload()">
            <i class="fas fa-arrow-left mr-2"></i>Back to All Products
        </button>
    `;
    container.appendChild(backButtonContainer);
}
</script>

<!-- Add this CSS for the toast notifications -->
<style>
.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    min-width: 200px;
    background-color: white;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 0.25rem;
    z-index: 1050;
}

.toast-header {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.toast-body {
    padding: 0.75rem;
}
</style>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>