<?php
/**
 * Access Control and Authorization Functions
 * Implements OWASP A01: Broken Access Control protections
 */

require_once __DIR__ . '/security.php';

/**
 * Rate limiting for API endpoints
 */
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

/**
 * Check if user owns the resource
 */
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
        
    } catch (PDOException $e) {
        error_log("Resource ownership check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate resource access with proper authorization
 */
function require_resource_access(int $resource_id, string $resource_type): void
{
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        http_response_code(401);
        die('Authentication required');
    }
    
    if (!user_owns_resource($resource_id, $resource_type, $user_id)) {
        http_response_code(403);
        die('Access denied');
    }
}

/**
 * IP-based access control
 */
function is_ip_allowed(string $ip = null): bool
{
    $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Block private IPs in production (adjust as needed)
    $private_ranges = [
        '10.0.0.0/8',
        '172.16.0.0/12', 
        '192.168.0.0/16',
        '127.0.0.0/8'
    ];
    
    // Uncomment to block private IPs in production
    // foreach ($private_ranges as $range) {
    //     if (ip_in_range($ip, $range)) {
    //         return false;
    //     }
    // }
    
    return true;
}

/**
 * Check if IP is in CIDR range
 */
function ip_in_range(string $ip, string $range): bool
{
    [$subnet, $mask] = explode('/', $range);
    $ip_long = ip2long($ip);
    $subnet_long = ip2long($subnet);
    $mask_long = -1 << (32 - $mask);
    
    return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
}

/**
 * Validate file access permissions
 */
function validate_file_access(string $file_path): bool
{
    $real_path = realpath($file_path);
    $base_path = realpath(__DIR__ . '/..');
    
    if ($real_path === false || strpos($real_path, $base_path) !== 0) {
        return false;
    }
    
    // Prevent access to sensitive files
    $forbidden_patterns = [
        '/config/',
        '/.env',
        '/.htaccess',
        '/composer.json',
        '/composer.lock'
    ];
    
    foreach ($forbidden_patterns as $pattern) {
        if (strpos($real_path, $pattern) !== false) {
            return false;
        }
    }
    
    return true;
}

/**
 * HTTP method validation
 */
function validate_http_method(array $allowed_methods): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    
    if (!in_array($method, $allowed_methods)) {
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowed_methods));
        die('Method not allowed');
    }
}

/**
 * Content-Type validation for API endpoints
 */
function validate_content_type(string $expected_type = 'application/json'): void
{
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, $expected_type) === false) {
        http_response_code(415);
        die('Unsupported media type');
    }
}

/**
 * Secure file upload validation
 */
function validate_file_upload(array $file): array
{
    $errors = [];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error';
    }
    
    if ($file['size'] > $max_size) {
        $errors[] = 'File too large';
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = 'File type not allowed';
    }
    
    // Validate file extension matches MIME type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    
    if (!in_array($extension, $allowed_extensions)) {
        $errors[] = 'File extension not allowed';
    }
    
    return $errors;
}
?>
