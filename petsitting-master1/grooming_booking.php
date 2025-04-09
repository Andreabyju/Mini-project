<?php
session_start();
require_once "connect.php";

// Initialize variables
$errorMsg = "";
$successMsg = "";

// Fetch services from the database (added in manage_services.php)
try {
    // Get all active dog services
    $dogStmt = $conn->prepare("SELECT * FROM services WHERE animal_type IN ('dog', 'both') AND is_available = 1");
    $dogStmt->execute();
    $dogServices = $dogStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all active cat services
    $catStmt = $conn->prepare("SELECT * FROM services WHERE animal_type IN ('cat', 'both') AND is_available = 1");
    $catStmt->execute();
    $catServices = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize services by type for display
    $services = [
        'dog' => [],
        'cat' => []
    ];
    
    // Process dog services
    foreach ($dogServices as $service) {
        $serviceId = $service['id'];
        $services['dog'][$serviceId] = [
            'name' => $service['name'],
            'price' => $service['price'],
            'duration' => $service['duration'],
            'description' => $service['description'],
            'type' => $service['service_type']
        ];
    }
    
    // Process cat services
    foreach ($catServices as $service) {
        $serviceId = $service['id'];
        $services['cat'][$serviceId] = [
            'name' => $service['name'],
            'price' => $service['price'],
            'duration' => $service['duration'],
            'description' => $service['description'],
            'type' => $service['service_type']
        ];
    }
} catch (PDOException $e) {
    $errorMsg = "Error loading services: " . $e->getMessage();
}

