<?php
session_start();
require 'config.php';
 
error_reporting(E_ALL);
ini_set('display_errors', 1);
 
// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
 
// Handle adding a new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $imageFileName = trim($_POST['image']); // Only the filename, e.g., ultramale.jpg
 
    if (!empty($imageFileName)) {
        $imagePath = "images/" . $imageFileName; // Store relative path in the database
 
        $stmt = $conn->prepare("INSERT INTO products (name, price, image, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $name, $price, $imagePath, $description);
 
        if ($stmt->execute()) {
            header("Location: admin.php");
            exit();
        } else {
            echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p style='color:red;'>Please enter an image filename.</p>";
    }
}
 
// Handle deleting a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
}
 
// Handle updating order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['order_status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
}
 
// Fetch all products
$products = $conn->query("SELECT * FROM products");
 
// Fetch all orders
$orders = $conn->query("
    SELECT orders.id, users.name, users.email, orders.total, orders.shipping_address, 
           orders.payment_method, orders.created_at, orders.status 
    FROM orders 
    JOIN users ON orders.user_id = users.id 
    ORDER BY orders.created_at DESC
");
?>
 
<!DOCTYPE html>
<html>
<head>
<title>Administrator</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<script>
        function confirmDelete(productId) {
            if (confirm("Are you sure you want to delete this product?")) {
                document.getElementById("deleteForm-" + productId).submit();
            }
        }
</script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
<div class="container-fluid">
<a class="navbar-brand" href="index.php">FragBros</a>
<div class="collapse navbar-collapse">
<ul class="navbar-nav ms-auto">
<li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
</ul>
</div>
</div>
</nav>
 
    <div class="container">
<h2>Add a Product</h2>
<form method="POST">
<div class="mb-3">
<label class="form-label">Product Name</label>
<input type="text" name="name" class="form-control" required>
</div>
<div class="mb-3">
<label class="form-label">Price ($)</label>
<input type="number" name="price" class="form-control" step="0.01" required>
</div>
<div class="mb-3">
<label class="form-label">Description</label>
<textarea name="description" class="form-control" required></textarea>
</div>
<div class="mb-3">
<label class="form-label">Image Filename (stored in 'images/' folder)</label>
<input type="text" name="image" class="form-control" placeholder="e.g., ultramale.jpg" required>
</div>
<button type="submit" name="add_product" class="btn btn-success">Add Product</button>
</form>
 
        <h2 class="mt-5">Manage Products</h2>
<table class="table">
<thead>
<tr>
<th>Name</th>
<th>Price</th>
<th>Image</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php while ($product = $products->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($product['name']) ?></td>
<td>$<?= number_format($product['price'], 2) ?></td>
<td><img src="<?= htmlspecialchars($product['image']) ?>" width="50"></td>
<td>
<form method="POST" id="deleteForm-<?= $product['id'] ?>">
<input type="hidden" name="product_id" value="<?= $product['id'] ?>">
<button type="button" onclick="confirmDelete(<?= $product['id'] ?>)" class="btn btn-danger btn-sm">Delete</button>
<input type="hidden" name="delete_product">
</form>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
 
        <h2 class="mt-5">Customer Orders</h2>
<table class="table">
<thead>
<tr>
<th>Order ID</th>
<th>Customer Name</th>
<th>Email</th>
<th>Total</th>
<th>Shipping Address</th>
<th>Payment Method</th>
<th>Date</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php while ($order = $orders->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($order['id']) ?></td>
<td><?= htmlspecialchars($order['name']) ?></td>
<td><?= htmlspecialchars($order['email']) ?></td>
<td>$<?= number_format($order['total'], 2) ?></td>
<td><?= htmlspecialchars($order['shipping_address']) ?></td>
<td><?= htmlspecialchars($order['payment_method']) ?></td>
<td><?= htmlspecialchars($order['created_at']) ?></td>
<td>
<form method="POST">
<input type="hidden" name="order_id" value="<?= $order['id'] ?>">
<select name="order_status" class="form-select">
<option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
<option value="Paid & Delivered" <?= $order['status'] === 'Paid & Delivered' ? 'selected' : '' ?>>Paid & Delivered</option>
</select>
<button type="submit" name="update_order_status" class="btn btn-primary btn-sm mt-1">Update</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</body>
</html>
