<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $due_date = $_POST['due_date'];
    $user_id = $_SESSION['user_id'];
    
    if (empty($email) || empty($due_date)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header("Location: ../pages/emails.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO emails (user_id, email, due_date) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $email, $due_date]);
        
        $_SESSION['success'] = 'Email added successfully!';
        header("Location: ../pages/emails.php");
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Failed to add email: ' . $e->getMessage();
        header("Location: ../pages/emails.php");
        exit();
    }
} else {
    header("Location: ../pages/emails.php");
    exit();
}
?>
