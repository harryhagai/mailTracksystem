<?php
require '../config/db.php';

// Apply rate limiting
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rate_limit($client_ip, RATE_LIMIT_LOGIN_REQUESTS, RATE_LIMIT_LOGIN_WINDOW)) {
    log_security_event(SECURITY_RATE_LIMIT_EXCEEDED, "Registration rate limit exceeded", ['ip' => $client_ip]);
    $_SESSION['error'] = 'Too many registration attempts. Please wait and try again.';
    header("Location: ../auth/register.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $validator = new InputValidator($_POST);
    $validator->validate('email', 'email', ['required' => true]);
    $validator->validate('password', 'string', ['required' => true, 'min_length' => PASSWORD_MIN_LENGTH]);
    $validator->validate('confirm_password', 'string', ['required' => true]);
    
    if ($validator->hasErrors()) {
        $_SESSION['error'] = 'Please fill in all fields correctly';
        log_security_event(SECURITY_LOGIN_FAILURE, "Registration validation failed", 
            ['errors' => $validator->getErrors()]);
        header("Location: ../auth/register.php");
        exit();
    }
    
    $data = $validator->getValidatedData();
    $email = $data['email'];
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
        header("Location: ../auth/register.php");
        exit();
    }
    
    $password_errors = SecurePassword::validateStrength($password);
    if (!empty($password_errors)) {
        $_SESSION['error'] = 'Password requirements not met: ' . implode(', ', $password_errors);
        header("Location: ../auth/register.php");
        exit();
    }
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Email already registered';
            log_security_event(SECURITY_LOGIN_FAILURE, "Duplicate registration attempt", 
                ['email' => $email, 'ip' => $client_ip]);
            header("Location: ../auth/register.php");
            exit();
        }
        
        // Hash password securely
        $hashed_password = SecurePassword::hash($password);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->execute([$email, $hashed_password]);
        
        $user_id = $pdo->lastInsertId();
        
        log_security_event(SECURITY_LOGIN_SUCCESS, "User registered successfully", 
            ['user_id' => $user_id, 'email' => $email]);
        
        $_SESSION['success'] = 'Registration successful! Please login.';
        header("Location: ../auth/login.php");
        exit();
        
    } catch(PDOException $e) {
        error_log('Registration failed: ' . $e->getMessage());
        log_security_event(SECURITY_LOGIN_FAILURE, "Database error during registration", 
            ['email' => $email, 'error' => $e->getMessage()]);
        $_SESSION['error'] = 'Registration failed. Please try again.';
        header("Location: ../auth/register.php");
        exit();
    }
} else {
    header("Location: ../auth/register.php");
    exit();
}
?>
