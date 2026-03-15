<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $email = trim($_POST['email']);
    $due_date = $_POST['due_date'];
    $user_id = $_SESSION['user_id'];
    
    if (empty($email) || empty($due_date) || empty($id)) {
        $_SESSION['error'] = 'Please fill in all fields';
        header("Location: ../pages/emails.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE emails SET email = ?, due_date = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$email, $due_date, $id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = 'Email updated successfully!';
        } else {
            $_SESSION['error'] = 'Email not found or no changes made';
        }
        header("Location: ../pages/emails.php");
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Failed to update email: ' . $e->getMessage();
        header("Location: ../pages/emails.php");
        exit();
    }
} else {
    header("Location: ../pages/emails.php");
    exit();
}
?>
