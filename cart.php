<?php 
session_start(); 
 
// Initialize cart if it doesn't exist 
if (!isset($_SESSION['cart'])) { 
    $_SESSION['cart'] = []; 
} 
 
// Handle POST requests 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    // Add product to cart 
    if (isset($_POST['product_id'])) { 
        $product_id = $_POST['product_id']; 
        $product_name = $_POST['product_name']; 
        $product_price = $_POST['product_price']; 
        $quantity = 1; 
 
        // Check if product is already in cart 
        $found = false; 
        foreach ($_SESSION['cart'] as &$item) { 
            if ($item['id'] == $product_id) { 
                $item['quantity']++; 
                $found = true; 
                break; 
            } 
        } 
 
        // If not found, add as a new item 
        if (!$found) { 
            $_SESSION['cart'][] = [ 
                'id' => $product_id, 
                'name' => $product_name, 
                'price' => $product_price, 
                'quantity' => $quantity 
            ]; 
        } 
    } 
 
    // Update product quantity 
    if (isset($_POST['update_quantity'])) { 
        $product_id = $_POST['product_id']; 
        $new_quantity = max(1, intval($_POST['quantity'])); 
        foreach ($_SESSION['cart'] as &$item) { 
            if ($item['id'] == $product_id) { 
                $item['quantity'] = $new_quantity; 
                break; 
            } 
        } 
    } 
 
    // Remove product from cart 
    if (isset($_POST['remove_item'])) { 
        $product_id = $_POST['product_id']; 
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function ($item) use ($product_id) { 
            return $item['id'] != $product_id; 
        }); 
    } 
} 
 
// Fetch cart items 
$cart_items = $_SESSION['cart']; 
?> 
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Cart</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> 
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
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li> 
                    <li class="nav-item"><a class="nav-link active" href="cart.php">Cart</a></li> 
                </ul> 
            </div> 
        </div> 
    </nav> 
 
    <!-- Cart Section --> 
    <div class="container"> 
        <h1>Your Cart</h1> 
        <?php if (empty($cart_items)): ?> 
            <p>Your cart is empty.</p> 
        <?php else: ?> 
            <table class="table"> 
                <thead> 
                    <tr> 
                        <th>Product</th> 
                        <th>Price</th> 
                        <th>Quantity</th> 
                        <th>Total</th> 
                        <th>Actions</th> 
                    </tr> 
                </thead> 
                <tbody> 
                    <?php foreach ($cart_items as $item): ?> 
                        <tr> 
                            <td><?= htmlspecialchars($item['name']) ?></td> 
                            <td>$<?= number_format($item['price'], 2) ?></td> 
                            <td> 
                                <form method="POST" style="display: inline;"> 
                                    <input type="hidden"

name="product_id" value="<?= $item['id'] ?>"> 
                                    <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" style="width: 50px;"> 
                                    <button type="submit" name="update_quantity" class="btn btn-secondary btn-sm">Update</button> 
                                </form> 
                            </td> 
                            <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td> 
                            <td> 
                                <form method="POST" style="display: inline;"> 
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>"> 
                                    <button type="submit" name="remove_item" class="btn btn-danger btn-sm">Remove</button> 
                                </form> 
                            </td> 
                        </tr> 
                    <?php endforeach; ?> 
                </tbody> 
            </table> 
        <?php endif; ?> 
    </div> 
</body> 
</html>
