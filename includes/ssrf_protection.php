<?php
/**
 * Server-Side Request Forgery (SSRF) Protection
 * Implements OWASP A10: Server-Side Request Forgery protections
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/logging.php';

/**
 * SSRF Protection Class
 */
class SSRFProtection {
    // Allowed domains for outbound requests
    private static $allowed_domains = [
        'localhost',
        '127.0.0.1',
        'api.example.com' // Add your allowed external APIs here
    ];
    
    // Blocked private IP ranges
    private static $blocked_ranges = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '224.0.0.0/4',
        '240.0.0.0/4'
    ];
    
    // Allowed ports
    private static $allowed_ports = [80, 443, 8080, 8443];
    
    // Blocked URL schemes
    private static $blocked_schemes = [
        'file://',
        'ftp://',
        'gopher://',
        'dict://',
        'ldap://',
        'tftp://',
        'data://'
    ];
    
    /**
     * Validate and sanitize URL for SSRF protection
     */
    public static function validateUrl(string $url): array {
        $errors = [];
        
        // Check for blocked schemes
        foreach (self::$blocked_schemes as $scheme) {
            if (stripos($url, $scheme) === 0) {
                $errors[] = "URL scheme not allowed: {$scheme}";
                log_security_event(
                    SECURITY_SUSPICIOUS_ACTIVITY,
                    "Blocked URL scheme detected",
                    ['url' => $url, 'scheme' => $scheme]
                );
                return $errors;
            }
        }
        
        // Parse URL
        $parsed = parse_url($url);
        if (!$parsed) {
            $errors[] = "Invalid URL format";
            return $errors;
        }
        
        // Check scheme
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            $errors[] = "Only HTTP and HTTPS schemes are allowed";
        }
        
        // Check host
        if (!isset($parsed['host'])) {
            $errors[] = "URL must contain a valid host";
            return $errors;
        }
        
        $host = $parsed['host'];
        
        // Check if host is an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (self::isPrivateIP($host)) {
                $errors[] = "Private IP addresses are not allowed";
                log_security_event(
                    SECURITY_SUSPICIOUS_ACTIVITY,
                    "Private IP access attempt",
                    ['url' => $url, 'ip' => $host]
                );
            }
        } else {
            // Check domain against allowed list
            if (!self::isAllowedDomain($host)) {
                $errors[] = "Domain not allowed: {$host}";
                log_security_event(
                    SECURITY_SUSPICIOUS_ACTIVITY,
                    "Blocked domain access attempt",
                    ['url' => $url, 'domain' => $host]
                );
            }
        }
        
        // Check port
        if (isset($parsed['port']) && !in_array($parsed['port'], self::$allowed_ports)) {
            $errors[] = "Port not allowed: {$parsed['port']}";
        }
        
        // Check for URL redirection attempts
        if (isset($parsed['path']) && strpos($parsed['path'], '@') !== false) {
            $errors[] = "URL contains suspicious characters";
        }
        
        return $errors;
    }
    
    /**
     * Check if IP is in private range
     */
    private static function isPrivateIP(string $ip): bool {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        $ip_long = ip2long($ip);
        
        foreach (self::$blocked_ranges as $range) {
            [$subnet, $mask] = explode('/', $range);
            $subnet_long = ip2long($subnet);
            $mask_long = -1 << (32 - $mask);
            
            if (($ip_long & $mask_long) === ($subnet_long & $mask_long)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if domain is allowed
     */
    private static function isAllowedDomain(string $domain): bool {
        // Check exact match
        if (in_array($domain, self::$allowed_domains)) {
            return true;
        }
        
        // Check subdomains
        foreach (self::$allowed_domains as $allowed) {
            if (strpos($domain, '.' . $allowed) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Make secure HTTP request
     */
    public static function makeRequest(string $url, array $options = []): ?array {
        $errors = self::validateUrl($url);
        if (!empty($errors)) {
            throw new InvalidArgumentException("Invalid URL: " . implode(', ', $errors));
        }
        
        $default_options = [
            'timeout' => 10,
            'max_redirects' => 3,
            'user_agent' => 'MailTrackSystem/1.0',
            'follow_redirects' => true,
            'verify_ssl' => true
        ];
        
        $options = array_merge($default_options, $options);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => $options['timeout'],
                'user_agent' => $options['user_agent'],
                'follow_location' => $options['follow_redirects'],
                'max_redirects' => $options['max_redirects'],
                'header' => [
                    'Accept: application/json',
                    'Connection: close'
                ]
            ],
            'ssl' => [
                'verify_peer' => $options['verify_ssl'],
                'verify_peer_name' => $options['verify_ssl'],
                'allow_self_signed' => false
            ]
        ]);
        
        $start_time = microtime(true);
        
        try {
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                $error = error_get_last();
                throw new RuntimeException("Request failed: " . ($error['message'] ?? 'Unknown error'));
            }
            
            $duration = microtime(true) - $start_time;
            
            // Log successful request
            log_security_event(
                SECURITY_DATA_MODIFICATION,
                "External API request completed",
                [
                    'url' => $url,
                    'duration' => round($duration, 2),
                    'response_size' => strlen($response)
                ]
            );
            
            return [
                'body' => $response,
                'headers' => $http_response_header ?? [],
                'status_code' => self::getStatusCodeFromHeaders($http_response_header ?? []),
                'duration' => $duration
            ];
            
        } catch (Exception $e) {
            $duration = microtime(true) - $start_time;
            
            log_security_event(
                SECURITY_SUSPICIOUS_ACTIVITY,
                "External API request failed",
                [
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'duration' => round($duration, 2)
                ]
            );
            
            throw $e;
        }
    }
    
    /**
     * Extract status code from headers
     */
    private static function getStatusCodeFromHeaders(array $headers): int {
        if (empty($headers)) {
            return 0;
        }
        
        $status_line = $headers[0] ?? '';
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $status_line, $matches)) {
            return (int) $matches[1];
        }
        
        return 0;
    }
    
    /**
     * Validate file download URL
     */
    public static function validateDownloadUrl(string $url): array {
        $errors = self::validateUrl($url);
        
        if (!empty($errors)) {
            return $errors;
        }
        
        $parsed = parse_url($url);
        
        // Check file extension
        if (isset($parsed['path'])) {
            $extension = strtolower(pathinfo($parsed['path'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'csv'];
            
            if (!in_array($extension, $allowed_extensions)) {
                $errors[] = "File extension not allowed: {$extension}";
            }
        }
        
        // Check content length if available
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
            if (isset($query['size']) && (int) $query['size'] > 10485760) { // 10MB
                $errors[] = "File size too large";
            }
        }
        
        return $errors;
    }
    
    /**
     * Add allowed domain
     */
    public static function addAllowedDomain(string $domain): void {
        if (!in_array($domain, self::$allowed_domains)) {
            self::$allowed_domains[] = $domain;
        }
    }
    
    /**
     * Add allowed port
     */
    public static function addAllowedPort(int $port): void {
        if (!in_array($port, self::$allowed_ports)) {
            self::$allowed_ports[] = $port;
        }
    }
}

