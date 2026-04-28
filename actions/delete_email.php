<?php
require '../config/db.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$id) {
        $_SESSION['error'] = 'Invalid email record';
        header("Location: ../pages/emails.php");
        exit();
    }
    
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
        error_log('Failed to delete email: ' . $e->getMessage());
        $_SESSION['error'] = 'Failed to delete email. Please try again.';
        header("Location: ../pages/emails.php");
        exit();
    }
} else {
    header("Location: ../pages/emails.php");
    exit();
}
?>
