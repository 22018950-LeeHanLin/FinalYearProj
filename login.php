<?php 
session_start(); 
require 'config.php'; 
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $email = $_POST['email']; 
    $password = $_POST['password']; 
 
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?"); 
    $stmt->bind_param("s", $email); 
    $stmt->execute(); 
    $result = $stmt->get_result(); 
    $user = $result->fetch_assoc(); 
 
    if ($user && password_verify($password, $user['password'])) { 
        $_SESSION['user_id'] = $user['id']; 
        $_SESSION['user_name'] = $user['name']; // Save the user's name in the session 
        header("Location: index.php"); // Redirect to home page after login 
        exit(); 
    } else { 
        $error = "Invalid email or password."; 
    } 
} 
?> 
<!DOCTYPE html> 
<html> 
<head> 
    <title>Login</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> 
</head> 
<body> 
    <div class="container"> 
        <h1>Login</h1> 
        <?php if (isset($error)): ?> 
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?> 
        <form method="POST"> 
            <div class="mb-3"> 
                <label for="email" class="form-label">Email</label> 
                <input type="email" name="email" id="email" class="form-control" required> 
            </div> 
            <div class="mb-3"> 
                <label for="password" class="form-label">Password</label> 
                <input type="password" name="password" id="password" class="form-control" required> 
            </div> 
            <button type="submit" class="btn btn-primary">Login</button> 
        </form> 
        <p>Don't have an account? <a href="register.php">Register here</a></p> 
    </div> 
</body> 
</html>
