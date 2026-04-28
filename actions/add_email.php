<?php
require '../config/db.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $email = validate_email_address($_POST['email'] ?? '');
    $due_date = validate_date_string($_POST['due_date'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if (!$email || !$due_date) {
        $_SESSION['error'] = 'Please enter a valid email and date';
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
        error_log('Failed to add email: ' . $e->getMessage());
        $_SESSION['error'] = 'Failed to add email. Please try again.';
        header("Location: ../pages/emails.php");
        exit();
    }
} else {
    header("Location: ../pages/emails.php");
    exit();
}
?>
