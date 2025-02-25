<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Pet Sitting - Free Bootstrap 4 Template by Colorlib</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="https://fonts.googleapis.com/css?family=Montserrat:200,300,400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
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
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar" style="background-color: grey;">
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
            <li class="nav-item"><a href="hhh2.php" class="nav-link"><i class="fa-solid fa-house fa-lg icon-black"></i></a></li>
            <li class="nav-item">
						<a href="#" class="nav-link">
							<i class="fa-solid fa-cart-shopping fa-lg icon-black"></i> 
						</a>
					</li>
          </ul>
        </div>
      </div>
    </nav>

    <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Sample product data - In a real application, this would come from a database
$catFood = [
    [
        'id' => 1,
        'name' => 'Premium Cat Kibble',
        'description' => 'High-protein dry food perfect for adult cats. Made with real chicken and fish.',
        'price' => 29.99,
        'image' => 'images/cat-food-1.jpg'
    ],
    [
        'id' => 2,
        'name' => 'Gourmet Wet Cat Food',
        'description' => 'Tender chunks in gravy, made with premium ingredients for a taste cats love.',
        'price' => 24.99,
        'image' => 'images/cat-food-2.jpg'
    ],
    [
        'id' => 3,
        'name' => 'Senior Cat Formula',
        'description' => 'Specially formulated for older cats with added vitamins and minerals.',
        'price' => 34.99,
        'image' => 'images/cat-food-3.jpg'
    ],
    [
        'id' => 4,
        'name' => 'Senior Cat Formula',
        'description' => 'Specially formulated for older cats with added vitamins and minerals.',
        'price' => 34.99,
        'image' => 'images/cat-food-3.jpg'
    ],
    [
        'id' => 5,
        'name' => 'Senior Cat Formula',
        'description' => 'Specially formulated for older cats with added vitamins and minerals.',
        'price' => 34.99,
        'image' => 'images/cat-food-3.jpg'
    ],
    [
        'id' => 6,
        'name' => 'Senior Cat Formula',
        'description' => 'Specially formulated for older cats with added vitamins and minerals.',
        'price' => 34.99,
        'image' => 'images/cat-food-3.jpg'
    ]
    
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
