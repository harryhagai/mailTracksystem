<?php
/**
 * Security Configuration
 * Implements OWASP A05: Security Misconfiguration protections
 */

// Security headers configuration
define('SECURITY_HEADERS_ENABLED', true);
define('STRICT_TRANSPORT_SECURITY', 'max-age=31536000; includeSubDomains; preload');
define('CONTENT_SECURITY_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';");
define('X_FRAME_OPTIONS', 'DENY');
define('X_CONTENT_TYPE_OPTIONS', 'nosniff');
define('REFERRER_POLICY', 'strict-origin-when-cross-origin');
define('PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=(), payment=()');

// Session security configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_REGENERATE_INTERVAL', 300); // 5 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Password policy
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 128);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// File upload security
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain']);
define('ALLOWED_FILE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt']);

// Rate limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 60); // seconds
define('RATE_LIMIT_LOGIN_REQUESTS', 5);
define('RATE_LIMIT_LOGIN_WINDOW', 300); // 5 minutes

// Logging configuration
define('SECURITY_LOG_ENABLED', true);
define('SECURITY_LOG_FILE', __DIR__ . '/../logs/security.log');
define('SECURITY_LOG_MAX_SIZE', 10485760); // 10MB
define('SECURITY_LOG_ROTATION', true);

// Database security
define('DB_SSL_ENABLED', false); // Enable in production
define('DB_SSL_CA', null);
define('DB_SSL_CERT', null);
define('DB_SSL_KEY', null);

// API security
define('API_RATE_LIMIT', 60);
define('API_RATE_LIMIT_WINDOW', 60);
define('API_TOKEN_EXPIRY', 3600);

// Development vs Production
define('ENVIRONMENT', 'development'); // Change to 'production' in production
define('DEBUG_MODE', false);
define('ERROR_REPORTING', ENVIRONMENT === 'development');

// Security features
define('ENABLE_2FA', false); // Future feature
define('ENABLE_IP_WHITELIST', false);
define('ENABLE_DEVICE_FINGERPRINTING', false);

// Allowed IP ranges (if whitelist enabled)
define('ALLOWED_IP_RANGES', [
    // '192.168.1.0/24',
    // '10.0.0.0/8'
]);

// Blocked user agents (bots, scrapers, etc.)
define('BLOCKED_USER_AGENTS', [
    'curl',
    'wget',
    'python-requests',
    'bot',
    'crawler',
    'spider',
    'scraper'
]);

// Sensitive file patterns to protect
define('PROTECTED_FILE_PATTERNS', [
    '/\.(env|log|key|pem|crt|p12)$/',
    '/(config|database|secret|private)\./i',
    '/\.(sql|backup|dump)$/'
]);

// Security monitoring
define('ENABLE_SECURITY_MONITORING', true);
define('ALERT_ON_SUSPICIOUS_ACTIVITY', true);
define('ALERT_EMAIL', ''); // Set to admin email for alerts

// Cookie security
define('COOKIE_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Strict');

// CORS configuration (if needed)
define('CORS_ENABLED', false);
define('CORS_ALLOWED_ORIGINS', []);
define('CORS_ALLOWED_METHODS', ['GET', 'POST', 'PUT', 'DELETE']);
define('CORS_ALLOWED_HEADERS', ['Content-Type', 'Authorization']);

// Input validation limits
define('MAX_INPUT_LENGTH', 10000);
define('MAX_FIELD_NAME_LENGTH', 100);
define('MAX_COOKIE_SIZE', 4096);

// Database query limits
define('MAX_QUERY_RESULTS', 1000);
define('QUERY_TIMEOUT', 30); // seconds

// SSL/TLS configuration
define('TLS_MIN_VERSION', '1.2');
define('TLS_CIPHERS', 'ECDHE+AESGCM:ECDHE+CHACHA20:DHE+AESGCM:DHE+CHACHA20:!aNULL:!MD5:!DSS');

// Backup security
define('BACKUP_ENCRYPTION_ENABLED', true);
define('BACKUP_RETENTION_DAYS', 30);

// Session fixation protection
define('SESSION_FIXATION_PROTECTION', true);

// Clickjacking protection
define('CLICKJACKING_PROTECTION', true);

// Content Security Policy
define('CSP_ENABLED', true);
define('CSP_REPORT_ONLY', false);

/**
 * Apply security configuration
 */
function apply_security_config(): void {
    // Set error reporting
    if (ERROR_REPORTING) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
    }
    
    // Set secure session settings
    ini_set('session.cookie_httponly', COOKIE_HTTPONLY ? '1' : '0');
    ini_set('session.cookie_secure', COOKIE_SECURE ? '1' : '0');
    ini_set('session.cookie_samesite', COOKIE_SAMESITE);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    
    // Set upload limits
    ini_set('upload_max_filesize', MAX_FILE_SIZE);
    ini_set('post_max_size', MAX_FILE_SIZE * 2);
    ini_set('max_input_vars', 3000);
    ini_set('max_execution_time', 30);
    ini_set('memory_limit', '256M');
    
    // Database settings
    ini_set('mysql.connect_timeout', 10);
    ini_set('default_socket_timeout', 30);
    
    // Security headers
    if (SECURITY_HEADERS_ENABLED && !headers_sent()) {
        header('Strict-Transport-Security: ' . STRICT_TRANSPORT_SECURITY);
        header('X-Frame-Options: ' . X_FRAME_OPTIONS);
        header('X-Content-Type-Options: ' . X_CONTENT_TYPE_OPTIONS);
        header('Referrer-Policy: ' . REFERRER_POLICY);
        header('Permissions-Policy: ' . PERMISSIONS_POLICY);
        
        if (CSP_ENABLED) {
            header('Content-Security-Policy: ' . CONTENT_SECURITY_POLICY);
        }
    }
}

/**
 * Check if request is from blocked user agent
 */
function is_blocked_user_agent(): bool {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    foreach (BLOCKED_USER_AGENTS as $blocked) {
        if (stripos($user_agent, $blocked) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Validate environment configuration
 */
function validate_security_config(): array {
    $errors = [];
    
    // Check for exposed configuration files
    $sensitive_files = [
        __DIR__ . '/../.env',
        __DIR__ . '/../.encryption_key',
        __DIR__ . '/../.jwt_secret'
    ];
    
    foreach ($sensitive_files as $file) {
        if (file_exists($file)) {
            $perms = fileperms($file);
            if ($perms && ($perms & 0x0044)) { // Readable by group or others
                $errors[] = "Sensitive file has insecure permissions: {$file}";
            }
        }
    }
    
    // Check for debug mode in production
    if (ENVIRONMENT === 'production' && DEBUG_MODE) {
        $errors[] = "Debug mode should not be enabled in production";
    }
    
    // Check for weak session settings
    if (ini_get('session.cookie_httponly') !== '1') {
        $errors[] = "Session cookies should be HTTP-only";
    }
    
    if (ini_get('session.use_only_cookies') !== '1') {
        $errors[] = "Session should use only cookies";
    }
    
    return $errors;
}

// Apply security configuration
apply_security_config();
?>
