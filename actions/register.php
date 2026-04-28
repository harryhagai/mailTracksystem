<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $email = validate_email_address($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$email || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header("Location: ../auth/register.php");
        exit();
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
        header("Location: ../auth/register.php");
        exit();
    }
    
    if (!password_is_strong($password)) {
        $_SESSION['error'] = 'Password must be at least 8 characters and include letters and numbers';
        header("Location: ../auth/register.php");
        exit();
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Email already registered';
            header("Location: ../auth/register.php");
            exit();
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hashed_password]);
        
        $_SESSION['success'] = 'Registration successful! Please login.';
        header("Location: ../auth/login.php");
        exit();
        
    } catch(PDOException $e) {
        error_log('Registration failed: ' . $e->getMessage());
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header("Location: ../auth/register.php");
        exit();
    }
} else {
    header("Location: ../auth/register.php");
    exit();
}
?>
