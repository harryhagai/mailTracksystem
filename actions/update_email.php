<?php
require '../config/db.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $email = validate_email_address($_POST['email'] ?? '');
    $due_date = validate_date_string($_POST['due_date'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if (!$id || !$email || !$due_date) {
        $_SESSION['error'] = 'Please enter valid details';
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
        error_log('Failed to update email: ' . $e->getMessage());
        $_SESSION['error'] = 'Failed to update email. Please try again.';
        header("Location: ../pages/emails.php");
        exit();
    }
} else {
    header("Location: ../pages/emails.php");
    exit();
}
?>
