<?php
session_start();
require_once "connect.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">No booking ID provided</div>';
    exit;
}

$bookingId = $_GET['id'];

try {
    // Get booking details
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = :id");
    $stmt->execute(['id' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo '<div class="alert alert-danger">Booking not found</div>';
        exit;
    }
    
    // Get services for this booking
    $servicesStmt = $conn->prepare("SELECT * FROM booking_services WHERE booking_id = :booking_id");
    $servicesStmt->execute(['booking_id' => $bookingId]);
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format date
    $appointmentDate = new DateTime($booking['appointment_date']);
    $formattedDate = $appointmentDate->format('F d, Y');
    
    // Format created at date
    $createdDate = new DateTime($booking['created_at']);
    $formattedCreatedDate = $createdDate->format('F d, Y h:i A');
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-paw mr-2"></i>Pet Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Pet Name:</th>
                        <td><?php echo htmlspecialchars($booking['pet_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Pet Type:</th>
                        <td><?php echo ucfirst(htmlspecialchars($booking['pet_type'])); ?></td>
                    </tr>
                    <?php if (!empty($booking['pet_breed'])): ?>
                    <tr>
                        <th>Breed:</th>
                        <td><?php echo htmlspecialchars($booking['pet_breed']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($booking['pet_age'])): ?>
                    <tr>
                        <th>Age:</th>
                        <td><?php echo htmlspecialchars($booking['pet_age']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($booking['pet_weight'])): ?>
                    <tr>
                        <th>Weight:</th>
                        <td><?php echo htmlspecialchars($booking['pet_weight']); ?> kg</td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Owner Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Name:</th>
                        <td><?php echo htmlspecialchars($booking['owner_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($booking['owner_email']); ?></td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td><?php echo htmlspecialchars($booking['owner_phone']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Appointment Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th>Date:</th>
                        <td><?php echo $formattedDate; ?></td>
                    </tr>
                    <tr>
                        <th>Time:</th>
                        <td><?php echo htmlspecialchars($booking['appointment_time']); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php 
                                $statusClass = 'status-' . $booking['status'];
                                $statusText = ucfirst($booking['status']);
                            ?>
                            <span class="status-badge <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Booked On:</th>
                        <td><?php echo $formattedCreatedDate; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-list-alt mr-2"></i>Special Instructions</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($booking['special_instructions'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($booking['special_instructions'])); ?></p>
                <?php else: ?>
                    <p class="text-muted">No special instructions provided.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-cut mr-2"></i>Selected Services</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Service</th>
                    <th class="text-right">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="2" class="text-center py-3">No services found for this booking.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                            <td class="text-right">₹<?php echo number_format($service['service_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <th>Total</th>
                    <th class="text-right">₹<?php echo number_format($booking['total_price'], 2); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div> 