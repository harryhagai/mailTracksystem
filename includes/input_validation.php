<?php
/**
 * Input Validation and Sanitization
 * Implements OWASP A03: Injection protections
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/logging.php';

/**
 * Comprehensive input validation
 */
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
                
            case 'url':
                $this->validateUrl($field, $value, $options);
                break;
                
            case 'text':
                $this->validateText($field, $value, $options);
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
        
        // Check for suspicious patterns
        $suspicious = detect_suspicious_input($value);
        if (!empty($suspicious)) {
            log_security_event(
                SECURITY_XSS_ATTEMPT,
                "Suspicious patterns in email field",
                ['field' => $field, 'value' => $value, 'patterns' => $suspicious]
            );
            $this->errors[$field] = "Invalid input detected";
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
        
        // Check for suspicious patterns
        $suspicious = detect_suspicious_input($value);
        if (!empty($suspicious)) {
            log_security_event(
                SECURITY_XSS_ATTEMPT,
                "Suspicious patterns in string field",
                ['field' => $field, 'value' => $value, 'patterns' => $suspicious]
            );
            $this->errors[$field] = "Invalid input detected";
            return;
        }
        
        // Sanitize string
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
    
    private function validateUrl(string $field, string $value, array $options): void {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "Invalid URL";
            return;
        }
        
        // Check for dangerous protocols
        $dangerous_protocols = ['javascript:', 'data:', 'vbscript:', 'file:'];
        foreach ($dangerous_protocols as $protocol) {
            if (stripos($value, $protocol) === 0) {
                $this->errors[$field] = "URL protocol not allowed";
                return;
            }
        }
        
        $this->data[$field] = $value;
    }
    
    private function validateText(string $field, string $value, array $options): void {
        $maxLength = $options['max_length'] ?? 1000;
        
        if (strlen($value) > $maxLength) {
            $this->errors[$field] = "Text too long (max {$maxLength} characters)";
            return;
        }
        
        // Allow more characters in text fields but still check for injection
        $suspicious = detect_suspicious_input($value);
        if (!empty($suspicious)) {
            log_security_event(
                SECURITY_XSS_ATTEMPT,
                "Suspicious patterns in text field",
                ['field' => $field, 'patterns' => $suspicious]
            );
            $this->errors[$field] = "Invalid input detected";
            return;
        }
        
        // Strip potentially dangerous HTML if allowed
        if ($options['allow_html'] ?? false) {
            $this->data[$field] = strip_tags($value, $options['allowed_tags'] ?? '<p><br><strong><em>');
        } else {
            $this->data[$field] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
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
    
    public function get(string $field, $default = null) {
        return $this->data[$field] ?? $default;
    }
}

/**
 * SQL Injection protection for raw queries
 */
function sanitize_sql_identifier(string $identifier): string {
    // Only allow alphanumeric characters and underscores
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
        throw new InvalidArgumentException("Invalid SQL identifier: {$identifier}");
    }
    return $identifier;
}

/**
 * Validate and sanitize file uploads
 */
function validate_uploaded_file(array $file, array $allowed_types = [], int $max_size = 5242880): array {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
        return $errors;
    }
    
    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds maximum allowed';
    }
    
    // Validate MIME type
    if (!empty($allowed_types)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($detected_type, $allowed_types)) {
            $errors[] = 'File type not allowed';
        }
    }
    
    // Validate file extension
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_extensions)) {
        $errors[] = 'File extension not allowed';
    }
    
    // Check for double extensions
    if (strpos($file['name'], '.') !== strrpos($file['name'], '.')) {
        $errors[] = 'Double file extensions not allowed';
    }
    
    return $errors;
}

/**
 * Generate secure random tokens
 */
function generate_secure_token(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Validate API key format
 */
function validate_api_key(string $key): bool {
    return preg_match('/^[a-zA-Z0-9]{32,64}$/', $key) === 1;
}

/**
 * Sanitize output for different contexts
 */
function sanitize_output(string $value, string $context = 'html'): string {
    switch ($context) {
        case 'html':
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            
        case 'js':
            return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            
        case 'css':
            // Basic CSS sanitization
            return preg_replace('/[<>"\'\\\\]/', '', $value);
            
        case 'url':
            return urlencode($value);
            
        default:
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/**
 * Validate JSON input
 */
function validate_json_input(string $json): ?array {
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    // Check for dangerous content
    $json_string = json_encode($data);
    $suspicious = detect_suspicious_input($json_string);
    
    if (!empty($suspicious)) {
        log_security_event(
            SECURITY_XSS_ATTEMPT,
            "Suspicious patterns in JSON input",
            ['patterns' => $suspicious]
        );
        return null;
    }
    
    return $data;
}
?>
