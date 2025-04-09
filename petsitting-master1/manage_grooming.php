<?php
// Add error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();
require_once "connect.php";

// Temporarily comment out the session check for testing
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
//     header("Location: login.php");
//     exit;
// }

// Initialize variables
$errorMsg = "";
$successMsg = "";

// Handle status updates or cancellations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $bookingId = $_POST['booking_id'];
        $newStatus = $_POST['status'];
        
        try {
            $stmt = $conn->prepare("UPDATE bookings SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $bookingId]);
            $successMsg = "Appointment status updated successfully.";
        } catch (PDOException $e) {
            $errorMsg = "Error updating status: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_booking'])) {
        $bookingId = $_POST['booking_id'];
        
        try {
            // First delete from booking_services (junction table entries)
            $stmt = $conn->prepare("DELETE FROM booking_services WHERE booking_id = :id");
            $stmt->execute(['id' => $bookingId]);
            
            // Then delete the booking
            $stmt = $conn->prepare("DELETE FROM bookings WHERE id = :id");
            $stmt->execute(['id' => $bookingId]);
            
            $successMsg = "Appointment deleted successfully.";
        } catch (PDOException $e) {
            $errorMsg = "Error deleting appointment: " . $e->getMessage();
        }
    }
}

// Debug connection
if (!isset($conn) || $conn === null) {
    echo "<div class='alert alert-danger'>Database connection failed. Check your connect.php file.</div>";
}

// Fetch all bookings with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Build the query based on search/filter criteria
$whereClause = "";
$params = [];

// Add search functionality
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $whereClause .= " WHERE (pet_name LIKE :search OR owner_name LIKE :search OR owner_email LIKE :search OR owner_phone LIKE :search)";
    $params['search'] = "%$search%";
}

// Add filter by status
if (isset($_GET['status_filter']) && !empty($_GET['status_filter']) && $_GET['status_filter'] !== 'all') {
    $statusFilter = $_GET['status_filter'];
    if (empty($whereClause)) {
        $whereClause .= " WHERE status = :status";
    } else {
        $whereClause .= " AND status = :status";
    }
    $params['status'] = $statusFilter;
}

// Add filter by date
if (isset($_GET['date_filter']) && !empty($_GET['date_filter'])) {
    $dateFilter = $_GET['date_filter'];
    if (empty($whereClause)) {
        $whereClause .= " WHERE appointment_date = :date";
    } else {
        $whereClause .= " AND appointment_date = :date";
    }
    $params['date'] = $dateFilter;
}

// Count total records for pagination
try {
    $countQuery = "SELECT COUNT(*) FROM bookings" . $whereClause;
    $stmt = $conn->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);
} catch (PDOException $e) {
    $errorMsg = "Error counting records: " . $e->getMessage();
    $totalRecords = 0;
    $totalPages = 0;
}

// Fetch bookings with limit and offset for pagination
try {
    $query = "SELECT * FROM bookings" . $whereClause . " ORDER BY appointment_date DESC, appointment_time DESC LIMIT :offset, :limit";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMsg = "Error fetching bookings: " . $e->getMessage();
    $bookings = [];
}

