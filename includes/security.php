<?php
function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        $_SESSION['error'] = 'Security token expired. Please try again.';
        redirect_back();
    }
}

function redirect_to(string $path): never
{
    header('Location: ' . $path);
    exit();
}

function redirect_back(string $fallback = '../auth/login.php'): never
{
    $target = $_SERVER['HTTP_REFERER'] ?? $fallback;
    $parts = parse_url($target);
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if (!empty($parts['host']) && $parts['host'] !== $host) {
        $target = $fallback;
    }

    header('Location: ' . $target);
    exit();
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect_to('../auth/login.php');
    }
}

function validate_email_address(string $email): ?string
{
    $email = trim($email);
    if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    return strtolower($email);
}

function validate_date_string(string $date): ?string
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        return null;
    }

    return $date;
}

function password_is_strong(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Za-z]/', $password)
        && preg_match('/\d/', $password);
}

secure_session_start();
send_security_headers();
