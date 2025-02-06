<?php 
session_start(); 
require 'config.php'; 
 
// Fetch products from the database 
$result = $conn->query("SELECT * FROM products"); 
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Products</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> 
    <style> 
        .product-image { 
            width: 100%; 
            height: 250px; 
            object-fit: cover; 
            border-radius: 5px; 
        } 
    </style> 
</head> 
<body> 
    <!-- Navbar --> 
    <nav class="navbar navbar-expand-lg navbar-light bg-light"> 
        <div class="container-fluid"> 
            <a class="navbar-brand" href="index.php">eShop</a> 
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"> 
                <span class="navbar-toggler-icon"></span> 
            </button> 
            <div class="collapse navbar-collapse" id="navbarNav"> 
                <ul class="navbar-nav ms-auto"> 
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li> 
                    <li class="nav-item"><a class="nav-link active" href="products.php">Products</a></li> 
                    <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li> 
                    <?php if (isset($_SESSION['user_name'])): ?> 
                        <li class="nav-item"><a class="nav-link" href="#">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</a></li> 
                        <li class="nav-item"><a class="nav-link" href="logout.php">Log Out</a></li> 
                    <?php else: ?> 
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li> 
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li> 
                    <?php endif; ?> 
                </ul> 
            </div> 
        </div> 
    </nav> 
 
    <!-- Products Section --> 
    <div class="container"> 
        <h1>Products</h1> 
        <div class="row"> 
            <?php while ($product = $result->fetch_assoc()): ?> 
                <div class="col-md-4"> 
                    <div class="card" style="margin-bottom: 20px;"> 
                        <img src="<?= htmlspecialchars($product['image']) ?>" class="card-img-top product-image" alt="<?= htmlspecialchars($product['name']) ?>"> 
                        <div class="card-body"> 
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5> 
                            <p class="card-text">$<?= number_format($product['price'], 2) ?></p> 
                            <form method="POST" action="cart.php"> 
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>"> 
                                <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['name']) ?>"> 
                                <input type="hidden" name="product_price" value="<?= $product['price'] ?>"> 
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
