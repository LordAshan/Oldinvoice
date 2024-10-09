<?php
// Public/invoice.php

// Start output buffering
ob_start();

// Enable error reporting temporarily (Disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include necessary functions and libraries
require_once __DIR__ . '/../src/lib/functions.php'; // Adjust the path as necessary

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission to create an invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    createInvoice();
    exit; // Ensure no further code is executed after PDF generation
}

// Fetch all products from the database
$products = getAllProducts();
?>
<?php include __DIR__ . '/../src/templates/header.php'; ?> <!-- Include header -->

<!-- Main Content Container -->
<div class="container my-5">
    <h1 class="text-center mb-4">Create Invoice</h1>

    <!-- Display Success or Error Messages -->
    <?php
        if (!empty($_SESSION['errors'])) {
            echo '<div class="alert alert-danger" role="alert">';
            foreach ($_SESSION['errors'] as $error) {
                echo '<p class="mb-0">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';
            }
            echo '</div>';
            unset($_SESSION['errors']); // Clear errors after displaying
        }
    ?>

    <!-- Invoice Form -->
    <form action="invoice.php" method="post" id="invoice-form" target="_blank">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Customer Details Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Customer Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Client Name -->
                    <div class="col-md-6">
                        <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                        <input type="text" id="client_name" name="client_name" class="form-control" required>
                    </div>
                    <!-- Customer Phone Number -->
                    <div class="col-md-6">
                        <label for="customer_phone" class="form-label">Customer Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" id="customer_phone" name="customer_phone" class="form-control"  placeholder="e.g., 0771234567" required>
                        <div class="form-text">Enter the phone number.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Details Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Invoice Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Purchase Date -->
                    <div class="col-md-4">
                        <label for="purchase_date" class="form-label">Purchase Date <span class="text-danger">*</span></label>
                        <input type="date" id="purchase_date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required onchange="updateExpireDate()">
                    </div>
                    
                    <!-- Expire Duration -->
                    <div class="col-md-4">
                        <label for="expire_duration" class="form-label">Expire Duration <span class="text-danger">*</span></label>
                        <select id="expire_duration" name="expire_duration" class="form-select" required onchange="updateExpireDate()">
                            <option value="">Select Duration</option>
                            <option value="1_month">1 Month</option>
                            <option value="2_months">2 Months</option>
                            <option value="3_months">3 Months</option>
                            <option value="6_months">6 Months</option>
                            <option value="1_year">1 Year</option>
                            <option value="2_years">2 Years</option>
                            <option value="3_years">3 Years</option>
                            <option value="5_years">5 Years</option>
                        </select>
                    </div>
                    
                    <!-- Expire Date Display -->
                    <div class="col-md-4">
                        <label for="expire_date" class="form-label">Expire Date</label>
                        <input type="date" id="expire_date" name="expire_date" class="form-control" readonly>
                    </div>
                    
                    <!-- Payment Status -->
                    <div class="col-md-6">
                        <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                        <select id="payment_status" name="payment_status" class="form-select" required>
                            <option value="Paid">Paid</option>
                            <option value="Not Paid">Not Paid</option>
                            <option value="Partial">Partial</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Products</h5>
                <button type="button" class="btn btn-primary btn-sm" onclick="addProduct()">Add Product</button>
            </div>
            <div class="card-body" id="product-list">
                <!-- Initial Product Item -->
                <div class="product-item border p-3 mb-3 position-relative">
                    <!-- Remove Product Button (Hidden for the first product) -->
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-2" aria-label="Close" onclick="removeProduct(this)"></button>

                    <div class="row g-3">
                        <!-- Product Name Dropdown -->
                        <div class="col-md-6">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <select class="form-select product-name" name="product_name[]" onchange="fetchProductDetails(this)" required>
                                <option value="">Select a product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Quantity Input -->
                        <div class="col-md-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control quantity" name="quantity[]" value="1" min="1" required oninput="calculateTotal()">
                        </div>

                        <!-- Price Display -->
                        <div class="col-md-3">
                            <label class="form-label">Price</label>
                            <input type="hidden" class="form-control price-input" name="price[]">
                            <p class="form-control-plaintext">LKR <span class="price">0.00</span></p>
                        </div>

                        <!-- Upgrader Key Field (Hidden by Default) -->
                        <div class="col-12 upgrader-key-field d-none">
                            <label class="form-label">Upgrader Key</label>
                            <input type="text" class="form-control upgrader-key" name="upgrader_key[]" placeholder="Enter Upgrader Key">
                        </div>
                    </div>
                </div>
                <!-- Additional Product Items will be appended here by JavaScript -->
            </div>
        </div>

        <!-- Discount and Total Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <!-- Discount Input -->
                    <div class="col-md-6">
                        <label for="discount" class="form-label">Discount (%)</label>
                        <input type="number" id="discount" name="discount" class="form-control" value="0" min="0" max="100" oninput="calculateTotal()">
                        <div class="form-text">Enter a discount percentage to apply to the subtotal.</div>
                    </div>
                </div>
                <hr>
                <!-- Subtotal and Total Display -->
                <div class="d-flex justify-content-end">
                    <div class="me-5">
                        <p class="mb-1"><strong>Subtotal:</strong> LKR <span id="subtotal">0.00</span></p>
                        <p class="mb-1"><strong>Total:</strong> LKR <span id="total">0.00</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success btn-lg">Create Invoice</button>
        </div>
    </form>
</div>

<!-- Embed Products Data for JavaScript -->
<script>
    const products = <?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<!-- Include External JavaScript -->
<script src="../resources/js/invoice.js"></script>

<?php include __DIR__ . '/../src/templates/footer.php'; ?> <!-- Include footer -->
