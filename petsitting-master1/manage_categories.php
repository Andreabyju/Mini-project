<?php
require_once "connect.php";

// Initialize variables
$categoryName = '';
$categoryDescription = '';
$error = null;
$success = null;

// Handle form submission for adding a category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $categoryName = $_POST['category_name'];
    $categoryDescription = $_POST['category_description'];

    if (empty($categoryName)) {
        $error = "Category name is required.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
            $stmt->bindParam(':name', $categoryName);
            $stmt->bindParam(':description', $categoryDescription);
            $stmt->execute();
            $success = "Category added successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle category deletion
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "Category deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting category: " . $e->getMessage();
    }
}

// Fetch existing categories
try {
    $categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching categories: " . $e->getMessage();
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - The Canine & Feline Co.</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-green-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <div>
                    <h1 class="text-xl font-bold">The Canine & Feline Co.</h1>
                    <span class="text-sm">Manage Categories </span>
                </div>
            </div>
            <div>
            <a href="admin_dashboard.php" class="text-white hover:text-gray-300 mr-4">Dashboard</a>
                <a href="demo.php" class="text-white hover:text-gray-300">Logout</a>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white h-screen shadow-lg">
            <div class="p-4">
                <div class="space-y-2">
                    <a href="admin_dashboard.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Dashboard
                    </a>
                    <a href="manage_users.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Manage Users
                    </a>
                    <a href="manage_products.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Manage Products
                    </a>
                    <a href="manage_categories.php" class="block p-3 rounded-lg bg-green-100 text-green-800 font-medium">
                        Manage Categories
                    </a>
                   
                    <a href="manage_services.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Manage Services
                    </a>
                    <a href="manage_grooming.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Grooming Appointments
                    </a>
                    
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h1 class="text-2xl font-bold mb-4">Manage Categories</h1>

            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-4 mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-4 mb-4"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="bg-white p-6 rounded-lg shadow">
                <div class="mb-4">
                    <label for="category_name" class="block text-sm font-medium text-gray-700">Category Name</label>
                    <input type="text" name="category_name" id="category_name" value="<?php echo htmlspecialchars($categoryName); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div class="mb-4">
                    <label for="category_description" class="block text-sm font-medium text-gray-700">Category Description</label>
                    <textarea name="category_description" id="category_description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"><?php echo htmlspecialchars($categoryDescription); ?></textarea>
                </div>
                <button type="submit" name="add_category" class="bg-green-600 text-white px-4 py-2 rounded">Add Category</button>
            </form>

            <h2 class="text-xl font-bold mt-8">Existing Categories</h2>
            <ul class="mt-4">
                <?php foreach ($categories as $category): ?>
                    <li class="flex justify-between items-center mb-2 bg-white p-4 rounded shadow">
                        <div>
                            <span class="font-medium"><?php echo htmlspecialchars($category['name']); ?></span>
                            <?php if (!empty($category['description'])): ?>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($category['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <a href="?delete=<?php echo $category['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>