// Available time slots
$timeSlots = [
    '09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM',
    '12:00 PM', '12:30 PM', '01:00 PM', '01:30 PM', '02:00 PM', '02:30 PM',
    '03:00 PM', '03:30 PM', '04:00 PM', '04:30 PM', '05:00 PM'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $petName = $_POST['pet_name'];
    $petType = $_POST['pet_type'];
    $petBreed = $_POST['pet_breed'] ?? '';
    $petAge = $_POST['pet_age'] ?? '';
    $petWeight = $_POST['pet_weight'] ?? '';
    $ownerName = $_POST['owner_name'];
    $ownerEmail = $_POST['owner_email'];
    $ownerPhone = $_POST['owner_phone'];
    $bookingDate = $_POST['booking_date'];
    $bookingTime = $_POST['booking_time'];
    $specialInstructions = $_POST['special_instructions'] ?? '';
    $selectedServices = isset($_POST['service_type']) ? $_POST['service_type'] : [];
    
    // Validate inputs
    if (empty($petName) || empty($petType) || empty($ownerName) || 
        empty($ownerEmail) || empty($ownerPhone) || empty($bookingDate) || 
        empty($bookingTime) || empty($selectedServices)) {
        $errorMsg = "Please fill in all required fields and select at least one service.";
    } else {
        try {
            // Calculate total price from selected services
            $totalPrice = 0;
            $serviceDetails = [];
            
            // Get details of selected services
            foreach ($selectedServices as $serviceId) {
                $serviceQuery = $conn->prepare("SELECT name, price FROM services WHERE id = :id");
                $serviceQuery->execute(['id' => $serviceId]);
                $serviceInfo = $serviceQuery->fetch(PDO::FETCH_ASSOC);
                
                if ($serviceInfo) {
                    $totalPrice += $serviceInfo['price'];
                    $serviceDetails[$serviceId] = $serviceInfo;
                }
            }
            
            // First create the booking record with total price
            $stmt = $conn->prepare("INSERT INTO bookings (pet_name, pet_type, pet_breed, pet_age, pet_weight, 
                owner_name, owner_email, owner_phone, appointment_date, appointment_time, 
                special_instructions, total_price) 
                VALUES (:pet_name, :pet_type, :pet_breed, :pet_age, :pet_weight, :owner_name, :owner_email, 
                :owner_phone, :appointment_date, :appointment_time, :special_instructions, :total_price)");
            
            $stmt->execute([
                'pet_name' => $petName,
                'pet_type' => $petType,
                'pet_breed' => $petBreed,
                'pet_age' => $petAge,
                'pet_weight' => $petWeight,
                'owner_name' => $ownerName,
                'owner_email' => $ownerEmail,
                'owner_phone' => $ownerPhone,
                'appointment_date' => $bookingDate,
                'appointment_time' => $bookingTime,
                'special_instructions' => $specialInstructions,
                'total_price' => $totalPrice
            ]);
            
            $bookingId = $conn->lastInsertId();
            
            // Then add each selected service to the booking_services table with service details
            foreach ($selectedServices as $serviceId) {
                $stmt = $conn->prepare("INSERT INTO booking_services (booking_id, service_id, service_name, service_price) 
                                        VALUES (:booking_id, :service_id, :service_name, :service_price)");
                $stmt->execute([
                    'booking_id' => $bookingId,
                    'service_id' => $serviceId,
                    'service_name' => $serviceDetails[$serviceId]['name'] ?? '',
                    'service_price' => $serviceDetails[$serviceId]['price'] ?? 0
                ]);
            }
            
            // Redirect to confirmation page with booking ID
            header("Location: grooming_confirmation.php?booking_id=" . $bookingId);
            exit;
        } catch (PDOException $e) {
            $errorMsg = "Error booking appointment: " . $e->getMessage();
        }
    }
}

// Make sure the necessary tables exist
try {
    // Create bookings table if it doesn't exist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pet_name VARCHAR(255) NOT NULL,
            pet_type ENUM('dog', 'cat') NOT NULL,
            pet_breed VARCHAR(255),
            pet_age VARCHAR(50),
            pet_weight VARCHAR(50),
            owner_name VARCHAR(255) NOT NULL,
            owner_email VARCHAR(255) NOT NULL,
            owner_phone VARCHAR(20) NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time VARCHAR(20) NOT NULL,
            special_instructions TEXT,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            total_price DECIMAL(10,2),
            payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Check if total_price column exists in bookings table, add it if not
    $result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'total_price'");
    if ($result->rowCount() == 0) {
        // total_price column doesn't exist, add it
        $conn->exec("ALTER TABLE bookings ADD COLUMN total_price DECIMAL(10,2) AFTER special_instructions");
    }
    
    // Check if payment_status column exists in bookings table, add it if not
    $result = $conn->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'");
    if ($result->rowCount() == 0) {
        // payment_status column doesn't exist, add it
        $conn->exec("ALTER TABLE bookings ADD COLUMN payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending' AFTER total_price");
    }
    
    // Create booking_services junction table if it doesn't exist
    $conn->exec("
        CREATE TABLE IF NOT EXISTS booking_services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            service_id INT NOT NULL,
            service_name VARCHAR(255),
            service_price DECIMAL(10,2),
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
        )
    ");
    
    // Check if service_name column exists in booking_services table, add it if not
    $result = $conn->query("SHOW COLUMNS FROM booking_services LIKE 'service_name'");
    if ($result->rowCount() == 0) {
        // service_name column doesn't exist, add it
        $conn->exec("ALTER TABLE booking_services ADD COLUMN service_name VARCHAR(255) AFTER service_id");
    }
    
    // Check if service_price column exists in booking_services table, add it if not
    $result = $conn->query("SHOW COLUMNS FROM booking_services LIKE 'service_price'");
    if ($result->rowCount() == 0) {
        // service_price column doesn't exist, add it
        $conn->exec("ALTER TABLE booking_services ADD COLUMN service_price DECIMAL(10,2) AFTER service_name");
    }
    
} catch (PDOException $e) {
    $errorMsg = "Table creation error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grooming & Spa Booking - The Canine & Feline Co.</title>
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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    
    <style>
        .service-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .service-card.selected {
            border-color: #00bd56;
            background-color: rgba(0, 189, 86, 0.05);
        }
        
        .service-card.selected::before {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 25px;
            height: 25px;
            background-color: #00bd56;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .service-price {
            color: #00bd56;
            font-weight: bold;
            font-size: 1.25rem;
        }
        
        .booking-form {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .appointment-date-input {
            position: relative;
        }
        
        .appointment-date-input i {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .time-slot {
            display: inline-block;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .time-slot:hover {
            background-color: #f8f9fa;
        }
        
        .time-slot.selected {
            background-color: #00bd56;
            color: white;
            border-color: #00bd56;
        }
        
        .pet-type-selector {
            display: flex;
            margin-bottom: 20px;
        }
        
        .pet-type-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: 10px;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .pet-type-option:hover {
            transform: translateY(-5px);
        }
        
        .pet-type-option.selected {
            border-color: #00bd56;
            background-color: rgba(0, 189, 86, 0.05);
        }
        
        .pet-type-option i {
            font-size: 40px;
            display: block;
            margin-bottom: 10px;
            color: #555;
        }
        
        .pet-type-option.selected i {
            color: #00bd56;
        }
        
        .section-heading {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-weight: 600;
        }
        
        .section-heading::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #00bd56;
        }
        
        .booking-btn {
            background-color: #00bd56;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .booking-btn:hover {
            background-color: #009945;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('images/grooming-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
            text-align: center;
        }
        
        .hero-section h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .hero-section p {
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Added style for total price display */
        .total-price-container {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid #e9ecef;
        }
        
        .total-price-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00bd56;
        }
        
        #selected-services-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        /* Added styles for form validation */
        .form-group.has-error .form-control {
            border-color: #dc3545;
        }
        
        .form-group.has-success .form-control {
            border-color: #28a745;
        }
        
        .error-message {
            color: #dc3545;s
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }
        
        .form-group.has-error .error-message {
            display: block;
        }
        
        /* Required field indicator */
        .required-label::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
      <div class="container">
        <a class="navbar-brand" href="index.html" style="font-weight: 400;">
          <span class="flaticon-pawprint-1 mr-2"></span>The Canine & Feline Co.
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="fa fa-bars"></span> Menu
        </button>
        <div class="collapse navbar-collapse" id="ftco-nav">
          <div class="navbar-nav ml-auto">
            <div class="nav-item">
              <a href="hhh2.php" class="nav-link">
                <i class="fa-solid fa-house mr-2"></i> Home
              </a>
            </div>
          </div>
        </div>
      </div>
    </nav>
    <!-- END nav -->
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1>Pet Grooming & Spa Services</h1>
            <p>Treat your furry friend to a day of pampering and care with our professional grooming services</p>
        </div>
    </div>
    
    <div class="container mb-5">
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger mb-4"><?php echo $errorMsg; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success mb-4"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Left Column: Booking Form -->
            <div class="col-lg-8">
                <div class="booking-form">
                    <h2 class="section-heading">Book a Grooming Appointment</h2>
                    
                    <form method="POST" id="booking-form">
                        <!-- Step 1: Select Pet Type -->
                        <div class="booking-step" id="step-1">
                            <h4 class="mb-4">Step 1: Tell us about your pet</h4>
                            <div class="pet-type-selector">
                                <div class="pet-type-option" data-pet-type="dog">
                                    <i class="fas fa-dog"></i>
                                    <h5>Dog</h5>
                                </div>
                                <div class="pet-type-option" data-pet-type="cat">
                                    <i class="fas fa-cat"></i>
                                    <h5>Cat</h5>
                                </div>
                            </div>
                            <input type="hidden" name="pet_type" id="pet-type-input">
                            
                            <div class="form-group">
                                <label for="pet_name" class="required-label">Pet's Name</label>
                                <input type="text" class="form-control" id="pet_name" name="pet_name" required>
                                <small class="error-message" id="pet_name_error">Please enter your pet's name</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pet_breed">Breed</label>
                                        <input type="text" class="form-control" id="pet_breed" name="pet_breed">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pet_age">Age</label>
                                        <input type="text" class="form-control" id="pet_age" name="pet_age">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pet_weight">Weight (kg)</label>
                                        <input type="number" class="form-control" id="pet_weight" name="pet_weight">
                                        <small class="error-message" id="pet_weight_error">Please enter a valid weight</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 2: Select Service -->
                        <div class="booking-step mt-5" id="step-2">
                            <h4 class="mb-4">Step 2: Choose service(s)</h4>
                            <p class="text-muted">Select one or more services for your pet</p>
                            
                            <!-- Dog Services -->
                            <div id="dog-services" class="services-container">
                                <div class="row">
                                    <?php foreach ($services['dog'] as $id => $service): ?>
                                        <div class="col-md-6">
                                            <div class="service-card multi-select" data-service="<?php echo $id; ?>">
                                                <h5><?php echo $service['name']; ?></h5>
                                                <p class="service-price">₹<?php echo $service['price']; ?></p>
                                                <p class="text-muted mb-0">Duration: <?php echo $service['duration']; ?> minutes</p>
                                                <p class="small text-muted"><?php echo substr($service['description'], 0, 100); ?></p>
                                                <input type="checkbox" name="service_type[]" value="<?php echo $id; ?>" style="display:none;" class="service-checkbox">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Cat Services -->
                            <div id="cat-services" class="services-container" style="display: none;">
                                <div class="row">
                                    <?php foreach ($services['cat'] as $id => $service): ?>
                                        <div class="col-md-6">
                                            <div class="service-card multi-select" data-service="<?php echo $id; ?>">
                                                <h5><?php echo $service['name']; ?></h5>
                                                <p class="service-price">₹<?php echo $service['price']; ?></p>
                                                <p class="text-muted mb-0">Duration: <?php echo $service['duration']; ?> minutes</p>
                                                <p class="small text-muted"><?php echo substr($service['description'], 0, 100); ?></p>
                                                <input type="checkbox" name="service_type[]" value="<?php echo $id; ?>" style="display:none;" class="service-checkbox">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 3: Select Date & Time -->
                        <div class="booking-step mt-5" id="step-3">
                            <h4 class="mb-4">Step 3: Choose date and time</h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="booking_date" class="required-label">Appointment Date</label>
                                        <div class="input-group date">
                                            <input type="text" class="form-control datepicker" id="booking_date" name="booking_date" required readonly>
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="booking_time" class="required-label">Appointment Time</label>
                                        <select class="form-control" id="booking_time" name="booking_time" required>
                                            <option value="">Select a time</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Step 4: Your Information -->
                        <div class="booking-step mt-5" id="step-4">
                            <h4 class="mb-4">Step 4: Your contact information</h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="owner_name" class="required-label">Your Name</label>
                                        <input type="text" class="form-control" id="owner_name" name="owner_name" required>
                                        <small class="error-message" id="owner_name_error">Please enter your full name</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="owner_email" class="required-label">Email Address</label>
                                        <input type="email" class="form-control" id="owner_email" name="owner_email" required>
                                        <small class="error-message" id="owner_email_error">Please enter a valid email address</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="owner_phone" class="required-label">Phone Number</label>
                                <input type="tel" class="form-control" id="owner_phone" name="owner_phone" required>
                                <small class="error-message" id="owner_phone_error">Please enter a valid phone number</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="special_instructions">Special Instructions or Requests</label>
                                <textarea class="form-control" id="special_instructions" name="special_instructions" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="text-center mt-5">
                            <button type="submit" name="book_appointment" class="booking-btn">
                                <i class="fas fa-calendar-check mr-2"></i> Book Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Right Column: Information -->
            <div class="col-lg-4">
                <!-- Selected Services Summary Box (New Position) -->
                <div class="card mb-4" id="service-total" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart mr-2"></i> Selected Services (<span id="selected-count">0</span>)</h5>
                    </div>
                    <div class="card-body">
                        <div id="selected-services-list" class="mb-3">
                            <!-- Selected services will be listed here dynamically -->
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Total:</h5>
                            <h4 class="text-success mb-0">₹<span id="total-price">0</span></h4>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i> Our Grooming Services</h5>
                    </div>
                    <div class="card-body">
                        <p>Our professional grooming services include:</p>
                        <ul class="mb-0">
                            <li>Bath with premium shampoo</li>
                            <li>Blow dry and brush out</li>
                            <li>Nail trimming and filing</li>
                            <li>Ear cleaning</li>
                            <li>Teeth brushing (premium packages)</li>
                            <li>Styling and haircut (premium packages)</li>
                            <li>Aromatherapy (deluxe packages)</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-paw mr-2"></i> Why Choose Us</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Certified professional groomers</li>
                            <li>Cage-free, stress-free environment</li>
                            <li>Premium, pet-safe products</li>
                            <li>Individual attention to each pet</li>
                            <li>Clean, sanitized equipment</li>
                            <li>Special care for seniors and puppies/kittens</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i> Important Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Before Your Appointment:</strong></p>
                        <ul class="mb-0">
                            <li>Please ensure your pet's vaccinations are up to date</li>
                            <li>Arrive 10 minutes before your appointment time</li>
                            <li>Cancellations should be made at least 24 hours in advance</li>
                            <li>Please inform us of any health issues or allergies your pet may have</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>The Canine & Feline Co.</h5>
                    <p>Providing premium care for your pets since 2010. Our professional team is dedicated to keeping your pets healthy and happy.</p>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p><i class="fas fa-map-marker-alt mr-2"></i> 123 Pet Street, Bangalore, India</p>
                    <p><i class="fas fa-phone mr-2"></i> +91 98765 43210</p>
                    <p><i class="fas fa-envelope mr-2"></i> info@caninefeline.com</p>
                </div>
                <div class="col-md-4">
                    <h5>Opening Hours</h5>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                    <p>Saturday: 9:00 AM - 5:00 PM</p>
                    <p>Sunday: Closed</p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> The Canine & Feline Co. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
    $(document).ready(function() {
        // Disable browser's default datepicker and dropdown behavior
        $('input[type="date"]').on('click', function(e) {
            e.preventDefault();
            $(this).attr('type', 'text');
            $(this).attr('placeholder', 'YYYY-MM-DD');
        });

        // Prevent dropdown on number inputs
        $('input[type="number"]').on('wheel', function(e) {
            e.preventDefault();
        });

        // Prevent spinners on number inputs
        const noSpinnerStyles = `
            <style>
                /* Hide spinner for Chrome, Safari, Edge, Opera */
                input::-webkit-outer-spin-button,
                input::-webkit-inner-spin-button {
                    -webkit-appearance: none;
                    margin: 0;
                }

                /* Hide spinner for Firefox */
                input[type=number] {
                    -moz-appearance: textfield;
                }

                /* Custom styling for date inputs */
                input[type="date"]::-webkit-calendar-picker-indicator,
                input[type="date"]::-webkit-inner-spin-button {
                    display: none;
                    -webkit-appearance: none;
                }

                input[type="date"] {
                    position: relative;
                }

                /* Remove dropdown arrow for select elements */
                select {
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    appearance: none;
                    background-image: none !important;
                    padding-right: 0.75rem !important;
                }

                select::-ms-expand {
                    display: none;
                }
            </style>
        `;

        // Add the styles to the page
        $('head').append(noSpinnerStyles);

        // Custom date input handling
        $('input[type="date"]').each(function() {
            $(this).attr('autocomplete', 'off');
            $(this).on('keypress', function(e) {
                // Allow only numbers and dash
                if (!/[\d-]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        });

        // Custom number input handling
        $('input[type="number"]').each(function() {
            $(this).attr('autocomplete', 'off');
            $(this).on('keypress', function(e) {
                // Allow only numbers and decimal point
                if (!/[\d.]/.test(e.key)) {
                    e.preventDefault();
                }
            });
        });

        // Prevent paste of non-numeric values
        $('input[type="number"], input[type="date"]').on('paste', function(e) {
            const pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            if ($(this).attr('type') === 'number' && !/^\d*\.?\d*$/.test(pastedData)) {
                e.preventDefault();
            }
            if ($(this).attr('type') === 'date' && !/^\d{4}-\d{2}-\d{2}$/.test(pastedData)) {
                e.preventDefault();
            }
        });

        // Set dog as default pet type on page load
        $('#pet-type-input').val('dog');
        $('.pet-type-option[data-pet-type="dog"]').addClass('selected');
        
        // Pet Type Selection
        $('.pet-type-option').click(function() {
            $('.pet-type-option').removeClass('selected');
            $(this).addClass('selected');
            
            const petType = $(this).data('pet-type');
            $('#pet-type-input').val(petType);
            
            // Show corresponding services
            $('.services-container').hide();
            $('#' + petType + '-services').show();
            
            // Reset service selections when changing pet type
            $('.service-card').removeClass('selected');
            $('.service-checkbox').prop('checked', false);
            updateSelectedServices();
        });
        
        // Multiple Service Selection
        $('.service-card').click(function() {
            $(this).toggleClass('selected');
            // Toggle the checkbox inside this card
            $(this).find('.service-checkbox').prop('checked', $(this).hasClass('selected'));
            updateSelectedServices();
            
            // Validate service selection
            validateServices();
        });
        
        // Add validation functions
        function validateRequired(field) {
            const value = $(field).val().trim();
            const fieldId = $(field).attr('id');
            const errorMsgId = `#${fieldId}_error`;
            
            if (!value) {
                $(field).closest('.form-group').addClass('has-error').removeClass('has-success');
                return false;
            } else {
                $(field).closest('.form-group').removeClass('has-error').addClass('has-success');
                return true;
            }
        }
        
        function validateEmail(field) {
            const value = $(field).val().trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!value || !emailRegex.test(value)) {
                $(field).closest('.form-group').addClass('has-error').removeClass('has-success');
                return false;
            } else {
                $(field).closest('.form-group').removeClass('has-error').addClass('has-success');
                return true;
            }
        }
        
        function validatePhone(field) {
            const value = $(field).val().trim();
            // Allow various phone formats
            const phoneRegex = /^[\d\s\+\-\(\)]{10,15}$/;
            
            if (!value || !phoneRegex.test(value)) {
                $(field).closest('.form-group').addClass('has-error').removeClass('has-success');
                return false;
            } else {
                $(field).closest('.form-group').removeClass('has-error').addClass('has-success');
                return true;
            }
        }
        
        function validateNumber(field, min = null, max = null) {
            const value = $(field).val().trim();
            const numValue = parseFloat(value);
            
            if (value === '' || isNaN(numValue) || 
                (min !== null && numValue < min) || 
                (max !== null && numValue > max)) {
                $(field).closest('.form-group').addClass('has-error').removeClass('has-success');
                return false;
            } else {
                $(field).closest('.form-group').removeClass('has-error').addClass('has-success');
                return true;
            }
        }
        
        function validateDate(field) {
            const value = $(field).val();
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const selectedDate = new Date(value);
            
            if (!value || selectedDate < today) {
                $(field).closest('.form-group').addClass('has-error').removeClass('has-success');
                return false;
            } else {
                $(field).closest('.form-group').removeClass('has-error').addClass('has-success');
                return true;
            }
        }
        
        function validateServices() {
            if ($('.service-card.selected').length === 0) {
                $('#step-2').addClass('has-error');
                return false;
            } else {
                $('#step-2').removeClass('has-error');
                return true;
            }
        }
        
        // Add event listeners for live validation
        $('#pet_name').on('blur', function() {
            validateRequired(this);
        });
        
        $('#pet_weight').on('blur', function() {
            validateNumber(this, 0);
        });
        
        $('#booking_date').on('change', function() {
            validateDate(this);
        });
        
        $('#booking_time').on('change', function() {
            validateRequired(this);
        });
        
        $('#owner_name').on('blur', function() {
            validateRequired(this);
        });
        
        $('#owner_email').on('blur', function() {
            validateEmail(this);
        });
        
        $('#owner_phone').on('blur', function() {
            validatePhone(this);
        });
        
        // Update selected services and total
        function updateSelectedServices() {
            let totalPrice = 0;
            const servicesList = $('#selected-services-list');
            servicesList.empty();
            
            // Count selected services
            const selectedCount = $('.service-card.selected').length;
            
            // Get all selected services
            $('.service-card.selected').each(function() {
                // Add to visible list and calculate total
                const serviceName = $(this).find('h5').text();
                const servicePrice = parseInt($(this).find('.service-price').text().replace('₹', ''));
                totalPrice += servicePrice;
                
                // Add each service to the list
                servicesList.append(`<div class="mb-1">• ${serviceName} - ₹${servicePrice}</div>`);
            });
            
            // Update the count
            $('#selected-count').text(selectedCount);
            
            // Update the total price display
            $('#total-price').text(totalPrice);
            if (selectedCount > 0) {
                $('#service-total').show();
            } else {
                $('#service-total').hide();
            }
        }
        
        // Form Validation
        $('#booking-form').submit(function(e) {
            let isValid = true;
            
            // Validate all required fields
            isValid = validateRequired($('#pet_name')) && isValid;
            isValid = validateNumber($('#pet_weight'), 0) && isValid;
            isValid = validateRequired($('#owner_name')) && isValid;
            isValid = validateEmail($('#owner_email')) && isValid;
            isValid = validatePhone($('#owner_phone')) && isValid;
            isValid = validateDate($('#booking_date')) && isValid;
            isValid = validateRequired($('#booking_time')) && isValid;
            
            // Check pet type
            if (!$('#pet-type-input').val()) {
                alert('Please select a pet type (Dog or Cat)');
                isValid = false;
            }
            
            // Check if at least one service is selected
            isValid = validateServices() && isValid;
            
            if (!isValid) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('.has-error').first().offset().top - 100
                }, 500);
            }
        });

        // Validation helper functions
        function showError(element, message) {
            const $field = $(element);
            const errorDiv = $field.next('.invalid-feedback');
            
            $field.removeClass('is-valid').addClass('is-invalid');
            if (errorDiv.length) {
                errorDiv.text(message);
            } else {
                $('<div class="invalid-feedback">' + message + '</div>').insertAfter($field);
            }
        }

        function showSuccess(element) {
            const $field = $(element);
            $field.removeClass('is-invalid').addClass('is-valid');
            $field.next('.invalid-feedback').remove();
        }

        // Live validation functions
        function validatePetName(field) {
            const value = $(field).val().trim();
            if (!value) {
                showError(field, "Pet name is required");
                return false;
            }
            if (value.length < 2) {
                showError(field, "Pet name must be at least 2 characters long");
                return false;
            }
            if (!/^[a-zA-Z\s]+$/.test(value)) {
                showError(field, "Pet name should only contain letters and spaces");
                return false;
            }
            showSuccess(field);
            return true;
        }

        function validatePetAge(field) {
            const value = $(field).val().trim();
            if (value && (isNaN(value) || value < 0 || value > 30)) {
                showError(field, "Please enter a valid age between 0 and 30");
                return false;
            }
            showSuccess(field);
            return true;
        }

        function validatePetWeight(field) {
            const value = $(field).val().trim();
            if (value && (isNaN(value) || value <= 0 || value > 100)) {
                showError(field, "Please enter a valid weight between 0 and 100 kg");
                return false;
            }
            showSuccess(field);
            return true;
        }

        function validateDates() {
            const startDate = new Date($('#booking_date').val());
            const endDate = new Date($('#booking_time').val());
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Validate start date
            if (startDate < today) {
                showError('#booking_date', "Start date cannot be in the past");
                return false;
            }

            // Validate end date
            if (endDate < startDate) {
                showError('#booking_time', "End date cannot be before start date");
                return false;
            }

            // Validate booking duration
            const daysDiff = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
            if (daysDiff > 30) {
                showError('#booking_date', "Maximum booking duration is 30 days");
                return false;
            }

            showSuccess('#booking_date');
            showSuccess('#booking_time');
            return true;
        }

        function validateAddonDays(field) {
            const value = parseInt($(field).val());
            const totalDays = calculateDaysBetweenDates();
            
            if (isNaN(value) || value < 1) {
                showError(field, "Minimum 1 day required");
                return false;
            }
            if (value > totalDays) {
                showError(field, `Cannot exceed total booking days (${totalDays})`);
                return false;
            }
            showSuccess(field);
            return true;
        }

        // Add live validation event listeners
        $('#pet_name').on('input blur', function() {
            validatePetName(this);
        });

        $('#pet_age').on('input blur', function() {
            validatePetAge(this);
        });

        $('#pet_weight').on('input blur', function() {
            validatePetWeight(this);
        });

        // Date validation
        $('#booking_date').on('change', function() {
            validateDates();
            // Update end date min attribute
            $('#booking_time').attr('min', $(this).val());
        });

        $('#booking_time').on('change', function() {
            validateDates();
        });

        // Addon days validation
        $('.addon-days').on('input', function() {
            validateAddonDays(this);
        });

        // Service type validation
        $('input[name="service_type"]').on('change', function() {
            const serviceType = $('input[name="service_type"]:checked').val();
            if (!serviceType) {
                showError('input[name="service_type"]', "Please select a service type");
                return false;
            }
            showSuccess('input[name="service_type"]');
            return true;
        });

        // Form submission validation
        $('#booking-form').on('submit', function(e) {
            let isValid = true;

            // Validate all fields
            isValid = validatePetName($('#pet_name')) && isValid;
            isValid = validatePetAge($('#pet_age')) && isValid;
            isValid = validatePetWeight($('#pet_weight')) && isValid;
            isValid = validateDates() && isValid;

            // Validate addon days if checked
            $('.addon-checkbox:checked').each(function() {
                const addonId = $(this).attr('id');
                const daysInput = $(`#${addonId}Days`);
                isValid = validateAddonDays(daysInput) && isValid;
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

        // Add CSS for validation styles
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
            </style>
        `;

        // Add validation styles to the page
        $('head').append(validationStyles);

        // Get today's date in YYYY-MM-DD format
        const today = new Date().toISOString().split('T')[0];
        
        // Set minimum date for both date inputs
        $('#booking_date').attr('min', today);
        
        // Initialize the datepicker
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            startDate: new Date(),
            autoclose: true,
            todayHighlight: true,
            daysOfWeekDisabled: [0], // Disable Sundays
            clearBtn: false,
            orientation: "bottom auto"
        });

        // Style the datepicker
        const datepickerStyles = `
            <style>
                .datepicker {
                    padding: 4px;
                    border-radius: 4px;
                    direction: ltr;
                    z-index: 1000 !important;
                }
                .datepicker-dropdown {
                    top: 0;
                    left: 0;
                    padding: 4px;
                    background-color: white;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    box-shadow: 0 6px 12px rgba(0,0,0,.175);
                }
                .datepicker table {
                    margin: 0;
                    -webkit-touch-callout: none;
                    -webkit-user-select: none;
                    -khtml-user-select: none;
                    -moz-user-select: none;
                    -ms-user-select: none;
                    user-select: none;
                }
                .datepicker table tr td.active,
                .datepicker table tr td.active:hover,
                .datepicker table tr td.active.disabled,
                .datepicker table tr td.active.disabled:hover {
                    background-color: #00bd56 !important;
                    background-image: none;
                    color: #fff !important;
                }
                .datepicker table tr td.today {
                    background-color: #FFF3CD !important;
                    border-color: #FFE69C;
                }
                .datepicker table tr td,
                .datepicker table tr th {
                    text-align: center;
                    width: 30px;
                    height: 30px;
                    border-radius: 4px;
                    border: none;
                    padding: 5px;
                }
                .datepicker table tr td.day:hover {
                    background: #eeeeee;
                    cursor: pointer;
                }
                .datepicker table tr td.disabled,
                .datepicker table tr td.disabled:hover {
                    background: none;
                    color: #999999;
                    cursor: default;
                }
                .input-group-text {
                    cursor: pointer;
                }
                .input-group-text i {
                    color: #00bd56;
                }
            </style>
        `;

        // Add the styles to the page
        $('head').append(datepickerStyles);

        // Handle date selection
        $('.datepicker').on('changeDate', function(e) {
            validateDate(this);
            updateAvailableTimeSlots();
        });

        // Make the calendar icon trigger the datepicker
        $('.input-group-text').on('click', function() {
            $(this).closest('.input-group').find('.datepicker').datepicker('show');
        });

        function validateDate(dateInput) {
            const selectedDate = new Date(dateInput.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                showError(dateInput, "Cannot select a past date");
                return false;
            }

            if (selectedDate.getDay() === 0) {
                showError(dateInput, "We are closed on Sundays. Please select another date.");
                return false;
            }

            showSuccess(dateInput);
            return true;
        }

        // Function to update available time slots
        function updateAvailableTimeSlots() {
            const selectedDate = new Date($('#booking_date').val());
            const timeSelect = $('#booking_time');
            timeSelect.empty();
            
            timeSelect.append('<option value="">Select a time</option>');
            
            if (selectedDate && !isNaN(selectedDate.getTime())) {
                const isToday = selectedDate.toDateString() === new Date().toDateString();
                const currentHour = new Date().getHours();
                
                const timeSlots = [
                    '09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM', 
                    '11:00 AM', '11:30 AM', '12:00 PM', '12:30 PM',
                    '01:00 PM', '01:30 PM', '02:00 PM', '02:30 PM',
                    '03:00 PM', '03:30 PM', '04:00 PM', '04:30 PM',
                    '05:00 PM'
                ];

                timeSlots.forEach(slot => {
                    const hour = parseInt(slot.split(':')[0]);
                    const isPM = slot.includes('PM');
                    const hour24 = isPM ? (hour === 12 ? 12 : hour + 12) : (hour === 12 ? 0 : hour);

                    if (!isToday || hour24 > currentHour) {
                        timeSelect.append(`<option value="${slot}">${slot}</option>`);
                    }
                });
            }
        }

        // Helper functions for validation feedback
        function showError(element, message) {
            $(element).addClass('is-invalid').removeClass('is-valid');
            $(element).closest('.form-group').find('.invalid-feedback').text(message);
        }

        function showSuccess(element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
            $(element).closest('.form-group').find('.invalid-feedback').text('');
        }

        // Form submission validation
        $('#daycareForm').on('submit', function(e) {
            const dateValid = validateDate($('#booking_date')[0]);
            const timeValid = $('#booking_time').val() !== '';

            if (!dateValid || !timeValid) {
                e.preventDefault();
                if (!timeValid) {
                    showError($('#booking_time')[0], "Please select an appointment time");
                }
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
    </script>
</body>
</html> 