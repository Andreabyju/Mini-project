<?php
session_start();
require_once "connect.php";

header('Content-Type: application/json');

// Get search query
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'No search query provided']);
    exit;
}

try {
    // Log the search query for debugging
    error_log("Search query: " . $query);
    
    // Search in the products table
    $stmt = $conn->prepare("SELECT * FROM products WHERE 
                          name LIKE :query OR 
                          description LIKE :query OR 
                          category LIKE :query");
    
    $searchParam = "%" . $query . "%";
    $stmt->bindParam(':query', $searchParam);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the number of results found
    error_log("Search results count: " . count($results));
    
    echo json_encode([
        'success' => true,
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error searching products: ' . $e->getMessage()
    ]);
}
?> 