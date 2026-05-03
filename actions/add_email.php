<?php
require '../config/db.php';

require_login();

// Apply rate limiting for data modifications
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rate_limit($client_ip . '_email_add', 10, 60)) {
    log_security_event(SECURITY_RATE_LIMIT_EXCEEDED, "Email add rate limit exceeded", ['ip' => $client_ip]);
    $_SESSION['error'] = 'Too many requests. Please wait and try again.';
    header("Location: ../pages/emails.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $validator = new InputValidator($_POST);
    $validator->validate('email', 'email', ['required' => true]);
    $validator->validate('due_date', 'date', ['required' => true]);
    
    if ($validator->hasErrors()) {
        $_SESSION['error'] = 'Please enter a valid email and date';
        log_security_event(SECURITY_DATA_MODIFICATION, "Email add validation failed", 
            ['errors' => $validator->getErrors()]);
        header("Location: ../pages/emails.php");
        exit();
    }
    
    $data = $validator->getValidatedData();
    $email = $data['email'];
    $due_date = $data['due_date'];
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO emails (user_id, email, due_date) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $email, $due_date]);
        
        $email_id = $pdo->lastInsertId();
        
        log_data_modification('INSERT', 'emails', $email_id, null, [
            'user_id' => $user_id,
            'email' => $email,
            'due_date' => $due_date
        ]);
        
        $_SESSION['success'] = 'Email added successfully!';
        header("Location: ../pages/emails.php");
        exit();
        
    } catch(PDOException $e) {
        error_log('Failed to add email: ' . $e->getMessage());
        log_security_event(SECURITY_DATA_MODIFICATION, "Email add database error", 
            ['error' => $e->getMessage()]);
        $_SESSION['error'] = 'Failed to add email. Please try again.';
        header("Location: ../pages/emails.php");
        exit();
    }
} else {
    header("Location: ../pages/emails.php");
    exit();
}
?>
