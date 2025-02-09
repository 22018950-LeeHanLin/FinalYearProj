<?php
session_start();
require 'config.php';
 
// Ensure the user is logged in and has a verification code
if (!isset($_SESSION['user_id']) || !isset($_SESSION['verification_code'])) {
    header("Location: login.php");
    exit();
}
 
// Handle verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_code = $_POST['verification_code'];
    $generated_code = $_SESSION['verification_code'];
 
    if ($entered_code == $generated_code) {
        // Clear the verification code from the session
        unset($_SESSION['verification_code']);
 
        // Redirect to the dashboard or home page
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}
 
// For testing: Display the generated code
$generated_code = $_SESSION['verification_code'];
?>
<!DOCTYPE html>
<html>
<head>
<title>2FA Verification</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
<h1>2FA Verification</h1>
<p>Please enter the verification code to proceed.</p>
 
        <?php if (isset($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
 
        <form method="POST">
<div class="mb-3">
<label for="verification_code" class="form-label">Verification Code</label>
<input type="text" name="verification_code" id="verification_code" class="form-control" required>
</div>
<button type="submit" class="btn btn-primary">Verify</button>
</form>
 
        <p><strong>Generated Code:</strong> <?= htmlspecialchars($generated_code) ?></p>
</div>
</body>
</html>
