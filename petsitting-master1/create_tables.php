<?php
require_once 'connect.php';

// Create Users table
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    pet_name VARCHAR(50) NOT NULL,
    pet_age INT NOT NULL,
    pet_breed VARCHAR(50) NOT NULL,
    pet_type ENUM('dog', 'cat', 'bird', 'other') NOT NULL
)";

// Drop Order_Items table if it exists
$sql_order_items = "DROP TABLE IF EXISTS order_items;";

// Drop Products table if it exists
$sql_products = "DROP TABLE IF EXISTS products;";

// Create Products table with image column
$sql_products .= "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Rest of the tables remain the same
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

$sql_order_items .= "CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price_per_unit DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

$sql_categories = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Create Payments table
$sql_payments = "CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    razorpay_payment_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
)";

// Add the ALTER TABLE statement after the table creation
$sql_alter_payments = "ALTER TABLE payments CHANGE payment_amount price DECIMAL(10,2) NOT NULL";

// Execute each table creation query and check for success
$tables = array(
    'Users' => $sql_users,
    'Products' => $sql_products,
    'Orders' => $sql_orders,
    'Order Items' => $sql_order_items,
    'Categories' => $sql_categories,
    'Payments' => $sql_payments,
    'Alter Payments' => $sql_alter_payments
);

foreach($tables as $table_name => $sql) {
    try {
        $result = $conn->exec($sql);
        if ($result !== false) {
            echo "✅ " . $table_name . " table created successfully<br>";
        } else {
            $error = $conn->errorInfo();
            echo "❌ Error creating " . $table_name . " table: " . $error[2] . "<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Error creating " . $table_name . " table: " . $e->getMessage() . "<br>";
    }
}

// Check database connection status
$connStatus = $conn->errorInfo();
if ($connStatus[0] === "00000") {
    echo "✅ Database connection is healthy";
} else {
    echo "❌ Database connection error: " . $connStatus[2];
}
?>