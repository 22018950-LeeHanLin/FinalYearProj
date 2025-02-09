<?php
session_start();
require 'config.php';
 
// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? "Guest";
 
// Fetch only 3 products from the database for display
$result = $conn->query("SELECT * FROM products LIMIT 3");
?>
 
<!DOCTYPE html>
<html>
<head>
<title>FragBros</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
        .product-image {
            width: 100%; /* Makes all images the same width */
            height: 450px; /* Fixed height for uniformity */
            object-fit: cover; /* Ensures images maintain aspect ratio without distortion */
        }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
<div class="container-fluid">
<a class="navbar-brand" href="index.php">FragBros</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav ms-auto">
<li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
<li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
<?php if ($isLoggedIn): ?>
<li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
<?php endif; ?>
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<li class="nav-item"><a class="nav-link" href="admin.php">Admin Panel</a></li>
<?php endif; ?>
<li class="nav-item">
<a class="nav-link" href="<?= $isLoggedIn ? 'logout.php' : 'login.php' ?>">
<?= $isLoggedIn ? 'Logout' : 'Login' ?>
</a>
</li>
</ul>
</div>
</div>
</nav>
 
    <div class="container text-center">
<h1>Welcome, <?= htmlspecialchars($userName) ?>!</h1>
<p>Explore our featured products below!.</p>
</div>
 
    <div class="container">
<div class="row">
<?php while ($product = $result->fetch_assoc()): ?>
<div class="col-md-4">
<div class="card">
<img src="<?= htmlspecialchars($product['image']) ?>" class="card-img-top product-image">
<div class="card-body">
<h5><?= htmlspecialchars($product['name']) ?></h5>
<p>$<?= number_format($product['price'], 2) ?></p>
<a href="products.php" class="btn btn-primary">View More</a>
</div>
</div>
</div>
<?php endwhile; ?>
</div>
</div>
</div>
</body>
</html>
