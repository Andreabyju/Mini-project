<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: hhh.php");
    exit();
}

// Get user ID and username from session
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'] ?? 0;

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

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Fetch user and pet information
$user_query = "SELECT * FROM users WHERE username = '$username' OR user_id = $user_id";
$user_result = $conn->query($user_query);
$user_data = $user_result->fetch_assoc();

// Fetch product purchase history with error handling
try {
    $purchase_query = "SELECT 
                        o.order_id, o.created_at as order_date, o.total_amount, o.status,
                        oi.product_id, oi.quantity, oi.price,
                        p.name as product_name, p.image_url
                       FROM orders o
                       JOIN order_items oi ON o.order_id = oi.order_id
                       JOIN products p ON oi.product_id = p.id
                       WHERE o.user_id = $user_id
                       ORDER BY o.created_at DESC";
    $purchase_result = $conn->query($purchase_query);
    
    // Log query info for debugging
    error_log("Purchase query executed. Found rows: " . $purchase_result->num_rows);
} catch (Exception $e) {
    error_log("Error fetching purchase history: " . $e->getMessage());
    $purchase_result = null;
}

// Fetch grooming booking history
// Check if the table exists first
$check_table_query = "SHOW TABLES LIKE 'grooming_appointments'";
$table_exists = $conn->query($check_table_query);

if ($table_exists && $table_exists->num_rows > 0) {
    $grooming_query = "SELECT * FROM grooming_appointments 
                      WHERE user_id = $user_id 
                      ORDER BY booking_date DESC";
    $grooming_result = $conn->query($grooming_query);
} else {
    // Table doesn't exist, set result to null
    $grooming_result = null;
}

// Fetch daycare booking history
$daycare_query = "SELECT * FROM daycare_bookings 
                  WHERE user_id = $user_id 
                  ORDER BY booking_date DESC";
