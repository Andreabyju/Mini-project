<?php
require_once "connect.php";
//session_start();

// Check if user is logged in and is an admin
//if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  //  header("Location: login.php");
    //exit();
//}

// Initialize default values
$totalUsers = 0;
$totalBookings = 0;
$totalRevenue = 0;
$recentBookings = [];
$error = null;

// Fetch statistics
try {
    // Total users
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE role != 'admin'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalUsers = $result['total_users'];

    // Total bookings
    $stmt = $conn->prepare("SELECT COUNT(*) as total_bookings FROM bookings");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalBookings = $result['total_bookings'];

    // Calculate total revenue
    $stmt = $conn->prepare("SELECT COALESCE(SUM(price), 0) as total_revenue FROM bookings");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalRevenue = $result['total_revenue'];

    // Recent bookings
    $stmt = $conn->prepare("SELECT b.*, u.username 
                         FROM bookings b 
                         JOIN users u ON b.user_id = u.user_id 
                         ORDER BY b.booking_date DESC 
                         LIMIT 5");
    $stmt->execute();
    $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Initialize $recentBookings if query failed
if (!isset($recentBookings)) {
    $recentBookings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - The Canine & Feline Co.</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-green-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <div>
                    <h1 class="text-xl font-bold">The Canine & Feline Co.</h1>
                    <span class="text-sm">Admin Dashboard</span>
                </div>
            </div>
            <div>
                <?php if (isset($_SESSION) && isset($_SESSION['username'])): ?>
                    <span class="mr-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <?php else: ?>
                    <span class="mr-4">Welcome, Admin</span>
                <?php endif; ?>
                <a href="demo.php" class="text-white hover:text-gray-300">Logout</a>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white h-screen shadow-lg">
            <div class="p-4">
                <div class="space-y-2">
                    <a href="admin_dashboard.php" class="block p-3 rounded-lg bg-green-100 text-green-800 font-medium">
                        Dashboard
                    </a>
                    <a href="manage_users.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Manage Users
                    </a>
                    <a href="manage_products.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Manage Products
                    </a>
                    <a href="manage_categories.php" class="block p-3 rounded-lg bg-gray-100 text-gray-700 font-medium">
                        Manage Categories
                    </a>
                    <a href="manage_bookings.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Manage Bookings
                    </a>
                    <a href="manage_services.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Manage Services
                    </a>
                    <a href="manage_grooming.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Grooming Appointments
                    </a>
                    <a href="reports.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        View Reports
                    </a>
                    
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total Users</h3>
                    <p class="text-3xl font-bold"><?php echo $totalUsers; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total Bookings</h3>
                    <p class="text-3xl font-bold"><?php echo $totalBookings; ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Revenue</h3>
                    <p class="text-3xl font-bold">$<?php echo number_format($totalRevenue, 2); ?></p>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">Recent Bookings</h2>
                    <div class="overflow-x-auto">
                        <?php if (empty($recentBookings)): ?>
                            <p class="text-gray-500 text-center py-4">No recent bookings found.</p>
                        <?php else: ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td class="px-6 py-4">#<?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($booking['username']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-sm rounded-full 
                                                <?php echo $booking['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                                ($booking['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">View</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <script>
            alert('<?php echo $error; ?>');
        </script>
    <?php endif; ?>
</body>
</html> 