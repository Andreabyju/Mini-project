<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: hhh.php");
    exit();
}

// Check if booking was successful
if (!isset($_SESSION['booking_success']) || !$_SESSION['booking_success']) {
    header("Location: daycare_booking.php");
    exit();
}

// Database connection details
$db_host = "localhost";
$db_username = "root";
$db_password = "";
$db_name = "petstore";

// Database connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get booking details
$booking_id = $_SESSION['booking_id'];
$sql = "SELECT * FROM daycare_bookings WHERE booking_id = $booking_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
} else {
    echo "No booking found.";
    exit();
}

// Calculate number of days
$start_date = new DateTime($booking['start_date']);
$end_date = new DateTime($booking['end_date']);
$days = $end_date->diff($start_date)->days + 1;

$conn->close();

// Clear booking session variables to prevent duplicate display on refresh
$_SESSION['booking_success'] = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Booking Confirmation - The Canine & Feline Co.</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        .confirmation-container {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 50px;
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmation-header h2 {
            color: #00bd56;
        }
        
        .booking-details {
            margin-bottom: 30px;
        }
        
        .detail-row {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            padding: 10px 0;
        }
        
        .detail-label {
            font-weight: bold;
            width: 40%;
        }
        
        .detail-value {
            width: 60%;
        }
        
        .payment-notice {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 5px solid #f5c6cb;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #00bd56;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .booking-summary {
            background: #e9f9f0;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .page-header {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/bg_2.jpg');
            background-size: cover;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 50px;
        }
        
        .total-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00bd56;
        }
        
        .action-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        
        @media print {
            .navbar, .page-header, .action-buttons, footer {
                display: none;
            }
            
            body {
                padding: 0;
                margin: 0;
            }
            
            .confirmation-container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <span class="flaticon-pawprint-1 mr-2"></span>The Canine & Feline Co.
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="fa fa-bars"></span> Menu
            </button>
            <div class="collapse navbar-collapse" id="ftco-nav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a href="index.html" class="nav-link">Home</a></li>
                    <!-- Add other navigation items here if needed -->
                    
                    <li class="nav-item d-flex align-items-center ml-auto">
                        <div class="nav-item"><a href="hhh.php" class="nav-link">Log Out</a></div>
                        
                        <div class="nav-item">
                            <a href="profile.php" class="profile-link">
                                <div class="profile-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="profile-name">
                                    <?php 
                                        if(isset($_SESSION['username']) && !empty($_SESSION['username'])) {
                                            echo htmlspecialchars($_SESSION['username']);
                                        } else {
                                            echo 'Profile';
                                        }
                                    ?>
                                </span>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- END nav -->

    <!-- Page Header -->
    <div class="page-header">
        <div class="container text-center">
            <h1>Booking Confirmation</h1>
            <p class="lead">Thank you for choosing The Canine & Feline Co.</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="confirmation-container">
                    <div class="confirmation-header">
                        <div class="success-icon">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <h2>Booking Confirmed!</h2>
                        <p>Your daycare booking has been successfully confirmed. Please save or print this page for your records.</p>
                    </div>
                    
                    <div class="booking-summary">
                        <p>Date of Booking: <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?></p>
                    </div>
                    
                    <div class="booking-details mt-4">
                        <h4>Pet Information</h4>
                        <div class="detail-row">
                            <div class="detail-label">Pet Name:</div>
                            <div class="detail-value"><?php echo htmlspecialchars(ucfirst($booking['pet_name'])); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Pet Type:</div>
                            <div class="detail-value"><?php echo htmlspecialchars(ucfirst($booking['pet_type'])); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Breed:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['pet_breed']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Age:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['pet_age']); ?> years</div>
                        </div>
                        <?php if(!empty($booking['special_needs'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Special Needs:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($booking['special_needs']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="booking-details">
                        <h4>Booking Details</h4>
                        <div class="detail-row">
                            <div class="detail-label">Start Date:</div>
                            <div class="detail-value"><?php echo date('F j, Y', strtotime($booking['start_date'])); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">End Date:</div>
                            <div class="detail-value"><?php echo date('F j, Y', strtotime($booking['end_date'])); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Duration:</div>
                            <div class="detail-value"><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Service Type:</div>
                            <div class="detail-value">
                                <?php echo $booking['service_type'] == 'half' ? 'Half Day (₹250 per day)' : 'Full Day (₹500 per day)'; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="booking-details">
                        <h4>Additional Services</h4>
                        <?php if($booking['extra_play']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Extra Play Time:</div>
                            <div class="detail-value">Yes (₹100 × <?php echo $booking['extra_play_days']; ?> days = ₹<?php echo 100 * $booking['extra_play_days']; ?>)</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($booking['special_meal']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Premium Meal:</div>
                            <div class="detail-value">Yes (₹150 × <?php echo $booking['special_meal_days']; ?> days = ₹<?php echo 150 * $booking['special_meal_days']; ?>)</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($booking['training']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Basic Training:</div>
                            <div class="detail-value">Yes (₹200 × <?php echo $booking['training_days']; ?> days = ₹<?php echo 200 * $booking['training_days']; ?>)</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if($booking['pickup_service']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Pickup & Drop Service:</div>
                            <div class="detail-value">Yes (₹300 flat)</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!$booking['extra_play'] && !$booking['special_meal'] && !$booking['training'] && !$booking['pickup_service']): ?>
                        <div class="detail-row">
                            <div class="detail-value">No additional services selected</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="booking-details">
                        <div class="detail-row">
                            <div class="detail-label">Total Amount:</div>
                            <div class="detail-value total-price">₹<?php echo htmlspecialchars($booking['total_price']); ?></div>
                        </div>
                    </div>
                    
                    <div class="payment-notice">
                        <h5><i class="fa fa-info-circle"></i> Payment Information</h5>
                        <p class="mb-0"><strong>Note:</strong> Payment should be made only after the service has been completed. You can pay by cash, credit/debit card, or UPI at our facility.</p>
                    </div>
                    
                    <div class="action-buttons">
                        <button onclick="window.print()" class="btn btn-outline-primary">
                            <i class="fa fa-print"></i> Print Confirmation
                        </button>
                        <a href="hhh2.php" class="btn btn-primary">
                            Return to Home Page
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <!-- Footer content here -->
    </footer>

    <!-- loader -->
    <div id="ftco-loader" class="show fullscreen"><svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#F96D00"/></svg></div>

    <!-- Scripts -->
    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-migrate-3.0.1.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.easing.1.3.js"></script>
    <script src="js/jquery.waypoints.min.js"></script>
    <script src="js/jquery.stellar.min.js"></script>
    <script src="js/jquery.animateNumber.min.js"></script>
    <script src="js/bootstrap-datepicker.js"></script>
    <script src="js/jquery.timepicker.min.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/jquery.magnific-popup.min.js"></script>
    <script src="js/scrollax.min.js"></script>
    <script src="js/main.js"></script>
    
</body>
</html>