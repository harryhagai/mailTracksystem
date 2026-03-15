<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header("Location: ../auth/login.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            header("Location: ../pages/dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = 'Invalid email or password';
            header("Location: ../auth/login.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Login failed: ' . $e->getMessage();
        header("Location: ../auth/login.php");
        exit();
    }
} else {
    header("Location: ../auth/login.php");
    exit();
}
?>