// Count appointments by status for dashboard cards
try {
    $pendingStmt = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $confirmedStmt = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'");
    $completedStmt = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'");
    $cancelledStmt = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'");
    $todayStmt = $conn->query("SELECT COUNT(*) FROM bookings WHERE appointment_date = CURDATE()");
    
    $pendingCount = $pendingStmt->fetchColumn();
    $confirmedCount = $confirmedStmt->fetchColumn();
    $completedCount = $completedStmt->fetchColumn();
    $cancelledCount = $cancelledStmt->fetchColumn();
    $todayCount = $todayStmt->fetchColumn();
} catch (PDOException $e) {
    // Silently fail, display 0
    $pendingCount = $confirmedCount = $completedCount = $cancelledCount = $todayCount = 0;
    $errorMsg .= " Error counting status: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grooming Appointments - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <style>
        /* Override all font families with !important */
        * {
            font-family: 'Montserrat', sans-serif !important;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }
        
        /* Header styles */
        header {
            background-color: #00a07a;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            display: flex;
            flex-direction: column;
        }
        
        .header-left h1 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .header-left h2 {
            font-size: 16px;
            font-weight: 400;
            margin: 0;
        }
        
        .header-right a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-size: 15px;
        }
        
        /* Main content layout */
        .main-container {
            display: flex;
            flex: 1;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color: white;
            padding: 20px 0;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
        }
        
        .sidebar-nav li a {
            display: block;
            padding: 12px 30px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
        }
        
        .sidebar-nav li a:hover {
            background-color: #f9f9f9;
        }
        
        .sidebar-nav li.active a {
            background-color: #e0f7e9;
            color: #00a07a;
        }
        
        /* Content area */
        .content {
            flex: 1;
            padding: 30px;
        }
        
        .content h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            color: #333;
        }
        
        /* Add new service form */
        .form-card {
            background: white;
            border-radius: 5px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
        }
        
        .form-control:focus {
            border-color: #00a07a;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 160, 122, 0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: flex;
            margin: 0 -10px;
        }
        
        .form-col {
            padding: 0 10px;
            flex: 1;
        }
        
        .btn-primary {
            background-color: #00a07a;
            border: none;
            padding: 10px 20px;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #008c6a;
        }
        
        /* Bookings table */
        .table-responsive {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 2px solid #f0f0f0;
            font-weight: 600;
            color: #333;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #ffeecf;
            color: #e29c00;
        }
        
        .status-confirmed {
            background-color: #dcf5ff;
            color: #0083b0;
        }
        
        .status-completed {
            background-color: #e0f7e9;
            color: #00a07a;
        }
        
        .status-cancelled {
            background-color: #ffe0e0;
            color: #d30000;
        }
        
        /* Checkboxes */
        .checkbox-container {
            display: flex;
            align-items: center;
        }
        
        .custom-checkbox {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }
        
        /* Form action buttons */
        .form-actions {
            margin-top: 15px;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination .page-item .page-link {
            color: #00a07a;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #00a07a;
            border-color: #00a07a;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-left">
            <h1>The Canine & Feline Co.</h1>
            <h2>Manage Grooming</h2>
        </div>
        <div class="header-right">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </header>
    
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="manage_products.php">Manage Products</a></li>
                <li><a href="manage_categories.php">Manage Categories</a></li>
                <li class="active"><a href="manage_grooming.php">Manage Grooming</a></li>
                <li><a href="manage_services.php">Manage Services</a></li>
                
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="content">
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success"><?php echo $successMsg; ?></div>
            <?php endif; ?>
            
            <h2>Add New Grooming Appointment</h2>
            
            <div class="form-card">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">Pet Name</label>
                                <input type="text" class="form-control" name="pet_name" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">Owner Name</label>
                                <input type="text" class="form-control" name="owner_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">Appointment Date</label>
                                <input type="date" class="form-control" name="appointment_date" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">Appointment Time</label>
                                <select class="form-control" name="appointment_time" required>
                                    <option value="">Select time</option>
                                    <?php
                                    $times = ['09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM'];
                                    foreach ($times as $time):
                                    ?>
                                        <option value="<?php echo $time; ?>"><?php echo $time; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">Animal Type</label>
                                <select class="form-control" name="pet_type" required>
                                    <option value="">Select animal type</option>
                                    <option value="dog">Dog</option>
                                    <option value="cat">Cat</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label class="form-label">Service Type</label>
                                <select class="form-control" name="service_type" required>
                                    <option value="">Select service type</option>
                                    <option value="basic">Basic Grooming</option>
                                    <option value="premium">Premium Grooming</option>
                                    <option value="spa">Spa Treatment</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-container">
                            <input type="checkbox" class="custom-checkbox" id="status" name="status" checked>
                            <label for="status">Available</label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" name="add_appointment">Add Appointment</button>
                    </div>
                </form>
            </div>
            
            <h2>Manage Grooming</h2>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pet Details</th>
                            <th>Owner</th>
                            <th>Appointment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No appointments found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['pet_name']); ?></strong><br>
                                        <small><?php echo ucfirst(htmlspecialchars($booking['pet_type'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['owner_name']); ?></td>
                                    <td>
                                        <?php 
                                            $date = new DateTime($booking['appointment_date']);
                                            echo $date->format('M d, Y'); 
                                        ?><br>
                                        <small><?php echo htmlspecialchars($booking['appointment_time']); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            $statusClass = 'status-' . $booking['status'];
                                            $statusText = ucfirst($booking['status']);
                                        ?>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info view-details-btn" 
                                                data-id="<?php echo $booking['id']; ?>"
                                                data-toggle="modal" data-target="#viewDetailsModal">
                                            View
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary update-status-btn"
                                                data-id="<?php echo $booking['id']; ?>"
                                                data-current-status="<?php echo $booking['status']; ?>"
                                                data-toggle="modal" data-target="#updateStatusModal">
                                            Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                data-id="<?php echo $booking['id']; ?>"
                                                data-toggle="modal" data-target="#deleteModal">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status_filter']) ? '&status_filter=' . urlencode($_GET['status_filter']) : ''; ?><?php echo isset($_GET['date_filter']) ? '&date_filter=' . urlencode($_GET['date_filter']) : ''; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status_filter']) ? '&status_filter=' . urlencode($_GET['status_filter']) : ''; ?><?php echo isset($_GET['date_filter']) ? '&date_filter=' . urlencode($_GET['date_filter']) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status_filter']) ? '&status_filter=' . urlencode($_GET['status_filter']) : ''; ?><?php echo isset($_GET['date_filter']) ? '&date_filter=' . urlencode($_GET['date_filter']) : ''; ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="bookingDetails">
                    <!-- Details will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Appointment Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="updateStatusBookingId">
                        <div class="form-group">
                            <label class="form-label">New Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this appointment? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="booking_id" id="deleteBookingId">
                        <button type="submit" name="delete_booking" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // View Details Modal
            $('.view-details-btn').click(function() {
                var bookingId = $(this).data('id');
                $('#bookingDetails').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
                
                // AJAX call to fetch booking details
                $.ajax({
                    url: 'get_booking_details.php',
                    type: 'GET',
                    data: {id: bookingId},
                    success: function(response) {
                        $('#bookingDetails').html(response);
                    },
                    error: function() {
                        $('#bookingDetails').html('<div class="alert alert-danger">Error loading booking details.</div>');
                    }
                });
            });
            
            // Update Status Modal
            $('.update-status-btn').click(function() {
                var bookingId = $(this).data('id');
                var currentStatus = $(this).data('current-status');
                $('#updateStatusBookingId').val(bookingId);
                $('#status').val(currentStatus);
            });
            
            // Delete Modal
            $('.delete-btn').click(function() {
                var bookingId = $(this).data('id');
                $('#deleteBookingId').val(bookingId);
            });
        });
    </script>
</body>
</html> 