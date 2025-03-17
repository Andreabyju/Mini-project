<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "connect.php";

// Initialize variables
$productName = '';
$productPrice = '';
$productDescription = '';
$productCategory = '';
$error = null;
$success = null;

// Fetch existing categories
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission for adding a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $productName = $_POST['product_name'];
    $productPrice = $_POST['product_price'];
    $productDescription = $_POST['product_description'];
    $productCategory = $_POST['product_category'];
    
    try {
        // Handle image upload
        $imageName = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $imageName = uniqid() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $imageName;
            
            if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadFile)) {
                throw new Exception("Failed to upload image.");
            }
        }
        
        // Debugging: Check if category is set and not empty
        if (empty($productCategory)) {
            throw new Exception("Product category is not set.");
        }

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, image_url) VALUES (:name, :description, :price, :category, :image)");
        $stmt->execute([
            'name' => $productName,
            'description' => $productDescription,
            'price' => $productPrice,
            'category' => $productCategory,
            'image' => $imageName
        ]);
        
        $success = "Product added successfully!";
        
        // Clear form
        $productName = $productPrice = $productDescription = $productCategory = '';
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $productId = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute(['id' => $productId]);
        $success = "Product deleted successfully!";
    } catch (Exception $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Fetch existing products
$products = $conn->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - The Canine & Feline Co.</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-green-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <div>
                    <h1 class="text-xl font-bold">The Canine & Feline Co.</h1>
                    <span class="text-sm">Manage Products</span>
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
                    <a href="manage_products.php" class="block p-3 rounded-lg bg-green-100 text-green-800 font-medium">
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
            <h1 class="text-2xl font-bold mb-4">Add New Product</h1>
            
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-4 mb-4 rounded"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-4 mb-4 rounded"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="product_name" class="block text-sm font-medium text-gray-700">Product Name</label>
                        <input type="text" name="product_name" id="product_name" value="<?php echo htmlspecialchars($productName); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="product_price" class="block text-sm font-medium text-gray-700">Product Price</label>
                        <input type="number" name="product_price" id="product_price" value="<?php echo htmlspecialchars($productPrice); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                    </div>
                    <div class="mb-4">
                        <label for="product_description" class="block text-sm font-medium text-gray-700">Product Description</label>
                        <textarea name="product_description" id="product_description" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"><?php echo htmlspecialchars($productDescription); ?></textarea>
                    </div>
                    <div class="mb-4">
                        <label for="product_category" class="block text-sm font-medium text-gray-700">Product Category</label>
                        <select name="product_category" id="product_category" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                            <option value="" disabled <?php echo empty($productCategory) ? 'selected' : ''; ?>>Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['name']); ?>" <?php echo $productCategory === $category['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="product_image" class="block text-sm font-medium text-gray-700">Product Image</label>
                        <input type="file" name="product_image" id="product_image" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" accept="image/*" required>
                        <p class="mt-1 text-sm text-gray-500">Accepted formats: JPG, JPEG, PNG, GIF</p>
                    </div>
                    <button type="submit" name="add_product" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Product</button>
                </form>
            </div>

            <h2 class="text-xl font-bold mb-4">Existing Products</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="px-6 py-4">$<?php echo htmlspecialchars($product['price']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($product['category']); ?></td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-16 h-16 object-cover rounded">
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
    <form action="" method="GET" style="display:inline;">
        <input type="hidden" name="delete" value="<?php echo htmlspecialchars($product['id']); ?>">
        <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded">
            Delete
        </button>
    </form>
</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>