/**
 * Secure cURL wrapper
 */
function secure_curl_request(string $url, array $options = []): ?array {
    $errors = SSRFProtection::validateUrl($url);
    if (!empty($errors)) {
        throw new InvalidArgumentException("Invalid URL: " . implode(', ', $errors));
    }
    
    $default_options = [
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_USERAGENT => 'MailTrackSystem/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, $default_options);
    curl_setopt_array($ch, $options);
    
    $start_time = microtime(true);
    
    try {
        $response = curl_exec($ch);
        
        if ($response === false) {
            throw new RuntimeException("cURL request failed: " . curl_error($ch));
        }
        
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $duration = microtime(true) - $start_time;
        
        curl_close($ch);
        
        log_security_event(
            SECURITY_DATA_MODIFICATION,
            "cURL request completed",
            [
                'url' => $url,
                'status_code' => $status_code,
                'duration' => round($duration, 2),
                'response_size' => strlen($body)
            ]
        );
        
        return [
            'body' => $body,
            'headers' => $headers,
            'status_code' => $status_code,
            'duration' => $duration
        ];
        
    } catch (Exception $e) {
        $duration = microtime(true) - $start_time;
        
        log_security_event(
            SECURITY_SUSPICIOUS_ACTIVITY,
            "cURL request failed",
            [
                'url' => $url,
                'error' => $e->getMessage(),
                'duration' => round($duration, 2)
            ]
        );
        
        curl_close($ch);
        throw $e;
    }
}

/**
 * Validate webhook URL
 */
function validate_webhook_url(string $url): array {
    $errors = SSRFProtection::validateUrl($url);
    
    if (!empty($errors)) {
        return $errors;
    }
    
    $parsed = parse_url($url);
    
    // Webhooks should use HTTPS
    if ($parsed['scheme'] !== 'https') {
        $errors[] = "Webhooks must use HTTPS";
    }
    
    // Check for reasonable timeout
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $query);
        if (isset($query['timeout']) && ((int) $query['timeout'] > 30 || (int) $query['timeout'] < 1)) {
            $errors[] = "Webhook timeout must be between 1 and 30 seconds";
        }
    }
    
    return $errors;
}
?>
