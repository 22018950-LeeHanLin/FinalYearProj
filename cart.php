<?php
session_start();
require 'config.php';
 
// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add product to cart
    if (isset($_POST['product_id']) && !isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
 
        // Fetch product details from the database
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
 
        if ($product) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
 
            $found = false;
            // Check if the product already exists in the cart
            foreach ($_SESSION['cart'] as $index => $item) {
                if ($item['id'] == $product_id) {
                    $_SESSION['cart'][$index]['quantity']++;
                    $found = true;
                    break;
                }
            }
 
            // If not found, add it to the cart
            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => 1
                ];
            }
        }
    }
 
    // Update product quantity
    if (isset($_POST['update_quantity'])) {
        $product_id = $_POST['product_id'];
        $new_quantity = max(1, intval($_POST['quantity'])); // Ensure at least 1
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
        // Correctly filter the cart to remove only the specified product
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $product_id) {
                unset($_SESSION['cart'][$key]); // Remove the specific item
                break; // Stop after removing one item
            }
        }
        // Re-index the array to avoid gaps
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
}
 
// Fetch cart items
$cart_items = $_SESSION['cart'] ?? [];
?>
<!DOCTYPE html>
<html>
<head>
<title>Cart</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
<h1>Your Shopping Cart</h1>
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
<?php
                    $grand_total = 0;
                    foreach ($cart_items as $item):
                        $total_price = $item['price'] * $item['quantity'];
                        $grand_total += $total_price;
                    ?>
<tr>
<td><?= htmlspecialchars($item['name']) ?></td>
<td>$<?= number_format($item['price'], 2) ?></td>
<td>
<form method="POST" style="display: inline;">
<input type="hidden" name="product_id" value="<?= $item['id'] ?>">
<input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" style="width: 50px;">
<button type="submit" name="update_quantity" class="btn btn-secondary btn-sm">Update</button>
</form>
</td>
<td>$<?= number_format($total_price, 2) ?></td>
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
<h3>Total: $<?= number_format($grand_total, 2) ?></h3>
<?php endif; ?>
<a href="products.php" class="btn btn-primary">Continue Shopping</a>
<a href="checkout.php" class="btn btn-success">Checkout</a>
</div>
</body>
</html>
