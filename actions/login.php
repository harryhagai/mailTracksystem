<?php
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $email = validate_email_address($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $now = time();
    $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'until' => 0];

    if (!empty($attempts['until']) && $attempts['until'] > $now) {
        $_SESSION['error'] = 'Too many login attempts. Please wait and try again.';
        header("Location: ../auth/login.php");
        exit();
    }
    
    if (!$email || $password === '') {
        $_SESSION['error'] = 'Invalid email or password';
        header("Location: ../auth/login.php");
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            unset($_SESSION['login_attempts']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            header("Location: ../pages/dashboard.php");
            exit();
        } else {
            $count = ((int) ($attempts['count'] ?? 0)) + 1;
            $_SESSION['login_attempts'] = [
                'count' => $count,
                'until' => $count >= 5 ? $now + 600 : 0,
            ];
            $_SESSION['error'] = 'Invalid email or password';
            header("Location: ../auth/login.php");
            exit();
        }
    } catch(PDOException $e) {
        error_log('Login failed: ' . $e->getMessage());
        $_SESSION['error'] = 'Login failed. Please try again.';
        header("Location: ../auth/login.php");
        exit();
    }
} else {
    header("Location: ../auth/login.php");
    exit();
}
?>
