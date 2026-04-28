<?php
require __DIR__ . '/../config/db.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    if ($action == 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error'] = 'Please fill in all fields';
            header("Location: ../pages/profile.php");
            exit();
        }
        
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = 'New passwords do not match';
            header("Location: ../pages/profile.php");
            exit();
        }
        
        if (!password_is_strong($new_password)) {
            $_SESSION['error'] = 'Password must be at least 8 characters and include letters and numbers';
            header("Location: ../pages/profile.php");
            exit();
        }
        
        try {
            // Get current password hash
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                $_SESSION['error'] = 'Current password is incorrect';
                header("Location: ../pages/profile.php");
                exit();
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            $_SESSION['success'] = 'Password updated successfully!';
            header("Location: ../pages/profile.php");
            exit();
            
        } catch(PDOException $e) {
            error_log('Failed to update password: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to update password. Please try again.';
            header("Location: ../pages/profile.php");
            exit();
        }
    } else {
        header("Location: ../pages/profile.php");
        exit();
    }
} else {
    header("Location: ../pages/profile.php");
    exit();
}
?>
