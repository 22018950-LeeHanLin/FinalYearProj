<?php
session_start();
require 'config.php';
 
// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
 
$user_id = $_SESSION['user_id'];
$error = "";
$success = "";
 
// Fetch user details
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
 
// Fetch user order history with ordered product names
$order_stmt = $conn->prepare("
    SELECT orders.id, orders.total, orders.shipping_address, orders.payment_method, orders.created_at, orders.status, 
           (SELECT GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = orders.id) AS ordered_items
    FROM orders
    WHERE orders.user_id = ? 
    ORDER BY created_at DESC
");
 
 
$order_stmt->bind_param("i", $user_id);
$order_stmt->execute();
$orders = $order_stmt->get_result();
 
// Handle updating name
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_name'])) {
    $new_name = trim($_POST['name']);
    if (!empty($new_name)) {
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $new_name, $user_id);
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $new_name;
            $success = "Name updated successfully!";
        } else {
            $error = "Failed to update name.";
        }
    } else {
        $error = "Name cannot be empty.";
    }
}
 
// Handle updating password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
 
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stored_password = $stmt->get_result()->fetch_assoc()['password'];
 
    if (password_verify($current_password, $stored_password)) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            if ($stmt->execute()) {
                $success = "Password updated successfully!";
            } else {
                $error = "Failed to update password.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>
 
<!DOCTYPE html>
<html>
<head>
<title>My Account</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
 
        <h2>Profile Details</h2>
<p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
 
        <h3>Update Name</h3>
<form method="POST">
<div class="mb-3">
<label class="form-label">New Name</label>
<input type="text" name="name" class="form-control" required>
</div>
<button type="submit" name="update_name" class="btn btn-primary">Update Name</button>
</form>
 
        <h3 class="mt-4">Change Password</h3>
<form method="POST">
<div class="mb-3">
<label class="form-label">Current Password</label>
<input type="password" name="current_password" class="form-control" required>
</div>
<div class="mb-3">
<label class="form-label">New Password</label>
<input type="password" name="new_password" class="form-control" required>
</div>
<div class="mb-3">
<label class="form-label">Confirm New Password</label>
<input type="password" name="confirm_password" class="form-control" required>
</div>
<button type="submit" name="update_password" class="btn btn-warning">Change Password</button>
</form>
 
        <h2 class="mt-5">Order History</h2>
<?php if ($orders->num_rows > 0): ?>
<table class="table">
<thead>
<tr>
<th>Order ID</th>
<th>Items</th>
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
<td><?= htmlspecialchars($order['ordered_items']) ?></td>
<td>$<?= number_format($order['total'], 2) ?></td>
<td><?= htmlspecialchars($order['shipping_address']) ?></td>
<td><?= htmlspecialchars($order['payment_method']) ?></td>
<td><?= htmlspecialchars($order['created_at']) ?></td>
<td><strong><?= htmlspecialchars($order['status']) ?></strong></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<p>You have no past orders.</p>
<?php endif; ?>
</div>
</body>
</html>
