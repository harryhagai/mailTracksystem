<?php
require '../config/db.php';

require_login();

// Apply rate limiting for data modifications
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rate_limit($client_ip . '_email_delete', 10, 60)) {
    log_security_event(SECURITY_RATE_LIMIT_EXCEEDED, "Email delete rate limit exceeded", ['ip' => $client_ip]);
    $_SESSION['error'] = 'Too many requests. Please wait and try again.';
    header("Location: ../pages/emails.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $validator = new InputValidator($_POST);
    $validator->validate('id', 'int', ['required' => true, 'min' => 1]);
    
    if ($validator->hasErrors()) {
        $_SESSION['error'] = 'Invalid email record';
        log_security_event(SECURITY_DATA_MODIFICATION, "Email delete validation failed", 
            ['errors' => $validator->getErrors()]);
        header("Location: ../pages/emails.php");
        exit();
    }
    
    $data = $validator->getValidatedData();
    $id = $data['id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify resource ownership
    if (!user_owns_resource($id, 'email', $user_id)) {
        log_security_event(SECURITY_ACCESS_DENIED, "Unauthorized email deletion attempt", 
            ['email_id' => $id, 'user_id' => $user_id]);
        $_SESSION['error'] = 'Email not found';
        header("Location: ../pages/emails.php");
        exit();
    }
    
    try {
        // Get email data for logging before deletion
        $stmt = $pdo->prepare("SELECT * FROM emails WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $email_data = $stmt->fetch();
        
        $stmt = $pdo->prepare("DELETE FROM emails WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            log_data_modification('DELETE', 'emails', $id, $email_data, null);
            $_SESSION['success'] = 'Email deleted successfully!';
        } else {
            $_SESSION['error'] = 'Email not found';
        }
        header("Location: ../pages/emails.php");
        exit();
        
    } catch(PDOException $e) {
        error_log('Failed to delete email: ' . $e->getMessage());
        log_security_event(SECURITY_DATA_MODIFICATION, "Email delete database error", 
            ['email_id' => $id, 'error' => $e->getMessage()]);
        $_SESSION['error'] = 'Failed to delete email. Please try again.';
        header("Location: ../pages/emails.php");
        exit();
    }
} else {
    header("Location: ../pages/emails.php");
    exit();
}
?>
