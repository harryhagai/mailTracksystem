<?php
/**
 * Security Logging and Monitoring
 * Implements OWASP A09: Security Logging and Monitoring Failures protections
 */

require_once __DIR__ . '/security.php';

/**
 * Security event types
 */
define('SECURITY_LOGIN_SUCCESS', 'LOGIN_SUCCESS');
define('SECURITY_LOGIN_FAILURE', 'LOGIN_FAILURE');
define('SECURITY_LOGOUT', 'LOGOUT');
define('SECURITY_ACCESS_DENIED', 'ACCESS_DENIED');
define('SECURITY_CSRF_FAILURE', 'CSRF_FAILURE');
define('SECURITY_RATE_LIMIT_EXCEEDED', 'RATE_LIMIT_EXCEEDED');
define('SECURITY_SUSPICIOUS_ACTIVITY', 'SUSPICIOUS_ACTIVITY');
define('SECURITY_DATA_MODIFICATION', 'DATA_MODIFICATION');
define('SECURITY_FILE_UPLOAD', 'FILE_UPLOAD');
define('SECURITY_SQL_INJECTION_ATTEMPT', 'SQL_INJECTION_ATTEMPT');
define('SECURITY_XSS_ATTEMPT', 'XSS_ATTEMPT');

/**
 * Log security events
 */
function log_security_event(string $event_type, string $message, array $context = []): void
{
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => $event_type,
        'message' => $message,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'session_id' => session_id(),
        'context' => $context
    ];
    
    $log_message = json_encode($log_entry);
    
    // Log to security-specific file
    $log_file = __DIR__ . '/../logs/security.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    error_log($log_message . PHP_EOL, 3, $log_file);
    
    // Also log to system error log for critical events
    if (in_array($event_type, [
        SECURITY_LOGIN_FAILURE,
        SECURITY_ACCESS_DENIED,
        SECURITY_SUSPICIOUS_ACTIVITY,
        SECURITY_SQL_INJECTION_ATTEMPT,
        SECURITY_XSS_ATTEMPT
    ])) {
        error_log("SECURITY ALERT [{$event_type}]: {$message}");
    }
}

/**
 * Detect suspicious patterns in input
 */
function detect_suspicious_input(string $input): array
{
    $patterns = [
        'sql_injection' => [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|EXEC|ALTER|CREATE)\b)/i',
            '/(--|#|\/\*|\*\/)/',
            '/(\bOR\b.*=.*\bOR\b)/i',
            '/(\bAND\b.*=.*\bAND\b)/i'
        ],
        'xss' => [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>/i'
        ],
        'path_traversal' => [
            '/\.\.[\/\\]/',
            '/%2e%2e[\/\\]/i'
        ],
        'command_injection' => [
            '/[;&|`$(){}[\]]/',
            '/\b(curl|wget|nc|netcat|ssh|ftp|telnet)\b/i'
        ]
    ];
    
    $detections = [];
    
    foreach ($patterns as $type => $pattern_list) {
        foreach ($pattern_list as $pattern) {
            if (preg_match($pattern, $input)) {
                $detections[] = $type;
                break;
            }
        }
    }
    
    return array_unique($detections);
}

/**
 * Monitor and log suspicious requests
 */
function monitor_request(): void
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Check for suspicious patterns in URI
    $suspicious_patterns = detect_suspicious_input($uri);
    if (!empty($suspicious_patterns)) {
        log_security_event(
            SECURITY_SUSPICIOUS_ACTIVITY,
            "Suspicious patterns detected in URI: " . implode(', ', $suspicious_patterns),
            ['uri' => $uri, 'method' => $method]
        );
    }
    
    // Check for missing or suspicious User-Agent
    if (empty($user_agent) || strlen($user_agent) > 500) {
        log_security_event(
            SECURITY_SUSPICIOUS_ACTIVITY,
            "Suspicious User-Agent detected",
            ['user_agent' => $user_agent]
        );
    }
    
    // Monitor rapid requests
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!rate_limit($client_ip, 30, 60)) {
        log_security_event(
            SECURITY_RATE_LIMIT_EXCEEDED,
            "Rate limit exceeded",
            ['ip' => $client_ip]
        );
    }
}

/**
 * Log data modifications
 */
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

/**
 * Get security statistics
 */
function get_security_stats(): array
{
    $log_file = __DIR__ . '/../logs/security.log';
    
    if (!file_exists($log_file)) {
        return [
            'total_events' => 0,
            'login_attempts' => 0,
            'failures' => 0,
            'suspicious_activities' => 0,
            'last_24h' => 0
        ];
    }
    
    $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($logs === false) {
        return [
            'total_events' => 0,
            'login_attempts' => 0,
            'failures' => 0,
            'suspicious_activities' => 0,
            'last_24h' => 0
        ];
    }
    
    $stats = [
        'total_events' => count($logs),
        'login_attempts' => 0,
        'failures' => 0,
        'suspicious_activities' => 0,
        'last_24h' => 0
    ];
    
    $yesterday = time() - 86400;
    
    foreach ($logs as $log_line) {
        $entry = json_decode($log_line, true);
        
        if (!$entry) continue;
        
        $timestamp = strtotime($entry['timestamp']);
        if ($timestamp > $yesterday) {
            $stats['last_24h']++;
        }
        
        switch ($entry['event_type']) {
            case SECURITY_LOGIN_SUCCESS:
            case SECURITY_LOGIN_FAILURE:
                $stats['login_attempts']++;
                if ($entry['event_type'] === SECURITY_LOGIN_FAILURE) {
                    $stats['failures']++;
                }
                break;
                
            case SECURITY_SUSPICIOUS_ACTIVITY:
            case SECURITY_ACCESS_DENIED:
            case SECURITY_CSRF_FAILURE:
                $stats['suspicious_activities']++;
                break;
        }
    }
    
    return $stats;
}

/**
 * Alert on critical security events
 */
function alert_security_event(string $event_type, string $message): void
{
    // This could be extended to send emails, SMS, or integrate with SIEM systems
    $critical_events = [
        SECURITY_SQL_INJECTION_ATTEMPT,
        SECURITY_XSS_ATTEMPT,
        SECURITY_RATE_LIMIT_EXCEEDED
    ];
    
    if (in_array($event_type, $critical_events)) {
        // Log with higher priority
        error_log("CRITICAL SECURITY ALERT [{$event_type}]: {$message}");
        
        // Could also send email notification here
        // mail_admin("Security Alert", $message);
    }
}

// Initialize request monitoring
monitor_request();
?>
