<?php 
session_start(); 
require 'config.php'; 
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $name = $_POST['name']; 
    $email = $_POST['email']; 
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
 
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)"); 
    $stmt->bind_param("sss", $name, $email, $password); 
 
    if ($stmt->execute()) { 
        header("Location: login.php"); 
        exit(); 
    } else { 
        $error = "Registration failed: " . $conn->error; 
    } 
} 
?> 
<!DOCTYPE html> 
<html> 
<head> 
    <title>Register</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> 
</head> 
<body> 
    <div class="container"> 
        <h1>Register</h1> 
        <?php if (isset($error)): ?> 
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?> 
        <form method="POST"> 
            <div class="mb-3"> 
                <label for="name" class="form-label">Name</label> 
                <input type="text" name="name" id="name" class="form-control" required> 
            </div> 
            <div class="mb-3"> 
                <label for="email" class="form-label">Email</label> 
                <input type="email" name="email" id="email" class="form-control" required> 
            </div> 
            <div class="mb-3"> 
                <label for="password" class="form-label">Password</label> 
                <input type="password" name="password" id="password" class="form-control" required> 
            </div> 
            <button type="submit" class="btn btn-primary">Register</button> 
        </form> 
    </div> 
</body> 
</html>
