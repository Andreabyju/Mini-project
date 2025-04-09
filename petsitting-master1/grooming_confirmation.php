<?php
session_start();
require_once "connect.php";

// Initialize variables
$errorMsg = "";
$bookingDetails = null;
$serviceDetails = [];

// Check if booking ID is provided
if (isset($_GET['booking_id']) && !empty($_GET['booking_id'])) {
    $bookingId = $_GET['booking_id'];
    
    try {
        // Fetch booking details
        $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = :id");
        $stmt->execute(['id' => $bookingId]);
        $bookingDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bookingDetails) {
            // Fetch services for this booking
            $serviceStmt = $conn->prepare("SELECT * FROM booking_services WHERE booking_id = :booking_id");
            $serviceStmt->execute(['booking_id' => $bookingId]);
            $serviceDetails = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $errorMsg = "Booking not found.";
        }
    } catch (PDOException $e) {
        $errorMsg = "Error retrieving booking details: " . $e->getMessage();
    }
} else {
    $errorMsg = "No booking ID provided.";
}

// Format date for display
$formattedDate = "";
if ($bookingDetails && !empty($bookingDetails['appointment_date'])) {
    $date = new DateTime($bookingDetails['appointment_date']);
    $formattedDate = $date->format('l, F j, Y');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - The Canine & Feline Co.</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        * {
            font-family: 'Montserrat', sans-serif !important;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .confirmation-header {
            background-color: #00bd56;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .confirmation-header h1 {
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .confirmation-body {
            padding: 40px;
        }
        
        .confirmation-icon {
            font-size: 60px;
            color: #00bd56;
            margin-bottom: 20px;
        }
        
        .booking-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            width: 40%;
            font-weight: 600;
            color: #555;
        }
        
        .detail-value {
            width: 60%;
        }
        
        .services-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .services-list li {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .total-row {
            font-weight: 600;
            font-size: 1.1em;
            color: #00bd56;
            border-top: 2px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .action-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .btn-primary {
            background-color: #00bd56;
            border-color: #00bd56;
            padding: 10px 25px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #00a649;
            border-color: #00a649;
        }
        
        .btn-outline-secondary {
            color: #555;
            border-color: #ccc;
            padding: 10px 25px;
            font-weight: 500;
        }
        
        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
            color: #333;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                background-color: white;
            }
            
            .confirmation-container {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light no-print" id="ftco-navbar">
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
    
    <div class="container">
        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger mt-4"><?php echo $errorMsg; ?></div>
        <?php elseif ($bookingDetails): ?>
            <div class="confirmation-container">
                <div class="confirmation-header">
                    <h1><i class="fas fa-check-circle mr-2"></i> Booking Confirmed</h1>
                    <p class="mb-0">Your grooming appointment has been successfully booked!</p>
                </div>
                
                <div class="confirmation-body">
                    <div class="text-center">
                        <div class="confirmation-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h2>Thank You, <?php echo htmlspecialchars($bookingDetails['owner_name']); ?>!</h2>
                        <p class="lead">Your booking confirmation number is: <strong>#<?php echo $bookingDetails['id']; ?></strong></p>
                        <p>We've sent a confirmation email to <strong><?php echo htmlspecialchars($bookingDetails['owner_email']); ?></strong> with all the details.</p>
                    </div>
                    
                    <div class="booking-details">
                        <h4 class="mb-4">Appointment Details</h4>
                        
                        <div class="detail-row">
                            <div class="detail-label">Date & Time</div>
                            <div class="detail-value">
                                <?php echo $formattedDate; ?> at <?php echo htmlspecialchars($bookingDetails['appointment_time']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Pet Information</div>
                            <div class="detail-value">
                                <strong><?php echo htmlspecialchars($bookingDetails['pet_name']); ?></strong> (<?php echo ucfirst(htmlspecialchars($bookingDetails['pet_type'])); ?>)
                                <?php if (!empty($bookingDetails['pet_breed'])): ?>
                                    <br>Breed: <?php echo htmlspecialchars($bookingDetails['pet_breed']); ?>
                                <?php endif; ?>
                                <?php if (!empty($bookingDetails['pet_age'])): ?>
                                    <br>Age: <?php echo htmlspecialchars($bookingDetails['pet_age']); ?>
                                <?php endif; ?>
                                <?php if (!empty($bookingDetails['pet_weight'])): ?>
                                    <br>Weight: <?php echo htmlspecialchars($bookingDetails['pet_weight']); ?> kg
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Contact Information</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($bookingDetails['owner_name']); ?><br>
                                <?php echo htmlspecialchars($bookingDetails['owner_email']); ?><br>
                                <?php echo htmlspecialchars($bookingDetails['owner_phone']); ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Services Booked</div>
                            <div class="detail-value">
                                <ul class="services-list">
                                    <?php foreach ($serviceDetails as $service): ?>
                                        <li>
                                            <span><?php echo htmlspecialchars($service['service_name']); ?></span>
                                            <span>₹<?php echo number_format($service['service_price'], 2); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                    <li class="total-row">
                                        <span>Total</span>
                                        <span>₹<?php echo number_format($bookingDetails['total_price'], 2); ?></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <?php if (!empty($bookingDetails['special_instructions'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Special Instructions</div>
                                <div class="detail-value">
                                    <?php echo nl2br(htmlspecialchars($bookingDetails['special_instructions'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-buttons no-print">
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print mr-2"></i> Print
                        </button>
                        <a href="hhh2.php" class="btn btn-primary">
                            <i class="fas fa-home mr-2"></i> Return to Home
                        </a>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <p class="text-muted">
                            <small>
                                <i class="fas fa-info-circle mr-1"></i> 
                                If you need to cancel or reschedule your appointment, please contact us at least 24 hours in advance.
                            </small>
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-4">No booking information available.</div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5 no-print">
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
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 