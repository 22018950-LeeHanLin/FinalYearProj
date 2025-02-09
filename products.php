<?php
require 'config.php';
session_start();
 
// Fetch products from the database
$result = $conn->query("SELECT * FROM products");
?>
 
<!DOCTYPE html>
<html>
<head>
<title>Products</title>
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
<?php if (isset($_SESSION['user_id'])): ?>
<li class="nav-item"><a class="nav-link" href="account.php">My Account</a></li>
<?php endif; ?>
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<li class="nav-item"><a class="nav-link" href="admin.php">Admin Panel</a></li>
<?php endif; ?>
<li class="nav-item">
<a class="nav-link" href="<?= isset($_SESSION['user_id']) ? 'logout.php' : 'login.php' ?>">
<?= isset($_SESSION['user_id']) ? 'Logout' : 'Login' ?>
</a>
</li>
</ul>
</div>
</div>
</nav>
 
    <div class="container">
<h1>Our Products</h1>
<div class="row">
<?php while ($product = $result->fetch_assoc()): ?>
<div class="col-md-4">
<div class="card">
<img src="<?= htmlspecialchars($product['image']) ?>" class="card-img-top product-image">
<div class="card-body">
<h5><?= htmlspecialchars($product['name']) ?></h5>
<p>$<?= number_format($product['price'], 2) ?></p>
<form method="POST" action="cart.php">
<input type="hidden" name="product_id" value="<?= $product['id'] ?>">
<button type="submit" class="btn btn-primary">Add to Cart</button>
</form>
</div>
</div>
</div>
<?php endwhile; ?>
</div>
</div>
</body>
</html>
