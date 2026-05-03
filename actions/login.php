<?php
require '../config/db.php';

// Apply rate limiting
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rate_limit($client_ip, RATE_LIMIT_LOGIN_REQUESTS, RATE_LIMIT_LOGIN_WINDOW)) {
    log_security_event(SECURITY_RATE_LIMIT_EXCEEDED, "Login rate limit exceeded", ['ip' => $client_ip]);
    $_SESSION['error'] = 'Too many login attempts. Please wait and try again.';
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $validator = new InputValidator($_POST);
    $validator->validate('email', 'email', ['required' => true]);
    $validator->validate('password', 'string', ['required' => true, 'min_length' => 1]);
    
    if ($validator->hasErrors()) {
        $_SESSION['error'] = 'Invalid email or password';
        log_security_event(SECURITY_LOGIN_FAILURE, "Login validation failed", 
            ['errors' => $validator->getErrors()]);
        header("Location: ../auth/login.php");
        exit();
    }
    
    $data = $validator->getValidatedData();
    $email = $data['email'];
    $password = $data['password'];
    $now = time();
    $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'until' => 0];

    if (!empty($attempts['until']) && $attempts['until'] > $now) {
        $_SESSION['error'] = 'Account temporarily locked due to too many failed attempts. Please try again later.';
        log_security_event(SECURITY_LOGIN_FAILURE, "Login attempt during lockout", 
            ['email' => $email, 'ip' => $client_ip]);
        header("Location: ../auth/login.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && SecurePassword::verify($password, $user['password'])) {
            session_regenerate_id(true);
            unset($_SESSION['login_attempts']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['last_activity'] = time();
            
            log_security_event(SECURITY_LOGIN_SUCCESS, "User logged in successfully", 
                ['user_id' => $user['id'], 'email' => $email]);
            
            header("Location: ../pages/dashboard.php");
            exit();
        } else {
            $count = ((int) ($attempts['count'] ?? 0)) + 1;
            $_SESSION['login_attempts'] = [
                'count' => $count,
                'until' => $count >= MAX_LOGIN_ATTEMPTS ? $now + LOGIN_LOCKOUT_TIME : 0,
            ];
            
            log_security_event(SECURITY_LOGIN_FAILURE, "Invalid login attempt", 
                ['email' => $email, 'attempt_count' => $count, 'ip' => $client_ip]);
            
            $_SESSION['error'] = 'Invalid email or password';
            header("Location: ../auth/login.php");
            exit();
        }
    } catch(PDOException $e) {
        error_log('Login failed: ' . $e->getMessage());
        log_security_event(SECURITY_LOGIN_FAILURE, "Database error during login", 
            ['email' => $email, 'error' => $e->getMessage()]);
        $_SESSION['error'] = 'Login failed. Please try again.';
        header("Location: ../auth/login.php");
        exit();
    }
} else {
    header("Location: ../auth/login.php");
    exit();
}
?>