$daycare_result = $conn->query($daycare_query);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Dashboard - The Canine & Feline Co.</title>
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
        .dashboard-container {
            padding: 30px 0;
        }
        
        .dashboard-header {
            background: linear-gradient(to right, #00bd56, #039447);
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .dashboard-card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
            transition: transform 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .dashboard-section-title {
            border-bottom: 2px solid #00bd56;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #444;
        }
        
        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }
        
        .nav-tabs .nav-link {
            margin-bottom: -2px;
            border: none;
            color: #495057;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: #00bd56;
            background-color: transparent;
            border-bottom: 2px solid #00bd56;
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .service-item, .order-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 0;
        }
        
        .service-item:last-child, .order-item:last-child {
            border-bottom: none;
        }
        
        .service-date, .order-date {
            font-weight: 500;
            color: #00bd56;
        }
        
        .service-status, .order-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-upcoming {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid #f8f9fa;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            object-fit: cover;
        }
        
        .pet-details {
            margin-left: 20px;
        }
        
        .pet-name {
            font-size: 1.2rem;
            font-weight: 500;
            color: #00bd56;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .pet-info {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .pet-details {
                margin-left: 0;
                margin-top: 20px;
            }
        }
        
        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #00bd56;
            margin-right: 20px;
        }
        
        .user-details {
            flex: 1;
        }
        
        .edit-profile-btn {
            margin-left: auto;
        }
        
        .stat-card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #00bd56;
        }
        
        .stat-count {
            font-size: 2rem;
            font-weight: 700;
            color: #343a40;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
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
                    <li class="nav-item"><a href="hhh2.php" class="nav-link">Home</a></li>
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

    <!-- Remove the hero-wrap section completely and start with the dashboard container -->
    <div class="container dashboard-container" style="margin-top: 30px;">
        <div class="dashboard-header">
            <div class="row">
                <div class="col-md-8">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fa fa-user"></i>
                        </div>
                        <div class="user-details">
                            <h2>Welcome, <?php echo htmlspecialchars($user_data['name']); ?>!</h2>
                            <p>Member since: <?php echo date('F Y', strtotime($user_data['date_joined'] ?? date('Y-m-d'))); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <a href="#" class="btn btn-outline-light edit-profile-btn"><i class="fa fa-pencil"></i> Edit Profile</a>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fa fa-shopping-bag"></i>
                    <div class="stat-count"><?php echo $purchase_result ? $purchase_result->num_rows : 0; ?></div>
                    <div class="stat-label">Products Purchased</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fa fa-cut"></i>
                    <div class="stat-count"><?php echo $grooming_result ? $grooming_result->num_rows : 0; ?></div>
                    <div class="stat-label">Grooming Sessions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fa fa-home"></i>
                    <div class="stat-count"><?php echo $daycare_result ? $daycare_result->num_rows : 0; ?></div>
                    <div class="stat-label">Daycare Bookings</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fa fa-paw"></i>
                    <div class="stat-count">1</div>
                    <div class="stat-label">Your Pets</div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs" id="dashboardTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab">My Profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pet-tab" data-toggle="tab" href="#pet" role="tab">Pet Information</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="orders-tab" data-toggle="tab" href="#orders" role="tab">Purchase History</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="grooming-tab" data-toggle="tab" href="#grooming" role="tab">Grooming History</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="daycare-tab" data-toggle="tab" href="#daycare" role="tab">Daycare History</a>
            </li>
        </ul>
        
        <div class="tab-content" id="dashboardTabContent">
            <!-- Profile Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                <div class="dashboard-card">
                    <h4 class="dashboard-section-title">Personal Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($user_data['name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user_data['phone_number']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($user_data['address']); ?></p>
                            <p><strong>Pincode:</strong> <?php echo htmlspecialchars($user_data['pincode']); ?></p>
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pet Information Tab -->
            <div class="tab-pane fade" id="pet" role="tabpanel" aria-labelledby="pet-tab">
                <div class="dashboard-card">
                    <h4 class="dashboard-section-title">Pet Information</h4>
                    <div class="d-flex pet-info">
                        <div>
                            <img src="images/dog-placeholder.jpg" alt="Pet" class="profile-image">
                        </div>
                        <div class="pet-details">
                            <p class="pet-name"><?php echo htmlspecialchars($user_data['pet_name']); ?></p>
                            <p><strong>Type:</strong> <?php echo htmlspecialchars($user_data['pet_type']); ?></p>
                            <p><strong>Breed:</strong> <?php echo htmlspecialchars($user_data['pet_breed']); ?></p>
                            <p><strong>Age:</strong> <?php echo htmlspecialchars($user_data['pet_age']); ?> years</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Purchase History Tab -->
            <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                <div class="dashboard-card">
                    <h4 class="dashboard-section-title">Purchase History</h4>
                    
                    <?php if ($purchase_result && $purchase_result->num_rows > 0): ?>
                        <?php
                        $current_order_id = 0;
                        while ($order = $purchase_result->fetch_assoc()):
                            if ($current_order_id != $order['order_id']):
                                if ($current_order_id != 0) echo '</div></div>'; // Close previous order
                                $current_order_id = $order['order_id'];
                        ?>
                                <div class="order-item">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <span class="order-date">Order Date: <?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                                            <p><strong>Order #:</strong> <?php echo $order['order_id']; ?></p>
                                        </div>
                                        <div>
                                            <span class="service-status status-completed">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row">
                        <?php endif; ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="d-flex">
                                                <img src="uploads/<?php echo htmlspecialchars($order['image_url']); ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>" class="product-image mr-3">
                                                <div>
                                                    <p class="mb-0"><strong><?php echo htmlspecialchars($order['product_name']); ?></strong></p>
                                                    <p class="mb-0">Quantity: <?php echo $order['quantity']; ?></p>
                                                    <p class="mb-0">Price: ₹<?php echo $order['price']; ?></p>
                                                </div>
                                            </div>
                                        </div>
                        <?php endwhile; ?>
                        </div></div> <!-- Close the last order -->
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa fa-shopping-bag"></i>
                            <p>You haven't made any purchases yet.</p>
                            <?php if (isset($conn->error) && !empty($conn->error)): ?>
                            <p class="text-danger">Error: <?php echo $conn->error; ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Grooming History Tab -->
            <div class="tab-pane fade" id="grooming" role="tabpanel" aria-labelledby="grooming-tab">
                <div class="dashboard-card">
                    <h4 class="dashboard-section-title">Grooming & Spa History</h4>
                    
                    <?php if ($grooming_result && $grooming_result->num_rows > 0): ?>
                        <?php while ($booking = $grooming_result->fetch_assoc()): ?>
                            <div class="service-item">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="service-date">
                                            <?php echo date('F j, Y', strtotime($booking['appointment_date'])); ?> at 
                                            <?php echo date('g:i A', strtotime($booking['appointment_time'])); ?>
                                        </p>
                                        <p><strong>Service:</strong> <?php echo ucfirst($booking['service_type']); ?></p>
                                        <p><strong>Pet:</strong> <?php echo htmlspecialchars($booking['pet_name']); ?></p>
                                        <p><strong>Price:</strong> ₹<?php echo $booking['total_price']; ?></p>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <?php 
                                            $booking_date = strtotime($booking['appointment_date'] . ' ' . $booking['appointment_time']);
                                            $today = time();
                                            
                                            if ($booking_date > $today):
                                        ?>
                                            <span class="service-status status-upcoming">Upcoming</span>
                                        <?php else: ?>
                                            <span class="service-status status-completed">Completed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa fa-cut"></i>
                            <p>You haven't booked any grooming services yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Daycare History Tab -->
            <div class="tab-pane fade" id="daycare" role="tabpanel" aria-labelledby="daycare-tab">
                <div class="dashboard-card">
                    <h4 class="dashboard-section-title">Daycare History</h4>
                    
                    <?php if ($daycare_result && $daycare_result->num_rows > 0): ?>
                        <?php while ($booking = $daycare_result->fetch_assoc()): ?>
                            <div class="service-item">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="service-date">
                                            <?php echo date('F j, Y', strtotime($booking['start_date'])); ?> to 
                                            <?php echo date('F j, Y', strtotime($booking['end_date'])); ?>
                                        </p>
                                        <p><strong>Pet:</strong> <?php echo htmlspecialchars($booking['pet_name']); ?></p>
                                        <p><strong>Service:</strong> <?php echo $booking['service_type'] == 'half' ? 'Half Day' : 'Full Day'; ?> Daycare</p>
                                        
                                        <?php 
                                            $addons = [];
                                            if ($booking['extra_play']) $addons[] = 'Extra Play Time';
                                            if ($booking['special_meal']) $addons[] = 'Special Meal';
                                            if ($booking['training']) $addons[] = 'Training';
                                            if ($booking['pickup_service']) $addons[] = 'Pickup Service';
                                            
                                            if (!empty($addons)):
                                        ?>
                                            <p><strong>Add-ons:</strong> <?php echo implode(', ', $addons); ?></p>
                                        <?php endif; ?>
                                        
                                        <p><strong>Price:</strong> ₹<?php echo $booking['total_price']; ?></p>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <?php 
                                            $end_date = strtotime($booking['end_date']);
                                            $today = time();
                                            
                                            if ($end_date > $today):
                                        ?>
                                            <span class="service-status status-upcoming">Active/Upcoming</span>
                                        <?php else: ?>
                                            <span class="service-status status-completed">Completed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa fa-home"></i>
                            <p>You haven't booked any daycare services yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="ftco-footer ftco-section">
        <!-- Footer content -->
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