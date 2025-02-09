<?php
require 'config.php';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
 
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
 
    if ($stmt->execute()) {
        echo "Registration successful! <a href='login.php'>Login here</a>";
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
 
<!DOCTYPE html>
<html>
<head>
<title>Register</title>
</head>
<body>
<h1>Create an Account</h1>
<form method="POST">
<label for="name">Name:</label>
<input type="text" name="name" required><br>
<label for="email">Email:</label>
<input type="email" name="email" required><br>
<label for="password">Password:</label>
<input type="password" name="password" required><br>
<button type="submit">Register</button>
</form>
<p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>
