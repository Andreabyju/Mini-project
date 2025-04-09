<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "connect.php";

// Create services table if it doesn't exist
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            duration INT NOT NULL COMMENT 'Duration in minutes',
            animal_type ENUM('dog', 'cat', 'both') NOT NULL,
            service_type ENUM('grooming', 'spa', 'package') NOT NULL,
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    echo "Table creation failed: " . $e->getMessage();
}

// Initialize variables
$serviceName = '';
$servicePrice = '';
$serviceDuration = '';
$serviceDescription = '';
$serviceAnimalType = '';
$serviceType = '';
$isAvailable = true;
$error = null;
$success = null;
$isEditing = false;
$serviceId = null;

// Handle form submission for adding/editing a service
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_service']) || isset($_POST['update_service'])) {
        $serviceName = $_POST['service_name'];
        $servicePrice = $_POST['service_price'];
        $serviceDuration = $_POST['service_duration'];
        $serviceDescription = $_POST['service_description'];
        $serviceAnimalType = $_POST['animal_type'];
        $serviceType = $_POST['service_type'];
        $isAvailable = isset($_POST['is_available']) ? 1 : 0;
        
        try {
            if (isset($_POST['add_service'])) {
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO services (name, description, price, duration, animal_type, service_type, is_available) 
                    VALUES (:name, :description, :price, :duration, :animal_type, :service_type, :is_available)");
                $stmt->execute([
                    'name' => $serviceName,
                    'description' => $serviceDescription,
                    'price' => $servicePrice,
                    'duration' => $serviceDuration,
                    'animal_type' => $serviceAnimalType,
                    'service_type' => $serviceType,
                    'is_available' => $isAvailable
                ]);
                
                $success = "Service added successfully!";
                
                // Clear form
                $serviceName = $servicePrice = $serviceDuration = $serviceDescription = '';
                $serviceAnimalType = $serviceType = '';
                $isAvailable = true;
            } else {
                // Update existing service
                $serviceId = $_POST['service_id'];
                $stmt = $conn->prepare("UPDATE services SET name = :name, description = :description, price = :price, 
                    duration = :duration, animal_type = :animal_type, service_type = :service_type, is_available = :is_available 
                    WHERE id = :id");
                $stmt->execute([
                    'name' => $serviceName,
                    'description' => $serviceDescription,
                    'price' => $servicePrice,
                    'duration' => $serviceDuration,
                    'animal_type' => $serviceAnimalType,
                    'service_type' => $serviceType,
                    'is_available' => $isAvailable,
                    'id' => $serviceId
                ]);
                
                $success = "Service updated successfully!";
                // Redirect to prevent form resubmission
                header("Location: manage_services.php");
                exit();
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle edit service
if (isset($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM services WHERE id = :id");
        $stmt->execute(['id' => $_GET['edit']]);
        $service = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service) {
            $serviceName = $service['name'];
            $servicePrice = $service['price'];
            $serviceDuration = $service['duration'];
            $serviceDescription = $service['description'];
            $serviceAnimalType = $service['animal_type'];
            $serviceType = $service['service_type'];
            $isAvailable = $service['is_available'];
            $isEditing = true;
            $serviceId = $service['id'];
        }
    } catch (Exception $e) {
        $error = "Error loading service: " . $e->getMessage();
    }
}

// Handle service deletion
if (isset($_GET['delete'])) {
    $serviceId = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM services WHERE id = :id");
        $stmt->execute(['id' => $serviceId]);
        $success = "Service deleted successfully!";
    } catch (Exception $e) {
        $error = "Error deleting service: " . $e->getMessage();
    }
}

// Fetch all services
try {
    $stmt = $conn->query("SELECT * FROM services ORDER BY service_type, animal_type, name");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filter services by type
    $dogServices = array_filter($services, function($service) {
        return $service['animal_type'] == 'dog' || $service['animal_type'] == 'both';
    });
    
    $catServices = array_filter($services, function($service) {
        return $service['animal_type'] == 'cat' || $service['animal_type'] == 'both';
    });
    
    $groomingServices = array_filter($services, function($service) {
        return $service['service_type'] == 'grooming';
    });
    
    $spaServices = array_filter($services, function($service) {
        return $service['service_type'] == 'spa';
    });
    
} catch (PDOException $e) {
    $error = "Error fetching services: " . $e->getMessage();
    $services = $dogServices = $catServices = $groomingServices = $spaServices = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management - The Canine & Feline Co.</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-green-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <div>
                    <h1 class="text-xl font-bold">The Canine & Feline Co.</h1>
                    <span class="text-sm">Manage Services</span>
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
                    <a href="manage_categories.php" class="block p-3 rounded-lg hover:bg-gray-100 text-gray-700 font-medium">
                        Manage Categories
                    </a>
                    
                    <a href="manage_services.php" class="block p-3 rounded-lg bg-green-100 text-green-800 font-medium">
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
            <h1 class="text-2xl font-bold mb-4"><?php echo $isEditing ? 'Edit Service' : 'Add New Service'; ?></h1>
            
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-4 mb-4 rounded"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 text-green-700 p-4 mb-4 rounded"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow mb-8">
                <form method="POST">
                    <?php if ($isEditing): ?>
                        <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($serviceId); ?>">
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label for="service_name" class="block text-sm font-medium text-gray-700">Service Name</label>
                        <input type="text" name="service_name" id="service_name" value="<?php echo htmlspecialchars($serviceName); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="service_price" class="block text-sm font-medium text-gray-700">Price (₹)</label>
                            <input type="number" step="0.01" name="service_price" id="service_price" value="<?php echo htmlspecialchars($servicePrice); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                        </div>
                        
                        <div>
                            <label for="service_duration" class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
                            <input type="number" name="service_duration" id="service_duration" value="<?php echo htmlspecialchars($serviceDuration); ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                        </div>
                        
                        <div>
                            <label for="is_available" class="block text-sm font-medium text-gray-700">Status</label>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="is_available" id="is_available" class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-300 focus:ring focus:ring-green-200 focus:ring-opacity-50" <?php echo $isAvailable ? 'checked' : ''; ?>>
                                    <span class="ml-2">Available</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="animal_type" class="block text-sm font-medium text-gray-700">Animal Type</label>
                            <select name="animal_type" id="animal_type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                                <option value="" disabled <?php echo empty($serviceAnimalType) ? 'selected' : ''; ?>>Select animal type</option>
                                <option value="dog" <?php echo $serviceAnimalType === 'dog' ? 'selected' : ''; ?>>Dog</option>
                                <option value="cat" <?php echo $serviceAnimalType === 'cat' ? 'selected' : ''; ?>>Cat</option>
                                <option value="both" <?php echo $serviceAnimalType === 'both' ? 'selected' : ''; ?>>Both</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="service_type" class="block text-sm font-medium text-gray-700">Service Type</label>
                            <select name="service_type" id="service_type" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                                <option value="" disabled <?php echo empty($serviceType) ? 'selected' : ''; ?>>Select service type</option>
                                <option value="grooming" <?php echo $serviceType === 'grooming' ? 'selected' : ''; ?>>Grooming</option>
                                <option value="spa" <?php echo $serviceType === 'spa' ? 'selected' : ''; ?>>Spa</option>
                                <option value="package" <?php echo $serviceType === 'package' ? 'selected' : ''; ?>>Package</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="service_description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="service_description" id="service_description" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required><?php echo htmlspecialchars($serviceDescription); ?></textarea>
                    </div>
                    
                    <?php if ($isEditing): ?>
                        <button type="submit" name="update_service" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Update Service</button>
                        <a href="manage_services.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 ml-2">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_service" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Service</button>
                    <?php endif; ?>
                </form>
            </div>

            <h2 class="text-xl font-bold mb-4">Manage Services</h2>
            
            <!-- Tabs navigation -->
            <div class="border-b border-gray-200 mb-4">
                <ul class="flex -mb-px">
                    <li class="mr-1">
                        <a href="#" class="tab-link bg-white inline-block py-2 px-4 text-blue-600 hover:text-blue-800 font-medium" data-tab="all">All Services</a>
                    </li>
                    <li class="mr-1">
                        <a href="#" class="tab-link inline-block py-2 px-4 text-gray-600 hover:text-gray-800 font-medium" data-tab="dog">Dogs</a>
                    </li>
                    <li class="mr-1">
                        <a href="#" class="tab-link inline-block py-2 px-4 text-gray-600 hover:text-gray-800 font-medium" data-tab="cat">Cats</a>
                    </li>
                    <li class="mr-1">
                        <a href="#" class="tab-link inline-block py-2 px-4 text-gray-600 hover:text-gray-800 font-medium" data-tab="grooming">Grooming</a>
                    </li>
                    <li class="mr-1">
                        <a href="#" class="tab-link inline-block py-2 px-4 text-gray-600 hover:text-gray-800 font-medium" data-tab="spa">Spa</a>
                    </li>
                </ul>
            </div>
            
            <!-- Tab content -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- All Services Tab -->
                <div id="all-tab" class="tab-content active">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($services)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No services found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . (strlen($service['description']) > 50 ? '...' : ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['service_type'] == 'grooming'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Grooming</span>
                                            <?php elseif ($service['service_type'] == 'spa'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Spa</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Package</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['animal_type'] == 'dog'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Dog</span>
                                            <?php elseif ($service['animal_type'] == 'cat'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">Cat</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Both</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($service['duration']); ?> min</td>
                                        <td class="px-6 py-4">₹<?php echo number_format($service['price'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['is_available']): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="manage_services.php?edit=<?php echo $service['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_services.php?delete=<?php echo $service['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this service?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Dog Services Tab -->
                <div id="dog-tab" class="tab-content">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($dogServices)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No dog services found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dogServices as $service): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . (strlen($service['description']) > 50 ? '...' : ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['service_type'] == 'grooming'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Grooming</span>
                                            <?php elseif ($service['service_type'] == 'spa'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Spa</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Package</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($service['duration']); ?> min</td>
                                        <td class="px-6 py-4">₹<?php echo number_format($service['price'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['is_available']): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="manage_services.php?edit=<?php echo $service['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_services.php?delete=<?php echo $service['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this service?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Cat Services Tab -->
                <div id="cat-tab" class="tab-content">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($catServices)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No cat services found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($catServices as $service): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . (strlen($service['description']) > 50 ? '...' : ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['service_type'] == 'grooming'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Grooming</span>
                                            <?php elseif ($service['service_type'] == 'spa'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Spa</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Package</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($service['duration']); ?> min</td>
                                        <td class="px-6 py-4">₹<?php echo number_format($service['price'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['is_available']): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="manage_services.php?edit=<?php echo $service['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_services.php?delete=<?php echo $service['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this service?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Grooming Tab -->
                <div id="grooming-tab" class="tab-content">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($groomingServices)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No grooming services found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($groomingServices as $service): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . (strlen($service['description']) > 50 ? '...' : ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['animal_type'] == 'dog'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Dog</span>
                                            <?php elseif ($service['animal_type'] == 'cat'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">Cat</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Both</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($service['duration']); ?> min</td>
                                        <td class="px-6 py-4">₹<?php echo number_format($service['price'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['is_available']): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="manage_services.php?edit=<?php echo $service['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_services.php?delete=<?php echo $service['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this service?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Spa Tab -->
                <div id="spa-tab" class="tab-content">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Service Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($spaServices)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No spa services found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($spaServices as $service): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . (strlen($service['description']) > 50 ? '...' : ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['animal_type'] == 'dog'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Dog</span>
                                            <?php elseif ($service['animal_type'] == 'cat'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">Cat</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Both</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($service['duration']); ?> min</td>
                                        <td class="px-6 py-4">₹<?php echo number_format($service['price'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['is_available']): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="manage_services.php?edit=<?php echo $service['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_services.php?delete=<?php echo $service['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this service?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Package Tab -->
                <div id="package-tab" class="tab-content">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($packageServices)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No package services found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($packageServices as $service): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . (strlen($service['description']) > 50 ? '...' : ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['animal_type'] == 'dog'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Dog</span>
                                            <?php elseif ($service['animal_type'] == 'cat'): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">Cat</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Both</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($service['duration']); ?> min</td>
                                        <td class="px-6 py-4">₹<?php echo number_format($service['price'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($service['is_available']): ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="manage_services.php?edit=<?php echo $service['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_services.php?delete=<?php echo $service['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this service?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add FontAwesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <!-- Add JavaScript for tab switching -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show the first tab by default
            document.getElementById('all-tab').style.display = 'block';
            
            // Get all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            
            // Add click event to each tab button
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Hide all tabs
                    document.querySelectorAll('.tab-content').forEach(tab => {
                        tab.style.display = 'none';
                    });
                    
                    // Remove active class from all buttons
                    tabButtons.forEach(btn => {
                        btn.classList.remove('bg-green-600', 'text-white');
                        btn.classList.add('bg-gray-200', 'text-gray-700');
                    });
                    
                    // Show the selected tab
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).style.display = 'block';
                    
                    // Add active class to clicked button
                    this.classList.remove('bg-gray-200', 'text-gray-700');
                    this.classList.add('bg-green-600', 'text-white');
                });
            });
        });
    </script>
</body>
</html>