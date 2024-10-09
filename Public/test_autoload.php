<?php
// Public/test_autoload.php

use Ashan\Oldinvoice\Lib\Product;

// Include Composer's autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Include the database configuration
require_once __DIR__ . '/../src/config/database.php';

// Instantiate the Product class
$productModel = new Product($pdo);

// Fetch all products
$products = $productModel->getAll();

echo '<pre>';
print_r($products);
echo '</pre>';
?>
