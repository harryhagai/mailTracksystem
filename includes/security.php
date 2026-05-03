<?php
// Temporarily disable security modules to isolate 500 error
// require_once __DIR__ . '/security_config.php';
// require_once __DIR__ . '/logging.php';
// require_once __DIR__ . '/access_control.php';
// require_once __DIR__ . '/input_validation.php';
// require_once __DIR__ . '/crypto.php';
// require_once __DIR__ . '/ssrf_protection.php';

// Define constants temporarily to avoid errors
define('COOKIE_SAMESITE', 'Lax');
define('SESSION_LIFETIME', 3600);
define('SESSION_REGENERATE_INTERVAL', 300);
define('COOKIE_SECURE', false);
define('COOKIE_HTTPONLY', true);

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
    ini_set('session.cookie_samesite', COOKIE_SAMESITE);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => COOKIE_SECURE,
        'httponly' => COOKIE_HTTPONLY,
        'samesite' => COOKIE_SAMESITE,
    ]);

    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > SESSION_REGENERATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    // Basic security headers for now
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
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
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_destroy();
        redirect_to('../auth/login.php');
    }
    
    $_SESSION['last_activity'] = time();
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

// Temporary rate limiting function
function rate_limit(string $identifier, int $max_attempts = 60, int $window_seconds = 60): bool
{
    $cache_file = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
    
    if (!file_exists($cache_file)) {
        file_put_contents($cache_file, json_encode(['attempts' => 1, 'start_time' => time()]));
        return true;
    }
    
    $data = json_decode(file_get_contents($cache_file), true);
    $elapsed = time() - $data['start_time'];
    
    if ($elapsed > $window_seconds) {
        file_put_contents($cache_file, json_encode(['attempts' => 1, 'start_time' => time()]));
        return true;
    }
    
    if ($data['attempts'] >= $max_attempts) {
        return false;
    }
    
    $data['attempts']++;
    file_put_contents($cache_file, json_encode($data));
    return true;
}

// Temporary logging function
function log_security_event(string $event_type, string $message, array $context = []): void
{
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => $event_type,
        'message' => $message,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'context' => $context
    ];
    
    $log_message = json_encode($log_entry);
    error_log($log_message . PHP_EOL, 3, __DIR__ . '/../logs/security.log');
}

// Define security event constants
define('SECURITY_LOGIN_SUCCESS', 'LOGIN_SUCCESS');
define('SECURITY_LOGIN_FAILURE', 'LOGIN_FAILURE');
define('SECURITY_RATE_LIMIT_EXCEEDED', 'RATE_LIMIT_EXCEEDED');
define('SECURITY_ACCESS_DENIED', 'ACCESS_DENIED');
define('SECURITY_DATA_MODIFICATION', 'DATA_MODIFICATION');

// Define rate limiting constants
define('RATE_LIMIT_LOGIN_REQUESTS', 5);
define('RATE_LIMIT_LOGIN_WINDOW', 300);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

// Define password constants
define('PASSWORD_MIN_LENGTH', 8);

// Temporary InputValidator class
class InputValidator {
    private $data;
    private $errors = [];
    
    public function __construct(array $data = null) {
        $this->data = $data ?? $_REQUEST;
    }
    
    public function validate(string $field, string $type, array $options = []): self {
        $value = $this->data[$field] ?? null;
        
        if ($value === null || $value === '') {
            if ($options['required'] ?? false) {
                $this->errors[$field] = "Field {$field} is required";
            }
            return $this;
        }
        
        switch ($type) {
            case 'email':
                $this->validateEmail($field, $value, $options);
                break;
                
            case 'string':
                $this->validateString($field, $value, $options);
                break;
                
            case 'int':
                $this->validateInt($field, $value, $options);
                break;
                
            case 'date':
                $this->validateDate($field, $value, $options);
                break;
        }
        
        return $this;
    }
    
    private function validateEmail(string $field, string $value, array $options): void {
        $email = validate_email_address($value);
        
        if ($email === null) {
            $this->errors[$field] = "Invalid email address";
            return;
        }
        
        $this->data[$field] = $email;
    }
    
    private function validateString(string $field, string $value, array $options): void {
        $minLength = $options['min_length'] ?? 1;
        $maxLength = $options['max_length'] ?? 255;
        
        if (strlen($value) < $minLength) {
            $this->errors[$field] = "Minimum length is {$minLength} characters";
            return;
        }
        
        if (strlen($value) > $maxLength) {
            $this->errors[$field] = "Maximum length is {$maxLength} characters";
            return;
        }
        
        $this->data[$field] = trim($value);
    }
    
    private function validateInt(string $field, string $value, array $options): void {
        $min = $options['min'] ?? 0;
        $max = $options['max'] ?? PHP_INT_MAX;
        
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field] = "Must be a valid integer";
            return;
        }
        
        $intValue = (int) $value;
        
        if ($intValue < $min) {
            $this->errors[$field] = "Minimum value is {$min}";
            return;
        }
        
        if ($intValue > $max) {
            $this->errors[$field] = "Maximum value is {$max}";
            return;
        }
        
        $this->data[$field] = $intValue;
    }
    
    private function validateDate(string $field, string $value, array $options): void {
        $date = validate_date_string($value);
        
        if ($date === null) {
            $this->errors[$field] = "Invalid date format (YYYY-MM-DD)";
            return;
        }
        
        $this->data[$field] = $date;
    }
    
    public function hasErrors(): bool {
        return !empty($this->errors);
    }
    
    public function getErrors(): array {
        return $this->errors;
    }
    
    public function getValidatedData(): array {
        return $this->data;
    }
}

// Temporary SecurePassword class
class SecurePassword {
    public static function verify(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    public static function hash(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function validateStrength(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain lowercase letters';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain uppercase letters';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain numbers';
        }
        
        return $errors;
    }
}

// Additional helper functions for action files
function user_owns_resource(int $resource_id, string $resource_type, int $user_id): bool
{
    global $pdo;
    
    try {
        switch ($resource_type) {
            case 'email':
                $stmt = $pdo->prepare("SELECT id FROM emails WHERE id = ? AND user_id = ?");
                break;
            default:
                return false;
        }
        
        $stmt->execute([$resource_id, $user_id]);
        return $stmt->fetch() !== false;
        
    } catch(PDOException $e) {
        error_log("Resource ownership check failed: " . $e->getMessage());
        return false;
    }
}

function log_data_modification(string $action, string $table, int $record_id, array $old_data = null, array $new_data = null): void
{
    $context = [
        'action' => $action,
        'table' => $table,
        'record_id' => $record_id
    ];
    
    if ($old_data !== null) {
        $context['old_data'] = $old_data;
    }
    
    if ($new_data !== null) {
        $context['new_data'] = $new_data;
    }
    
    log_security_event(
        SECURITY_DATA_MODIFICATION,
        "Data modification: {$action} on {$table} #{$record_id}",
        $context
    );
}

secure_session_start();
send_security_headers();
