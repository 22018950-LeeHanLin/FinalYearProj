<?php
session_start();
require 'config.php';
 
// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}
 
// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userEmail = $isLoggedIn ? $_SESSION['user_email'] : '';
 
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = $_POST['shipping_address'];
    $payment_method = $_POST['payment_method'];
    $order_total = 0;
 
    // Calculate order total
    foreach ($_SESSION['cart'] as $item) {
        $order_total += $item['price'] * $item['quantity'];
    }
 
    // Save the order to the database
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, shipping_address, payment_method) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $_SESSION['user_id'], $order_total, $shipping_address, $payment_method);
    $stmt->execute();
    $order_id = $stmt->insert_id;
 
    // Save order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($_SESSION['cart'] as $item) {
        $stmt->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }
 
    // Clear the cart
    unset($_SESSION['cart']);
 
    // Show confirmation message on the same page
    $order_confirmed = true;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Checkout</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
<?php if (!empty($order_confirmed)): ?>
<h1>Thank You for Your Order!</h1>
<p>Your order has been placed successfully.</p>
<p><strong>Order Total:</strong> $<?= number_format($order_total, 2) ?></p>
<p><strong>Shipping Address:</strong> <?= htmlspecialchars($shipping_address) ?></p>
<a href="index.php" class="btn btn-primary">Continue Shopping</a>
<?php else: ?>
<h1>Checkout</h1>
<h3>Order Summary</h3>
<table class="table">
<thead>
<tr>
<th>Product</th>
<th>Quantity</th>
<th>Price</th>
<th>Total</th>
</tr>
</thead>
<tbody>
<?php
                    $grand_total = 0;
                    foreach ($_SESSION['cart'] as $item):
                        $total_price = $item['price'] * $item['quantity'];
                        $grand_total += $total_price;
                    ?>
<tr>
<td><?= htmlspecialchars($item['name']) ?></td>
<td><?= htmlspecialchars($item['quantity']) ?></td>
<td>$<?= number_format($item['price'], 2) ?></td>
<td>$<?= number_format($total_price, 2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<h4>Total: $<?= number_format($grand_total, 2) ?></h4>
 
            <form method="POST">
<h3>Shipping Information</h3>
<div class="mb-3">
<label for="shipping_address" class="form-label">Shipping Address</label>
<textarea name="shipping_address" id="shipping_address" class="form-control" required></textarea>
</div>
<h3>Payment Method</h3>
<div class="mb-3">
<select name="payment_method" class="form-select" required>
<option value="Cash on Delivery">Cash on Delivery</option>
</select>
</div>
<button type="submit" class="btn btn-success">Place Order</button>
</form>
<?php endif; ?>
</div>
</body>
</html>
