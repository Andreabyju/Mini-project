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
						<a href="#" class="nav-link">
							<i class="fa-solid fa-cart-shopping fa-lg" style="color: white;"></i> 
						</a>
					</li>
          </ul>
        </div>
      </div>
    </nav>
    <!-- END nav -->
<?php
session_start();

// Sample product data - In a real application, this would come from a database
$catFood = [
   
    
];

$dogFood = [
    [
        'id' => 8,
        'name' => 'Large Breed Dog Food',
        'description' => 'Complete nutrition for large breed adult dogs with glucosamine for joint health.',
        'price' => 39.99,
        'image' => 'images/dog-food-1.jpg'
    ],
    [
        'id' => 9,
        'name' => 'Puppy Growth Formula',
        'description' => 'Rich in DHA for brain development and proteins for growing puppies.',
        'price' => 32.99,
        'image' => 'images/dog-food-2.jpg'
    ],
    [
        'id' => 10,
        'name' => 'Grain-Free Dog Food',
        'description' => 'Premium grain-free formula with sweet potato and real meat.',
        'price' => 44.99,
        'image' => 'images/dog-food-3.jpg'
    ],
    [
        'id' => 11,
        'name' => 'Large Breed Dog Food',
        'description' => 'Complete nutrition for large breed adult dogs with glucosamine for joint health.',
        'price' => 39.99,
        'image' => 'images/dog-food-1.jpg'
    ],
    [
        'id' => 12,
        'name' => 'Large Breed Dog Food',
        'description' => 'Complete nutrition for large breed adult dogs with glucosamine for joint health.',
        'price' => 39.99,
        'image' => 'images/dog-food-1.jpg'
    ],
    [
        'id' => 13,
        'name' => 'Large Breed Dog Food',
        'description' => 'Complete nutrition for large breed adult dogs with glucosamine for joint health.',
        'price' => 39.99,
        'image' => 'images/dog-food-1.jpg'
    ]
];
?>

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
    </style>
</head>
<body>

<!-- Include your website's navigation here -->

<div class="container my-5">
    <!-- Cat Food Section -->
    <h2 class="category-title">Cat Food Products</h2>
    <div class="row mb-5">
        <?php foreach($catFood as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="product-card">
                    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image w-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                        <p class="card-text"><?php echo $product['description']; ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                            <button class="btn btn-primary add-to-cart-btn" 
                                    onclick="addToCart(<?php echo $product['id']; ?>)">
                                <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Dog Food Section -->
    <h2 class="category-title">Dog Food Products</h2>
    <div class="row">
        <?php foreach($dogFood as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="product-card">
                    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image w-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                        <p class="card-text"><?php echo $product['description']; ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                            <button class="btn btn-primary add-to-cart-btn" 
                                    onclick="addToCart(<?php echo $product['id']; ?>)">
                                <i class="fas fa-shopping-cart mr-2"></i>Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add to Cart JavaScript -->
<script>
function addToCart(productId) {
    // Here you would typically make an AJAX call to add the item to the cart
    // For example:
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
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert('Product added to cart!');
            // Update cart icon/counter if needed
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding product to cart');
    });
}
</script>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>