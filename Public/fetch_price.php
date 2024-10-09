<?php
// Public/fetch_price.php

// Enable error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Start the session if not already started
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Set response header to JSON
 */
header('Content-Type: application/json');

/**
 * Allow only GET requests
 */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

/**
 * Include necessary files
 * Adjust the path according to your directory structure
 */
require_once __DIR__ . '/../src/lib/functions.php'; // Ensure the path is correct

/**
 * Helper function to send JSON responses
 *
 * @param int    $status_code HTTP status code
 * @param bool   $success     Success flag
 * @param string $message     Message to return
 * @param array  $data        Additional data
 */
function sendJsonResponse($status_code, $success, $message, $data = []) {
    http_response_code($status_code);
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

/**
 * Validate and sanitize input
 */
$product_name = isset($_GET['product_name']) ? trim($_GET['product_name']) : '';

if (empty($product_name)) {
    sendJsonResponse(400, false, 'Product name is required.');
}

/**
 * Optionally, you can sanitize the product name further
 * For example, remove any unwanted characters
 */
$product_name = filter_var($product_name, FILTER_SANITIZE_STRING);

/**
 * Fetch product details by product name
 */
$product = getProductByName($product_name);

if ($product) {
    // Ensure the product has necessary fields
    if (isset($product['product_number']) && isset($product['price'])) {
        sendJsonResponse(200, true, 'Product found.', [
            'product_number' => $product['product_number'],
            'price' => (float)$product['price']
        ]);
    } else {
        // Incomplete product data
        sendJsonResponse(500, false, 'Product data is incomplete.');
    }
} else {
    sendJsonResponse(404, false, 'Product not found.');
}
?>
