<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['username'])) {
    header("Location: hhh.php");
    exit();
}

// Database connection details - MODIFY THESE WITH YOUR ACTUAL CREDENTIALS
$db_host = "localhost";
$db_username = "root"; 
$db_password = ""; 
$db_name = "petstore"; 

// Database connection with error handling
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create table with explicit debugging
try {
    // Create table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS daycare_bookings (
        booking_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        pet_name VARCHAR(100) NOT NULL,
        pet_type VARCHAR(50) NOT NULL,
        pet_breed VARCHAR(100),
        pet_age DECIMAL(5,1),
        special_needs TEXT,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        service_type VARCHAR(10) NOT NULL,
        extra_play BOOLEAN DEFAULT 0,
        extra_play_days INT DEFAULT 0,
        special_meal BOOLEAN DEFAULT 0,
        special_meal_days INT DEFAULT 0,
        training BOOLEAN DEFAULT 0,
        training_days INT DEFAULT 0,
        pickup_service BOOLEAN DEFAULT 0,
        total_price DECIMAL(10,2) NOT NULL,
        booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'confirmed'
    )";
    
    if ($conn->query($create_table_sql) === FALSE) {
        echo "<div style='color:red; padding:10px; background:#ffe0e0; border:1px solid red; margin:10px;'>
              Error creating table: " . $conn->error . "</div>";
    }
    // Remove or comment out the success message
    /*
    else {
        // Verify table was created
        $check_table = $conn->query("SHOW TABLES LIKE 'daycare_bookings'");
        if ($check_table->num_rows == 0) {
            echo "<div style='color:orange; padding:10px; background:#fff8e0; border:1px solid orange; margin:10px;'>
                  Table creation command succeeded but table does not exist. Please check database permissions.</div>";
        } else {
            echo "<div style='color:green; padding:10px; background:#e0ffe0; border:1px solid green; margin:10px;'>
                  Table 'daycare_bookings' exists or was successfully created.</div>";
        }
    }
    */
} catch (Exception $e) {
    echo "<div style='color:red; padding:10px; background:#ffe0e0; border:1px solid red; margin:10px;'>
          Exception: " . $e->getMessage() . "</div>";
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user ID from session (assuming you have user_id stored in session)
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Get form data and sanitize
    $pet_name = $conn->real_escape_string($_POST['petName']);
    $pet_type = $conn->real_escape_string($_POST['petType']);
    $pet_breed = $conn->real_escape_string($_POST['petBreed']);
    $pet_age = !empty($_POST['petAge']) ? floatval($_POST['petAge']) : NULL;
    $special_needs = $conn->real_escape_string($_POST['specialNeeds']);
    $start_date = $conn->real_escape_string($_POST['startDate']);
    $end_date = $conn->real_escape_string($_POST['endDate']);
    $service_type = $conn->real_escape_string($_POST['serviceType']);
    
    // Calculate days between dates
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $days = $end->diff($start)->days + 1;
    
    // Process addons
    $addons = isset($_POST['addons']) ? $_POST['addons'] : [];
    $extra_play = in_array('extraPlay', $addons) ? 1 : 0;
    $extra_play_days = isset($_POST['extraPlayDays']) ? intval($_POST['extraPlayDays']) : 0;
    
    $special_meal = in_array('specialMeal', $addons) ? 1 : 0;
    $special_meal_days = isset($_POST['specialMealDays']) ? intval($_POST['specialMealDays']) : 0;
    
    $training = in_array('training', $addons) ? 1 : 0;
    $training_days = isset($_POST['trainingDays']) ? intval($_POST['trainingDays']) : 0;
    
    $pickup = in_array('pickup', $addons) ? 1 : 0;
    
    // Calculate total price
    $base_rate = ($service_type == 'half') ? 250 : 500;
    $base_total = $base_rate * $days;
    
    $addon_total = 0;
    if ($extra_play) $addon_total += 100 * $extra_play_days;
    if ($special_meal) $addon_total += 150 * $special_meal_days;
    if ($training) $addon_total += 200 * $training_days;
    if ($pickup) $addon_total += 300;
    
    $total_price = $base_total + $addon_total;
    
    // Insert into database
    $sql = "INSERT INTO daycare_bookings (
        user_id, pet_name, pet_type, pet_breed, pet_age, special_needs, 
        start_date, end_date, service_type, 
        extra_play, extra_play_days, special_meal, special_meal_days, 
        training, training_days, pickup_service, total_price
    ) VALUES (
        $user_id, '$pet_name', '$pet_type', '$pet_breed', " . ($pet_age === NULL ? "NULL" : $pet_age) . ", '$special_needs', 
        '$start_date', '$end_date', '$service_type', 
        $extra_play, $extra_play_days, $special_meal, $special_meal_days, 
        $training, $training_days, $pickup, $total_price
    )";
    
    if ($conn->query($sql) === TRUE) {
        // Store booking ID in session for confirmation page
        $_SESSION['booking_id'] = $conn->insert_id;
        $_SESSION['booking_success'] = true;
        $conn->close();
        header("Location: booking_confirmation.php");
        exit();
    } else {
        $error_message = "Error: " . $sql . "<br>" . $conn->error;
        $conn->close();
        // You could redirect to an error page or display the error
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Pet Daycare Booking - The Canine & Feline Co.</title>
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
        .booking-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .service-option {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .service-option:hover {
            background: #f1f1f1;
        }
        
        .service-option.selected {
            border-color: #00bd56;
            background: rgba(0, 189, 86, 0.1);
        }
        
        .addon-item {
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        .price-display {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: right;
            color: #00bd56;
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
        
        .addon-days {
            width: 70px;
            display: inline-block;
        }
        
        .days-info {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
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

    <!-- Page Header -->
    <div class="page-header">
        <div class="container text-center">
            <h1>Pet Daycare Booking</h1>
            <p class="lead">Give your pet the care they deserve while you're away</p>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="booking-form">
                    <form id="daycareForm" method="post" action="">
                        <!-- Pet Information -->
                        <div class="form-section">
                            <h3 class="mb-4">Pet Information</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="petName">Pet Name</label>
                                        <input type="text" class="form-control" id="petName" name="petName" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="petType">Pet Type</label>
                                        <select class="form-control" id="petType" name="petType" required>
                                            <option value="">Select type</option>
                                            <option value="dog">Dog</option>
                                            <option value="cat">Cat</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="petBreed">Breed</label>
                                        <input type="text" class="form-control" id="petBreed" name="petBreed">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="petAge">Age</label>
                                        <input type="number" class="form-control" id="petAge" name="petAge" min="0" step="0.1">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="specialNeeds">Special Needs or Medical Conditions</label>
                                <textarea class="form-control" id="specialNeeds" name="specialNeeds" rows="3"></textarea>
                            </div>
                        </div>

                        <!-- Booking Details -->
                        <div class="form-section">
                            <h3 class="mb-4">Booking Details</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="startDate">Start Date</label>
                                        <input type="date" class="form-control" id="startDate" name="startDate" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="endDate">End Date</label>
                                        <input type="date" class="form-control" id="endDate" name="endDate" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label>Service Type</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="service-option" onclick="selectService('half')">
                                            <input type="radio" name="serviceType" id="halfDay" value="half" checked>
                                            <label for="halfDay">Half Day (₹250 per day)</label>
                                            <p class="text-muted small">Up to 5 hours of care, includes feeding and basic play</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="service-option" onclick="selectService('full')">
                                            <input type="radio" name="serviceType" id="fullDay" value="full">
                                            <label for="fullDay">Full Day (₹500 per day)</label>
                                            <p class="text-muted small">Up to 10 hours of care, includes feeding, extended play, and grooming</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3 days-info alert alert-info" id="totalDaysInfo">
                                Total booking: <span id="totalDays">0</span> days
                            </div>
                        </div>

                        <!-- Additional Services -->
                        <div class="form-section">
                            <h3 class="mb-4">Additional Services</h3>
                            <div class="addon-item">
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input addon-checkbox" id="extraPlay" name="addons[]" value="extraPlay" data-price="100">
                                            <label class="custom-control-label" for="extraPlay">Extra Play Time (₹100 per day)</label>
                                            <p class="text-muted small">Additional 1 hour of dedicated play time with staff</p>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group mb-0">
                                            <label for="extraPlayDays">Number of days:</label>
                                            <input type="number" class="form-control addon-days" id="extraPlayDays" name="extraPlayDays" min="1" value="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="addon-item">
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input addon-checkbox" id="specialMeal" name="addons[]" value="specialMeal" data-price="150">
                                            <label class="custom-control-label" for="specialMeal">Premium Meal (₹150 per day)</label>
                                            <p class="text-muted small">High-quality pet food tailored to your pet's needs</p>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group mb-0">
                                            <label for="specialMealDays">Number of days:</label>
                                            <input type="number" class="form-control addon-days" id="specialMealDays" name="specialMealDays" min="1" value="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="addon-item">
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input addon-checkbox" id="training" name="addons[]" value="training" data-price="200">
                                            <label class="custom-control-label" for="training">Basic Training Session (₹200 per day)</label>
                                            <p class="text-muted small">30-minute training session with our expert trainers</p>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group mb-0">
                                            <label for="trainingDays">Number of days:</label>
                                            <input type="number" class="form-control addon-days" id="trainingDays" name="trainingDays" min="1" value="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="addon-item">
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input addon-checkbox" id="pickup" name="addons[]" value="pickup" data-price="300">
                                            <label class="custom-control-label" for="pickup">Pickup & Drop Service (₹300 flat)</label>
                                            <p class="text-muted small">We'll pick up and drop off your pet within city limits</p>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group mb-0">
                                            <span class="text-muted">(One-time service)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Calculation -->
                        <div class="form-section">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3>Total Amount</h3>
                                </div>
                                <div class="col-md-6">
                                    <div class="price-display" id="totalPrice">₹0</div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">Book Now</button>
                    </form>
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
    
    <script>
        // JavaScript for dynamic price calculation and UI enhancements
        $(document).ready(function() {
            // Initialize price calculation
            updateTotalDays();
            calculateTotal();
            
            // Add event listeners
            $('#startDate, #endDate').on('change', function() {
                updateTotalDays();
                calculateTotal();
            });
            
            $('input[name="serviceType"]').on('change', calculateTotal);
            
            // When a checkbox is clicked
            $('.addon-checkbox').on('change', function() {
                calculateTotal();
            });
            
            // When addon days are changed - ensure immediate calculation
            $('.addon-days').on('input', function() {
                calculateTotal();
            });
            
            // Make the entire service option clickable
            $('.service-option').on('click', function() {
                $(this).find('input[type="radio"]').prop('checked', true);
                $('.service-option').removeClass('selected');
                $(this).addClass('selected');
                calculateTotal();
            });
            
            // Initialize selected state
            $('input[name="serviceType"]:checked').closest('.service-option').addClass('selected');
            
            // Function to update end date min attribute
            function updateEndDateMin() {
                const startDate = $('#startDate').val();
                if (startDate) {
                    $('#endDate').attr('min', startDate);
                    
                    // If current end date is before new start date, reset it
                    const endDate = $('#endDate').val();
                    if (endDate && endDate < startDate) {
                        $('#endDate').val(startDate);
                        updateTotalDays();
                        calculateTotal();
                    }
                }
            }

            // Add event listener for start date changes
            $('#startDate').on('change', function() {
                updateEndDateMin();
                validateField(this);
                validateDateRange();
            });

            // Initialize end date min on page load
            updateEndDateMin();
            
            // Disable keyboard input for date fields to prevent manual entry
            $('#startDate, #endDate').on('keydown', function(e) {
                e.preventDefault();
                return false;
            });
        });
        
        function selectService(type) {
            if (type === 'half') {
                $('#halfDay').prop('checked', true);
            } else {
                $('#fullDay').prop('checked', true);
            }
            $('.service-option').removeClass('selected');
            $('input[name="serviceType"]:checked').closest('.service-option').addClass('selected');
            calculateTotal();
        }
        
        function calculateDaysBetweenDates() {
            const startDate = new Date($('#startDate').val());
            const endDate = new Date($('#endDate').val());
            
            // Check if dates are valid
            if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                return 0;
            }
            
            // Check if end date is after start date
            if (endDate < startDate) {
                return 0;
            }
            
            // Calculate difference in days
            const diffTime = Math.abs(endDate - startDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
            
            return diffDays;
        }
        
        function updateTotalDays() {
            const days = calculateDaysBetweenDates();
            $('#totalDays').text(days);
            
            // Update all addon day inputs max value
            $('.addon-days').attr('max', days > 0 ? days : 1);
        }
        
        function calculateTotal() {
            const totalDays = calculateDaysBetweenDates();
            if (totalDays <= 0) {
                $('#totalPrice').text('₹0');
                return;
            }
            
            // Calculate base cost
            const baseRate = $('input[name="serviceType"]:checked').val() === 'half' ? 250 : 500;
            const baseTotal = baseRate * totalDays;
            
            // For debugging - show breakdown in console
            console.log("Base service: " + baseRate + " × " + totalDays + " days = ₹" + baseTotal);
            
            // Calculate addons
            let addonTotal = 0;
            let addonDetails = [];
            
            $('.addon-checkbox').each(function() {
                if ($(this).is(':checked')) {
                    const addonId = $(this).attr('id');
                    const price = parseInt($(this).data('price'));
                    const addonName = $(this).next('label').text().split('(')[0].trim();
                    
                    // Pickup service is flat rate
                    if (addonId === 'pickup') {
                        addonTotal += price;
                        addonDetails.push(addonName + ": ₹" + price + " (flat rate)");
                    } else {
                        const days = parseInt($('#' + addonId + 'Days').val()) || 1;
                        const addonCost = price * days;
                        addonTotal += addonCost;
                        addonDetails.push(addonName + ": ₹" + price + " × " + days + " days = ₹" + addonCost);
                    }
                }
            });
            
            // Log addon details to console for debugging
            console.log("Add-on services:");
            addonDetails.forEach(detail => console.log("- " + detail));
            
            const total = baseTotal + addonTotal;
            console.log("Total: ₹" + total);
            
            // Update display
            $('#totalPrice').text('₹' + total);
            
            // Show breakdown if there are addons
            if (addonDetails.length > 0) {
                let breakdownHTML = `<div class="mt-2 price-breakdown">
                    <p class="mb-1">Price Breakdown:</p>
                    <p class="mb-1">Base Service: ₹${baseRate} × ${totalDays} days = ₹${baseTotal}</p>`;
                    
                addonDetails.forEach(detail => {
                    breakdownHTML += `<p class="mb-1">- ${detail}</p>`;
                });
                
                breakdownHTML += `<p class="mb-0"><strong>Total: ₹${total}</strong></p></div>`;
                
                // Add or update breakdown section
                if ($('.price-breakdown').length) {
                    $('.price-breakdown').replaceWith(breakdownHTML);
                } else {
                    $('#totalPrice').after(breakdownHTML);
                }
            } else {
                $('.price-breakdown').remove();
            }
        }

        // Validation helper functions
        function showError(element, message) {
            // Remove existing error message if any
            removeError(element);
            
            const errorDiv = $('<div class="invalid-feedback">' + message + '</div>');
            $(element).addClass('is-invalid').after(errorDiv);
        }

        function showSuccess(element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
            removeError(element);
        }

        function removeError(element) {
            $(element).removeClass('is-invalid is-valid');
            $(element).next('.invalid-feedback').remove();
        }

        // Live validation setup
        $(document).ready(function() {
            // Add validation styles to form
            const formInputs = '#daycareForm input, #daycareForm select, #daycareForm textarea';
            $(formInputs).on('blur change', function() {
                validateField(this);
            });

            // Prevent form submission if validation fails
            $('#daycareForm').on('submit', function(e) {
                let isValid = true;
                
                // Validate all fields
                $(formInputs).each(function() {
                    if (!validateField(this)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = $('.is-invalid').first();
                    if (firstError.length) {
                        $('html, body').animate({
                            scrollTop: firstError.offset().top - 100
                        }, 500);
                    }
                }
            });
        });

        function validateField(field) {
            const $field = $(field);
            const value = $field.val().trim();
            const fieldId = $field.attr('id');
            
            // Common validation rules
            if ($field.prop('required') && !value) {
                showError(field, 'This field is required');
                return false;
            }

            // Specific field validations
            switch (fieldId) {
                case 'petName':
                    if (value.length < 2) {
                        showError(field, 'Pet name must be at least 2 characters long');
                        return false;
                    }
                    if (!/^[a-zA-Z\s]+$/.test(value)) {
                        showError(field, 'Pet name should only contain letters and spaces');
                        return false;
                    }
                    break;

                case 'petAge':
                    if (value && (isNaN(value) || value < 0 || value > 30)) {
                        showError(field, 'Please enter a valid age between 0 and 30');
                        return false;
                    }
                    break;

                case 'startDate':
                    const startDate = new Date(value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    if (startDate < today) {
                        showError(field, 'Start date cannot be in the past');
                        return false;
                    }
                    validateDateRange();
                    break;

                case 'endDate':
                    validateDateRange();
                    break;

                case 'extraPlayDays':
                case 'specialMealDays':
                case 'trainingDays':
                    const totalDays = calculateDaysBetweenDates();
                    if (parseInt(value) > totalDays) {
                        showError(field, 'Cannot exceed total booking days (' + totalDays + ')');
                        return false;
                    }
                    if (parseInt(value) < 1) {
                        showError(field, 'Minimum 1 day required');
                        return false;
                    }
                    break;
            }

            showSuccess(field);
            return true;
        }

        function validateDateRange() {
            const startDate = new Date($('#startDate').val());
            const endDate = new Date($('#endDate').val());
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
                // Validate start date is not in past
                if (startDate < today) {
                    showError('#startDate', 'Start date cannot be in the past');
                    return false;
                }

                // Validate end date is not before start date
                if (endDate < startDate) {
                    showError('#endDate', 'End date cannot be before start date');
                    $('#endDate').val($('#startDate').val()); // Reset end date to start date
                    updateTotalDays();
                    calculateTotal();
                    return false;
                }

                // Validate maximum booking duration (30 days)
                const daysDiff = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
                if (daysDiff > 30) {
                    showError('#endDate', 'Maximum booking duration is 30 days');
                    return false;
                }

                showSuccess('#startDate');
                showSuccess('#endDate');
                return true;
            }
            return false;
        }

        // Add CSS styles for validation
        const validationStyles = `
            <style>
                .is-invalid {
                    border-color: #dc3545 !important;
                    padding-right: calc(1.5em + 0.75rem) !important;
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e") !important;
                    background-repeat: no-repeat !important;
                    background-position: right calc(0.375em + 0.1875rem) center !important;
                    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
                }

                .is-valid {
                    border-color: #198754 !important;
                    padding-right: calc(1.5em + 0.75rem) !important;
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e") !important;
                    background-repeat: no-repeat !important;
                    background-position: right calc(0.375em + 0.1875rem) center !important;
                    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
                }

                .invalid-feedback {
                    display: block;
                    width: 100%;
                    margin-top: 0.25rem;
                    font-size: 0.875em;
                    color: #dc3545;
                }

                .form-group {
                    margin-bottom: 1rem;
                }
            </style>
        `;

        // Add validation styles to the page
        $('head').append(validationStyles);
    </script>
</body>
</html> 