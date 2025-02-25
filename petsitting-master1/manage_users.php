<?php
require_once "connect.php";
// Add error reporting at the top
error_reporting(E_ALL);
ini_set('display_errors', 1);

//session_start();

// Check if user is logged in and is an admin
//if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//    header("Location: login.php");
//    exit();
//}

// Initialize variables
$users = [];
$error = null;

// Fetch users from database with modified query
try {
    $stmt = $conn->prepare("SELECT * FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - The Canine & Feline Co.</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-green-600 text-white p-4 w-full">
        <div class="container mx-auto flex justify-between items-center px-4">
            <div class="flex items-center">
                <div>
                    <h1 class="text-xl font-bold">The Canine & Feline Co.</h1>
                    <span class="text-sm">Manage Users</span>
                </div>
            </div>
            <div>
                <a href="admin_dashboard.php" class="text-white hover:text-gray-300 mr-4">Dashboard</a>
                <a href="demo.php" class="text-white hover:text-gray-300">Logout</a>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar - removed h-screen -->
        <div class="w-64 bg-white shadow-lg">
            <div class="p-4">
                <div class="space-y-2">
                    <a href="admin_dashboard.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Dashboard
                    </a>
                    <a href="manage_users.php" class="block p-3 rounded-lg bg-gray-100 text-gray-700 font-medium">
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
                    <a href="reports.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        View Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">User Management</h2>
                        <a href="add_user.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Add New User</a>
                    </div>
                    <div class="relative" style="height: calc(100vh - 250px);">
                        <?php if (empty($users)): ?>
                            <p class="text-gray-500 text-center py-4">No users found.</p>
                        <?php else: ?>
                            <div class="overflow-y-auto h-full">
                                <table class="w-full table-fixed divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="w-12 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">ID</th>
                                            <th class="w-28 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Name</th>
                                            <th class="w-24 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Username</th>
                                            <th class="w-32 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Email</th>
                                            <th class="w-24 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Phone</th>
                                            <th class="w-32 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Address</th>
                                            <th class="w-16 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Pincode</th>
                                            <th class="w-20 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Pet Name</th>
                                            <th class="w-16 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Pet Age</th>
                                            <th class="w-24 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Pet Breed</th>
                                            <th class="w-20 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Pet Type</th>
                                            <th class="w-20 px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase bg-gray-50">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['user_id']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['address']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['pincode']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['pet_name']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['pet_age']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['pet_breed']); ?></td>
                                            <td class="px-2 py-3 text-sm truncate"><?php echo htmlspecialchars($user['pet_type']); ?></td>
                                            <td class="px-2 py-3 text-sm">
                                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900 mr-2">Edit</a>
                                                <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" 
                                                   class="text-red-600 hover:text-red-900"
                                                   onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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