<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM emails WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = 'Email deleted successfully!';
        } else {
            $_SESSION['error'] = 'Email not found';
        }
        header("Location: ../pages/emails.php");
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = 'Failed to delete email: ' . $e->getMessage();
        header("Location: ../pages/emails.php");
        exit();
    }
} else {
    header("Location: ../pages/emails.php");
    exit();
}
?>
