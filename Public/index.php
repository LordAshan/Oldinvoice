<?php
// Public/index.php

use Ashan\Oldinvoice\Lib\Product;

// Autoload classes using Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Include database connection
require_once __DIR__ . '/../src/config/database.php';

// Check if $pdo is defined
if (!isset($pdo)) {
    die('Database connection failed.');
}

// Start the session and generate CSRF token
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Instantiate the Product class with the PDO instance
$productModel = new Product($pdo);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validate CSRF token
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $_SESSION['flash']['error'][] = 'Invalid CSRF token.';
        header('Location: index.php');
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'add') {
        $product_number = $productModel->generateProductNumber();
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        // Validate inputs
        if (empty($name) || $price <= 0) {
            $_SESSION['flash']['error'][] = 'Please provide a valid product name and price.';
        } else {
            $success = $productModel->add($product_number, $name, $price);
            $_SESSION['flash'][$success ? 'success' : 'error'][] = $success
                ? 'Product added successfully!'
                : 'Error adding product. Please try again.';
        }

    } elseif ($action === 'update') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $product_number = trim($_POST['product_number'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        // Validate inputs
        if ($product_id <= 0 || empty($name) || $price <= 0) {
            $_SESSION['flash']['error'][] = 'Please provide valid product details.';
        } else {
            $success = $productModel->update($product_id, $product_number, $name, $price);
            $_SESSION['flash'][$success ? 'success' : 'error'][] = $success
                ? 'Product updated successfully!'
                : 'Error updating product. Please try again.';
        }

    } elseif ($action === 'delete') {
        $product_id = intval($_POST['product_id'] ?? 0);

        if ($product_id <= 0) {
            $_SESSION['flash']['error'][] = 'Invalid product ID.';
        } else {
            $success = $productModel->delete($product_id);
            $_SESSION['flash'][$success ? 'success' : 'error'][] = $success
                ? 'Product deleted successfully!'
                : 'Error deleting product. Please try again.';
        }
    }

    header('Location: index.php');
    exit;
}

/**
 * Fetch all products to display in the table
 */
$products = $productModel->getAll();

/**
 * Generate a new product number for the add form
 * This ensures that the next product has a unique identifier
 */
$product_number = $productModel->generateProductNumber();

/**
 * Fetch product details for editing if 'edit_id' is present in the query string
 */
$editProduct = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    if ($edit_id > 0) {
        $editProduct = $productModel->getById($edit_id);
        if (!$editProduct) {
            $_SESSION['flash']['error'][] = 'Product not found for editing.';
            header('Location: index.php');
            exit;
        }
    } else {
        $_SESSION['flash']['error'][] = 'Invalid product ID for editing.';
        header('Location: index.php');
        exit;
    }
}
?>

<?php include __DIR__ . '/../src/templates/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <!-- Product Form -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><?php echo $editProduct ? 'Update Product' : 'Add New Product'; ?></h4>
                </div>
                <div class="card-body">
                    <!-- Display Flash Messages -->
                    <?php
                        if (!empty($_SESSION['flash'])):
                            foreach ($_SESSION['flash'] as $type => $messages):
                                foreach ($messages as $message):
                    ?>
                                <div class="alert alert-<?php echo htmlspecialchars($type); ?> alert-dismissible fade show" role="alert">
                                    <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                    <?php
                                endforeach;
                            endforeach;
                            unset($_SESSION['flash']);
                        endif;
                    ?>

                    <form action="index.php" method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="<?php echo $editProduct ? 'update' : 'add'; ?>">
                        <?php if ($editProduct): ?>
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($editProduct['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="product_number" class="form-label">Product Number</label>
                            <input type="text" class="form-control" id="product_number" name="product_number" value="<?php echo htmlspecialchars($editProduct ? $editProduct['product_number'] : $product_number, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($editProduct['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            <div class="invalid-feedback">
                                Please enter a product name.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price (LKR) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($editProduct['price'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            <div class="invalid-feedback">
                                Please enter a valid price.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-<?php echo $editProduct ? 'warning' : 'primary'; ?> w-100">
                            <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                        </button>
                        <?php if ($editProduct): ?>
                            <a href="index.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Existing Products</h4>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">Product Number</th>
                                <th scope="col">Name</th>
                                <th scope="col">Price (LKR)</th>
                                <th scope="col" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">No products found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['product_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo number_format($product['price'], 2); ?></td>
                                        <td class="text-center">
                                            <!-- Action Dropdown -->
                                            <div class="dropdown">
                                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="actionMenu<?php echo $product['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionMenu<?php echo $product['id']; ?>">
                                                    <li>
                                                        <a href="index.php?edit_id=<?php echo htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8'); ?>" class="dropdown-item">
                                                            <i class="bi bi-pencil-square me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="index.php" method="post" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this product?');">
                                                                <i class="bi bi-trash me-2"></i>Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
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
</div>

<!-- Include Bootstrap JS and Dependencies (Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Include Custom JavaScript -->
<script src="../resources/js/index.js"></script>

<!-- Bootstrap Form Validation -->
<script>
    // Example starter JavaScript for disabling form submissions if there are invalid fields
    (() => {
        'use strict'

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }

                form.classList.add('was-validated')
            }, false)
        })
    })()
</script>

<?php include __DIR__ . '/../src/templates/footer.php'; ?>
