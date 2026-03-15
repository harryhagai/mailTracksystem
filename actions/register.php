<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header("Location: ../auth/register.php");
        exit();
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
        header("Location: ../auth/register.php");
        exit();
    }
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters';
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
        $_SESSION['error'] = 'Registration failed: ' . $e->getMessage();
        header("Location: ../auth/register.php");
        exit();
    }
} else {
    header("Location: ../auth/register.php");
    exit();
}
?>
