<?php
/**
 * Cryptographic Functions
 * Implements OWASP A02: Cryptographic Failures protections
 */

require_once __DIR__ . '/security.php';

/**
 * Secure encryption/decryption class
 */
class SecureCrypto {
    private static $method = 'aes-256-gcm';
    private static $key_length = 32;
    private static $iv_length = 12;
    private static $tag_length = 16;
    
    /**
     * Get or generate encryption key
     */
    private static function getKey(): string
    {
        $key_file = __DIR__ . '/../.encryption_key';
        
        if (file_exists($key_file)) {
            $key = file_get_contents($key_file);
            if ($key !== false) {
                return $key;
            }
        }
        
        // Generate new key (should be done securely in production)
        $key = random_bytes(self::$key_length);
        $result = file_put_contents($key_file, $key, 0600);
        
        if ($result === false) {
            throw new RuntimeException('Failed to create encryption key file');
        }
        
        return $key;
    }
    
    /**
     * Encrypt data
     */
    public static function encrypt(string $data): string {
        $key = self::getKey();
        $iv = random_bytes(self::$iv_length);
        $tag = '';
        
        $encrypted = openssl_encrypt(
            $data,
            self::$method,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::$tag_length
        );
        
        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed');
        }
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public static function decrypt(string $encrypted): string {
        $key = self::getKey();
        $data = base64_decode($encrypted);
        
        if (strlen($data) < self::$iv_length + self::$tag_length) {
            throw new RuntimeException('Invalid encrypted data');
        }
        
        $iv = substr($data, 0, self::$iv_length);
        $tag = substr($data, self::$iv_length, self::$tag_length);
        $encrypted_data = substr($data, self::$iv_length + self::$tag_length);
        
        $decrypted = openssl_decrypt(
            $encrypted_data,
            self::$method,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed');
        }
        
        return $decrypted;
    }
    
    /**
     * Generate secure hash
     */
    public static function hash(string $data, string $salt = null): string {
        if ($salt === null) {
            $salt = random_bytes(16);
        }
        
        return hash('sha256', $data . $salt) . ':' . base64_encode($salt);
    }
    
    /**
     * Verify hash
     */
    public static function verifyHash(string $data, string $hash): bool {
        [$hashed, $salt] = explode(':', $hash);
        return hash_equals($hashed, hash('sha256', $data . base64_decode($salt)));
    }
}

/**
 * Secure password handling
 */
class SecurePassword {
    private static $algorithm = PASSWORD_ARGON2ID;
    private static $options = [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ];
    
    /**
     * Hash password securely
     */
    public static function hash(string $password): string {
        return password_hash($password, self::$algorithm, self::$options);
    }
    
    /**
     * Verify password
     */
    public static function verify(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public static function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, self::$algorithm, self::$options);
    }
    
    /**
     * Generate secure random password
     */
    public static function generate(int $length = 12): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Validate password strength
     */
    public static function validateStrength(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        
        if (strlen($password) > 128) {
            $errors[] = 'Password must be less than 128 characters';
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
        
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]/', $password)) {
            $errors[] = 'Password must contain special characters';
        }
        
        // Check for common patterns
        $common_patterns = [
            '/^(.)\1{2,}$/', // Repeated characters
            '/^(123|abc|qwe)/i', // Sequential patterns
            '/(password|admin|user|login)/i' // Common words
        ];
        
        foreach ($common_patterns as $pattern) {
            if (preg_match($pattern, $password)) {
                $errors[] = 'Password contains common patterns';
                break;
            }
        }
        
        return $errors;
    }
}

/**
 * Secure token generation and validation
 */
class SecureToken {
    /**
     * Generate JWT-like token
     */
    public static function generate(array $payload, int $expires_in = 3600): string {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $expires_in;
        $payload['jti'] = bin2hex(random_bytes(16));
        
        $header_encoded = base64url_encode(json_encode($header));
        $payload_encoded = base64url_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, self::getSecret(), true);
        $signature_encoded = base64url_encode($signature);
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature_encoded;
    }
    
    /**
     * Validate token
     */
    public static function validate(string $token): ?array {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        [$header_encoded, $payload_encoded, $signature_encoded] = $parts;
        
        // Verify signature
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, self::getSecret(), true);
        $expected_signature = base64url_encode($signature);
        
        if (!hash_equals($signature_encoded, $expected_signature)) {
            return null;
        }
        
        // Decode payload
        $payload = json_decode(base64url_decode($payload_encoded), true);
        
        if (!$payload) {
            return null;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Get secret key
     */
    private static function getSecret(): string
    {
        $secret_file = __DIR__ . '/../.jwt_secret';
        
        if (file_exists($secret_file)) {
            $secret = file_get_contents($secret_file);
            if ($secret !== false) {
                return $secret;
            }
        }
        
        $secret = random_bytes(64);
        $result = file_put_contents($secret_file, $secret, 0600);
        
        if ($result === false) {
            throw new RuntimeException('Failed to create JWT secret file');
        }
        
        return $secret;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRF(): string {
        if (empty($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$token] = time();
        
        // Keep only last 10 tokens
        if (count($_SESSION['csrf_tokens']) > 10) {
            $_SESSION['csrf_tokens'] = array_slice($_SESSION['csrf_tokens'], -10, null, true);
        }
        
        return $token;
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRF(string $token): bool {
        if (empty($_SESSION['csrf_tokens'][$token])) {
            return false;
        }
        
        // Check if token is not too old (1 hour)
        if (time() - $_SESSION['csrf_tokens'][$token] > 3600) {
            unset($_SESSION['csrf_tokens'][$token]);
            return false;
        }
        
        // Use once
        unset($_SESSION['csrf_tokens'][$token]);
        return true;
    }
}

/**
 * Helper functions for base64url encoding/decoding
 */
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

/**
 * Secure random number generation
 */
function secure_random_int(int $min, int $max): int {
    return random_int($min, $max);
}

/**
 * Generate UUID v4
 */
function generate_uuid(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0x0fff) | 0x4000,
        random_int(0, 0x3fff) | 0x8000,
        random_int(0, 0xffff),
        random_int(0, 0xffff),
        random_int(0, 0xffff)
    );
}

/**
 * Constant-time string comparison
 */
function secure_compare(string $a, string $b): bool {
    return hash_equals($a, $b);
}
?>
