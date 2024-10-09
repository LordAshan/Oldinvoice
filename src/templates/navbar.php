<?php
// src/templates/navbar.php
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <!-- Replace the text with an image for the logo -->
        <a class="navbar-brand" href="index.php">
            <img src="images/logo_nav.png" alt="Subscription Planet" height="30">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? ' active' : ''; ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'invoice.php' ? ' active' : ''; ?>" href="invoice.php">Create Invoice</a>
                </li>
                <!-- Add more navigation links here as needed -->
            </ul>
        </div>
    </div>
</nav>

