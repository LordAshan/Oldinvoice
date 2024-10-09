<?php
// src/lib/functions.php

/**
 * Enable error reporting for development (remove or comment out in production)
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Database Configuration
 */
$host = 'localhost';
$db   = 'subplanet_invoice';
$user = 'root';
$pass = ''; // Replace with your database password if any
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions for errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation of prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options); // Establish PDO connection
} catch (PDOException $e) {
    error_log("Database Connection Failed: " . $e->getMessage());
    exit("Database connection failed.");
}

/**
 * Define Constants
 */
define('LOGO_PATH', __DIR__ . '/../../Public/images/logo.png'); // Adjust the path as necessary

/**
 * Autoload Composer Dependencies
 */
require_once __DIR__ . '/../../vendor/autoload.php'; // Correct path to Composer's autoloader

/**
 * Fetches a product by its name.
 *
 * @param string $product_name The name of the product.
 * @return array|false The product data or false if not found.
 */
function getProductByName($product_name) {
    global $pdo;
    try {
        $sql = "SELECT * FROM products WHERE name = :name LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':name' => $product_name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching product by name: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches a product by its product number.
 *
 * @param string $product_number The product number.
 * @return array|false The product data or false if not found.
 */
function getProductByNumber($product_number) {
    global $pdo;
    try {
        $sql = "SELECT * FROM products WHERE product_number = :product_number LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':product_number' => $product_number]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching product by number: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches all products from the database.
 *
 * @return array An array of products.
 */
function getAllProducts() {
    global $pdo;
    try {
        $sql = "SELECT * FROM products ORDER BY name ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching products: " . $e->getMessage());
        return [];
    }
}

/**
 * Adds a new product to the database.
 *
 * @param string $product_number The unique product number.
 * @param string $name           The name of the product.
 * @param float  $price          The price of the product.
 *
 * @return bool True on success, false on failure.
 */
function addProduct($product_number, $name, $price) {
    global $pdo;
    try {
        $sql = "INSERT INTO products (product_number, name, price) VALUES (:product_number, :name, :price)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':product_number' => $product_number,
            ':name' => $name,
            ':price' => $price
        ]);
    } catch (PDOException $e) {
        error_log("Error adding product: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing product in the database.
 *
 * @param int    $product_id     The ID of the product to update.
 * @param string $product_number The new product number.
 * @param string $name           The new name of the product.
 * @param float  $price          The new price of the product.
 *
 * @return bool True on success, false on failure.
 */
function updateProduct($product_id, $product_number, $name, $price) {
    global $pdo;
    try {
        $sql = "UPDATE products SET product_number = :product_number, name = :name, price = :price WHERE id = :product_id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':product_number' => $product_number,
            ':name' => $name,
            ':price' => $price,
            ':product_id' => $product_id
        ]);
    } catch (PDOException $e) {
        error_log("Error updating product: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a product from the database.
 *
 * @param int $product_id The ID of the product to delete.
 *
 * @return bool True on success, false on failure.
 */
function deleteProduct($product_id) {
    global $pdo;
    try {
        $sql = "DELETE FROM products WHERE id = :product_id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':product_id' => $product_id]);
    } catch (PDOException $e) {
        error_log("Error deleting product: " . $e->getMessage());
        return false;
    }
}

/**
 * Generates a unique product number with the format: PXXXX
 * where XXXX is a zero-padded sequential number.
 *
 * @return string|null The generated product number or null on failure.
 */
function generateProductNumber() {
    global $pdo;
    
    $prefix = 'P';
    try {
        // Fetch the latest product number
        $sql = "SELECT product_number FROM products ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && preg_match('/^P(\d{4})$/', $result['product_number'], $matches)) {
            $lastNumber = intval($matches[1]);
            $newNumber = $lastNumber + 1;
        } else {
            // If no products exist or format is incorrect, start from 1
            $newNumber = 1;
        }
        
        // Format the sequential number with leading zeros (e.g., 0001)
        $sequence = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $sequence; // Example: P0001
    } catch (PDOException $e) {
        error_log("Error generating product number: " . $e->getMessage());
        return null;
    }
}

/**
 * Generates a unique order number with the format: OXXXX
 * where XXXX is a zero-padded sequential number.
 *
 * @return string|null The generated order number or null on failure.
 */
function generateOrderNumber() {
    global $pdo;
    
    $prefix = 'O';
    try {
        // Fetch the latest order number
        $sql = "SELECT order_number FROM invoices ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && preg_match('/^O(\d{4})$/', $result['order_number'], $matches)) {
            $lastNumber = intval($matches[1]);
            $newNumber = $lastNumber + 1;
        } else {
            // If no invoices exist or format is incorrect, start from 1
            $newNumber = 1;
        }
        
        // Format the sequential number with leading zeros (e.g., 0001)
        $sequence = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $sequence; // Example: O0001
    } catch (PDOException $e) {
        error_log("Error generating order number: " . $e->getMessage());
        return null;
    }
}

/**
 * Calculates the expire date based on the purchase date and duration.
 *
 * @param string $purchase_date    The purchase date in 'Y-m-d' format.
 * @param string $expire_duration  The duration selected (e.g., '1_month').
 *
 * @return string|null The calculated expire date in 'Y-m-d' format or null on failure.
 */
function calculateExpireDate($purchase_date, $expire_duration) {
    try {
        $purchaseDate = new DateTime($purchase_date);
        
        switch ($expire_duration) {
            case '1_month':
                $purchaseDate->modify('+1 month');
                break;
            case '2_months':
                $purchaseDate->modify('+2 months');
                break;
            case '3_months':
                $purchaseDate->modify('+3 months');
                break;
            case '6_months':
                $purchaseDate->modify('+6 months');
                break;
            case '1_year':
                $purchaseDate->modify('+1 year');
                break;
            case '2_years':
                $purchaseDate->modify('+2 years');
                break;
            case '3_years':
                $purchaseDate->modify('+3 years');
                break;
            case '5_years':
                $purchaseDate->modify('+5 years');
                break;
            default:
                return null;
        }
        
        return $purchaseDate->format('Y-m-d');
    } catch (Exception $e) {
        error_log("Error calculating expire date: " . $e->getMessage());
        return null;
    }
}

/**
 * Inserts a new invoice into the database.
 *
 * @param PDO    $pdo            The PDO database connection object.
 * @param string $order_number   The unique order number.
 * @param string $purchase_date  The purchase date.
 * @param string $expire_date    The expire date.
 * @param string $client_name    The client's name.
 * @param string $customer_phone The customer's phone number.
 * @param string $payment_status The payment status.
 *
 * @return int|false The ID of the inserted invoice or false on failure.
 */
function insertInvoice($pdo, $order_number, $purchase_date, $expire_date, $client_name, $customer_phone, $payment_status) {
    try {
        $sql = "INSERT INTO invoices (order_number, purchase_date, expire_date, client_name, customer_phone, payment_status) 
                VALUES (:order_number, :purchase_date, :expire_date, :client_name, :customer_phone, :payment_status)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':order_number' => $order_number,
            ':purchase_date' => $purchase_date,
            ':expire_date' => $expire_date,
            ':client_name' => $client_name,
            ':customer_phone' => $customer_phone,
            ':payment_status' => $payment_status
        ]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error inserting invoice: " . $e->getMessage());
        return false;
    }
}

/**
 * Inserts an invoice item into the database.
 *
 * @param PDO         $pdo           The PDO database connection object.
 * @param int         $invoice_id    The ID of the invoice.
 * @param int         $product_id    The ID of the product.
 * @param int         $quantity      The quantity of the product.
 * @param float       $price         The price of the product.
 * @param string|null $upgrader_key  The upgrader key for specific products.
 *
 * @return bool True on success, false on failure.
 */
function insertInvoiceItem($pdo, $invoice_id, $product_id, $quantity, $price, $upgrader_key = null) {
    try {
        $sql = "INSERT INTO invoice_items (invoice_id, product_id, quantity, price, upgrader_key) 
                VALUES (:invoice_id, :product_id, :quantity, :price, :upgrader_key)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':invoice_id' => $invoice_id,
            ':product_id' => $product_id,
            ':quantity' => $quantity,
            ':price' => $price,
            ':upgrader_key' => $upgrader_key
        ]);
    } catch (PDOException $e) {
        error_log("Error inserting invoice item: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates the invoice totals in the database.
 *
 * @param PDO    $pdo        The PDO database connection object.
 * @param int    $invoice_id The ID of the invoice.
 * @param float  $subtotal   The subtotal amount.
 * @param float  $discount   The discount percentage.
 * @param float  $total      The total amount after discount.
 *
 * @return bool True on success, false on failure.
 */
function updateInvoiceTotals($pdo, $invoice_id, $subtotal, $discount, $total) {
    try {
        $sql = "UPDATE invoices SET subtotal = :subtotal, discount = :discount, total = :total WHERE id = :invoice_id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':subtotal' => $subtotal,
            ':discount' => $discount,
            ':total' => $total,
            ':invoice_id' => $invoice_id
        ]);
    } catch (PDOException $e) {
        error_log("Error updating invoice totals: " . $e->getMessage());
        return false;
    }
}

/**
 * Handles the creation of an invoice upon form submission.
 *
 * @return void
 */
function createInvoice() {
    global $pdo; // Use the global $pdo object

    $errors = [];

    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token.";
    }

    // Retrieve and sanitize form inputs
    $client_name = trim($_POST['client_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $payment_status = $_POST['payment_status'] ?? '';
    $purchase_date = $_POST['purchase_date'] ?? ''; // Provided by the form
    $expire_duration = $_POST['expire_duration'] ?? ''; // Selected duration
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;

    // Validate inputs
    if (empty($client_name) || empty($customer_phone) || empty($purchase_date) || empty($expire_duration)) {
        $errors[] = "Please fill in all required fields.";
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        return;
    }

    // Generate order number
    $order_number = generateOrderNumber();
    if (!$order_number) {
        $_SESSION['errors'][] = "Failed to generate order number. Please try again.";
        return;
    }

    // Calculate expire_date
    $expire_date = calculateExpireDate($purchase_date, $expire_duration);
    if (!$expire_date) {
        $_SESSION['errors'][] = "Failed to calculate expire date. Please try again.";
        return;
    }

    // Insert invoice into the database
    $invoice_id = insertInvoice($pdo, $order_number, $purchase_date, $expire_date, $client_name, $customer_phone, $payment_status);
    if (!$invoice_id) {
        $_SESSION['errors'][] = "Error creating invoice. Please try again.";
        return;
    }

    // Insert invoice items and calculate subtotal, discount, total
    $product_names = $_POST['product_name'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $upgrader_keys = $_POST['upgrader_key'] ?? []; // Capture upgrader_key array
    $subtotal = 0;
    $items = [];

    foreach ($product_names as $index => $product_name) {
        $quantity = intval($quantities[$index] ?? 0);
        if ($quantity <= 0) continue; // Skip invalid quantities

        $product = getProductByName($product_name);

        if ($product) {
            $price = floatval($product['price']);
            $total_price = $price * $quantity;
            $subtotal += $total_price;

            // Get the upgrader_key for this product if it exists
            $upgrader_key = isset($upgrader_keys[$index]) ? trim($upgrader_keys[$index]) : null;

            // Ensure upgrader_key is only added for specific products, e.g., Spotify
            if ($product['product_number'] !== 'P0001') {
                $upgrader_key = null; // Only allow upgrader_key for Spotify
            }

            $items[] = [
                'product_number' => $product['product_number'],
                'name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
                'upgrader_key' => $upgrader_key // Include the upgrader_key
            ];

            if (!insertInvoiceItem($pdo, $invoice_id, $product['id'], $quantity, $price, $upgrader_key)) {
                $_SESSION['errors'][] = "Error adding product: " . htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') . ". Please try again.";
                return;
            }
        } else {
            $_SESSION['errors'][] = "Product '" . htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8') . "' not found.";
            return;
        }
    }

    if (empty($items)) {
        $_SESSION['errors'][] = "No valid products selected.";
        return;
    }

    // Calculate total after discount
    $total = $subtotal - ($subtotal * ($discount / 100));

    // Update invoice totals
    if (!updateInvoiceTotals($pdo, $invoice_id, $subtotal, $discount, $total)) {
        $_SESSION['errors'][] = "Error updating invoice totals. Please try again.";
        return;
    }

    // Generate PDF
    generateInvoicePDF([
        'order_number' => $order_number,
        'client_name' => $client_name,
        'customer_phone' => $customer_phone,
        'purchase_date' => $purchase_date,
        'expire_date' => $expire_date,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total' => $total,
        'payment_status' => $payment_status
    ], $items);

    // Do not output anything else after PDF generation
    exit;
}

/**
 * Generates a PDF for the invoice.
 *
 * @param array $invoiceDetails Array containing invoice details.
 * @param array $items          Array of items to be included in the invoice.
 *
 * @return void
 */
function generateInvoicePDF($invoiceDetails, $items) {
    try {
        // Check if headers have already been sent
        if (headers_sent($file, $line)) {
            error_log("Headers already sent in $file on line $line");
            exit("Cannot generate PDF: Headers already sent.");
        }

        // Create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Subplanet Invoice System');
        $pdf->SetAuthor('Subplanet');
        $pdf->SetTitle('Invoice ' . $invoiceDetails['order_number']);
        $pdf->SetSubject('Invoice');
        $pdf->SetKeywords('Invoice, PDF');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15, true);

        // Add a page
        $pdf->AddPage();

        // Add the background image
        $bgImagePath = 'C:/xampp/htdocs/oldinvoice/Public/images/background.jpg'; // Ensure this path is correct
        if (file_exists($bgImagePath)) {
            // Remove margins to allow the background to cover the whole page
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->SetMargins(0, 0, 0, true);

            // Add the background image
            $pdf->Image($bgImagePath, 0, 0, $pdf->getPageWidth(), $pdf->getPageHeight(), '', '', '', false, 300, '', false, false, 0, 'F', false, false);
            
            // Reset margins if needed for the rest of the document
            $pdf->SetMargins(15, 15, 15, true);
            $pdf->SetAutoPageBreak(true, 0);
        } else {
            error_log("Background image not found at: " . $bgImagePath);
        }

        // Generate the HTML content
        $html = '
        <h1>Invoice</h1>
        <table cellpadding="5">
            <tr>
                <td><img src="' . LOGO_PATH . '" width="100"></td>
                <td>
                    <h2>SUBSCRIPTION PLANET</h2>
                    Phone: 075 696 4895<br>
                    Email: subscriptionplanet@gmail.com
                </td>
            </tr>
        </table>
        <hr>
        <table cellpadding="5">
            <tr>
                <td><strong>Order Number:</strong> ' . htmlspecialchars($invoiceDetails['order_number'], ENT_QUOTES, 'UTF-8') . '</td>
                <td><strong>Payment Status:</strong> ' . htmlspecialchars($invoiceDetails['payment_status'], ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td><strong>Client Name:</strong> ' . htmlspecialchars($invoiceDetails['client_name'], ENT_QUOTES, 'UTF-8') . '</td>
                <td><strong>Customer Phone:</strong> ' . htmlspecialchars($invoiceDetails['customer_phone'], ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td><strong>Purchase Date:</strong> ' . htmlspecialchars($invoiceDetails['purchase_date'], ENT_QUOTES, 'UTF-8') . '</td>
                <td><strong>Expire Date:</strong> ' . htmlspecialchars($invoiceDetails['expire_date'], ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>
        <hr>
        <h3>Items</h3>
        <table border="1" cellpadding="5">
            <thead>
                <tr>
                    <th><strong>Product Number</strong></th>
                    <th><strong>Product Name</strong></th>
                    <th><strong>Quantity</strong></th>
                    <th><strong>Price</strong></th>
                    <th><strong>Total</strong></th>
                </tr>
            </thead>
            <tbody>';

        // Add items to the table
        foreach ($items as $item) {
            $itemTotal = $item['price'] * $item['quantity'];
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($item['product_number'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($item['quantity'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td>LKR ' . number_format($item['price'], 2) . '</td>
                    <td>LKR ' . number_format($itemTotal, 2) . '</td>
                </tr>';

            // Check if the item has an upgrader key and include it
            if (!empty($item['upgrader_key'])) {
                $html .= '
                <tr>
                    <td colspan="5"><strong>Upgrader Key:</strong> ' . htmlspecialchars($item['upgrader_key'], ENT_QUOTES, 'UTF-8') . '</td>
                </tr>';
            }
        }

        $html .= '
            </tbody>
        </table>';

        // Add summary section
        $html .= '
        <br>
        <table cellpadding="5">
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td>LKR ' . number_format($invoiceDetails['subtotal'], 2) . '</td>
            </tr>';

        // Conditionally add discount if greater than 0
        if ($invoiceDetails['discount'] > 0) {
            $html .= '
            <tr>
                <td><strong>Discount (' . number_format($invoiceDetails['discount'], 2) . '%):</strong></td>
                <td>-LKR ' . number_format($invoiceDetails['subtotal'] * ($invoiceDetails['discount'] / 100), 2) . '</td>
            </tr>';
        }

        // Always add total
        $html .= '
            <tr>
                <td><strong>Total:</strong></td>
                <td>LKR ' . number_format($invoiceDetails['total'], 2) . '</td>
            </tr>
        </table>
        <hr>';

        // Add thank you message
        $html .= '
        <p>Thank you for your business!</p>
        <p style="text-align:center;">Page 1 of 1</p>';

        // Write HTML content to PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Output the PDF to the browser
        $pdf->Output('invoice_' . $invoiceDetails['order_number'] . '.pdf', 'I');
        exit; // Ensure no further code is executed

    } catch (Exception $e) {
        // Clean the output buffer and end output buffering
        if (ob_get_length()) {
            ob_end_clean();
        }
        error_log("Error generating PDF: " . $e->getMessage());
        // Output an error message
        header('Content-Type: text/plain');
        echo "PDF generation failed. Please try again later.";
    }
}
?>
