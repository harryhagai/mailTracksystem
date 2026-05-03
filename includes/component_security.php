<?php
/**
 * Component Security and Vulnerability Management
 * Implements OWASP A06: Vulnerable and Outdated Components protections
 */

require_once __DIR__ . '/security_config.php';
require_once __DIR__ . '/logging.php';

/**
 * Component Security Manager
 */
class ComponentSecurity {
    private static $component_registry = [];
    private static $vulnerability_cache = [];
    
    /**
     * Register a component with version information
     */
    public static function registerComponent(string $name, string $version, array $metadata = []): void {
        self::$component_registry[$name] = [
            'version' => $version,
            'metadata' => $metadata,
            'registered_at' => time()
        ];
    }
    
    /**
     * Get all registered components
     */
    public static function getComponents(): array {
        return self::$component_registry;
    }
    
    /**
     * Check for known vulnerabilities in components
     */
    public static function checkVulnerabilities(): array {
        $vulnerabilities = [];
        
        foreach (self::$component_registry as $name => $component) {
            $vulns = self::checkComponentVulnerabilities($name, $component['version']);
            if (!empty($vulns)) {
                $vulnerabilities[$name] = $vulns;
            }
        }
        
        return $vulnerabilities;
    }
    
    /**
     * Check specific component for vulnerabilities
     */
    private static function checkComponentVulnerabilities(string $component, string $version): array {
        $cache_key = $component . ':' . $version;
        
        if (isset(self::$vulnerability_cache[$cache_key])) {
            return self::$vulnerability_cache[$cache_key];
        }
        
        // In a real implementation, this would query a vulnerability database
        // For now, we'll implement basic checks
        $vulnerabilities = [];
        
        // Check for known vulnerable versions (example data)
        $known_vulnerabilities = [
            'php' => [
                '7.4.0' => ['CVE-2020-7064', 'CVE-2020-7065'],
                '7.3.0' => ['CVE-2019-11043'],
                '8.0.0' => ['CVE-2020-7070']
            ],
            'openssl' => [
                '1.1.1' => ['CVE-2021-3711'],
                '1.1.0' => ['CVE-2019-1543']
            ]
        ];
        
        if (isset($known_vulnerabilities[$component][$version])) {
            $vulnerabilities = $known_vulnerabilities[$component][$version];
        }
        
        self::$vulnerability_cache[$cache_key] = $vulnerabilities;
        return $vulnerabilities;
    }
    
    /**
     * Validate PHP version security
     */
    public static function validatePHPVersion(): array {
        $issues = [];
        $php_version = PHP_VERSION;
        
        // Check if PHP version is supported
        $supported_versions = ['8.1', '8.2', '8.3'];
        $major_version = substr($php_version, 0, 3);
        
        if (!in_array($major_version, $supported_versions)) {
            $issues[] = "PHP version {$php_version} may be outdated. Consider upgrading to a supported version.";
        }
        
        // Check for vulnerable PHP versions
        $vulnerable_versions = [
            '7.4.0', '7.4.1', '7.4.2', '7.4.3',
            '8.0.0', '8.0.1', '8.0.2'
        ];
        
        if (in_array($php_version, $vulnerable_versions)) {
            $issues[] = "PHP version {$php_version} has known vulnerabilities. Upgrade immediately.";
        }
        
        return $issues;
    }
    
    /**
     * Check PHP extensions for security issues
     */
    public static function checkPHPExtensions(): array {
        $issues = [];
        $extensions = get_loaded_extensions();
        
        // Check for potentially dangerous extensions
        $dangerous_extensions = [
            'php_shell' => 'Shell execution capabilities',
            'php_system' => 'System command execution',
            'php_passthru' => 'Command execution',
            'php_exec' => 'Command execution'
        ];
        
        foreach ($dangerous_extensions as $ext => $description) {
            if (in_array($ext, $extensions)) {
                $issues[] = "Dangerous extension '{$ext}' is enabled: {$description}";
            }
        }
        
        // Check for missing security extensions
        $recommended_extensions = [
            'openssl' => 'Encryption support',
            'hash' => 'Secure hashing',
            'mbstring' => 'Secure string handling',
            'filter' => 'Input validation',
            'json' => 'Secure JSON handling'
        ];
        
        foreach ($recommended_extensions as $ext => $description) {
            if (!in_array($ext, $extensions)) {
                $issues[] = "Recommended extension '{$ext}' is missing: {$description}";
            }
        }
        
        return $issues;
    }
    
    /**
     * Validate server configuration
     */
    public static function validateServerConfig(): array {
        $issues = [];
        
        // Check for dangerous PHP settings
        $dangerous_settings = [
            'allow_url_include' => '1',
            'allow_url_fopen' => '1',
            'register_globals' => '1',
            'magic_quotes_gpc' => '1',
            'safe_mode' => '0'
        ];
        
        foreach ($dangerous_settings as $setting => $bad_value) {
            $current = ini_get($setting);
            if ($current === $bad_value) {
                $issues[] = "Dangerous PHP setting detected: {$setting} = {$current}";
            }
        }
        
        // Check for missing security headers
        if (!ini_get('session.cookie_httponly')) {
            $issues[] = "Session cookies are not HTTP-only";
        }
        
        if (!ini_get('session.use_only_cookies')) {
            $issues[] = "Session cookies are not enforced";
        }
        
        return $issues;
    }
    
    /**
     * Generate security report
     */
    public static function generateSecurityReport(): array {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'components' => self::getComponents(),
            'vulnerabilities' => self::checkVulnerabilities(),
            'php_issues' => self::validatePHPVersion(),
            'extension_issues' => self::checkPHPExtensions(),
            'config_issues' => self::validateServerConfig()
        ];
        
        // Log security report
        log_security_event(
            SECURITY_DATA_MODIFICATION,
            "Security report generated",
            ['vulnerability_count' => count($report['vulnerabilities'])]
        );
        
        return $report;
    }
    
    /**
     * Auto-update vulnerability database (placeholder)
     */
    public static function updateVulnerabilityDatabase(): bool {
        // In a real implementation, this would fetch from a vulnerability database
        // For now, we'll just clear the cache
        self::$vulnerability_cache = [];
        
        log_security_event(
            SECURITY_DATA_MODIFICATION,
            "Vulnerability database updated"
        );
        
        return true;
    }
}

/**
 * Dependency Security Checker
 */
class DependencySecurity {
    /**
     * Check composer dependencies for vulnerabilities
     */
    public static function checkComposerDependencies(): array {
        $issues = [];
        $composer_lock = __DIR__ . '/../composer.lock';
        
        if (!file_exists($composer_lock)) {
            $issues[] = "composer.lock file not found - unable to check dependencies";
            return $issues;
        }
        
        $lock_data = json_decode(file_get_contents($composer_lock), true);
        
        if (!$lock_data || !isset($lock_data['packages'])) {
            $issues[] = "Invalid composer.lock format";
            return $issues;
        }
        
        foreach ($lock_data['packages'] as $package) {
            $vulns = self::checkPackageVulnerabilities(
                $package['name'],
                $package['version']
            );
            
            if (!empty($vulns)) {
                $issues[$package['name']] = [
                    'version' => $package['version'],
                    'vulnerabilities' => $vulns
                ];
            }
        }
        
        return $issues;
    }
    
    /**
     * Check specific package for vulnerabilities
     */
    private static function checkPackageVulnerabilities(string $package, string $version): array {
        // In a real implementation, this would query a vulnerability database
        // For now, we'll return empty array
        return [];
    }
    
    /**
     * Generate dependency report
     */
    public static function generateDependencyReport(): array {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'dependencies' => self::checkComposerDependencies()
        ];
    }
}

/**
 * File Integrity Checker
 */
class FileIntegrityChecker {
    private static $baseline_file = __DIR__ . '/../.file_integrity_baseline';
    
    /**
     * Create baseline of file hashes
     */
    public static function createBaseline(): bool {
        $baseline = [];
        $directory = __DIR__ . '/..';
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && !$file->isLink()) {
                $path = str_replace($directory . '/', '', $file->getPathname());
                
                // Skip certain files
                if (preg_match('/\.(log|tmp|cache)$/', $path) || 
                    strpos($path, '.git/') === 0 ||
                    strpos($path, 'logs/') === 0) {
                    continue;
                }
                
                $baseline[$path] = hash_file('sha256', $file->getPathname());
            }
        }
        
        file_put_contents(self::$baseline_file, json_encode($baseline, JSON_PRETTY_PRINT));
        
        log_security_event(
            SECURITY_DATA_MODIFICATION,
            "File integrity baseline created",
            ['file_count' => count($baseline)]
        );
        
        return true;
    }
    
    /**
     * Check file integrity against baseline
     */
    public static function checkIntegrity(): array {
        if (!file_exists(self::$baseline_file)) {
            return ['error' => 'No baseline file found. Run createBaseline() first.'];
        }
        
        $baseline = json_decode(file_get_contents(self::$baseline_file), true);
        $issues = [];
        $directory = __DIR__ . '/..';
        
        foreach ($baseline as $path => $expected_hash) {
            $full_path = $directory . '/' . $path;
            
            if (!file_exists($full_path)) {
                $issues['missing'][] = $path;
                continue;
            }
            
            $current_hash = hash_file('sha256', $full_path);
            
            if ($current_hash !== $expected_hash) {
                $issues['modified'][] = [
                    'file' => $path,
                    'expected' => $expected_hash,
                    'actual' => $current_hash
                ];
            }
        }
        
        if (!empty($issues)) {
            log_security_event(
                SECURITY_SUSPICIOUS_ACTIVITY,
                "File integrity check failed",
                ['issues' => $issues]
            );
        }
        
        return $issues;
    }
    
    /**
     * Update baseline with current file state
     */
    public static function updateBaseline(): bool {
        return self::createBaseline();
    }
}

// Register core components
ComponentSecurity::registerComponent('php', PHP_VERSION, [
    'os' => PHP_OS,
    'sapi' => PHP_SAPI,
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
]);

ComponentSecurity::registerComponent('openssl', OPENSSL_VERSION_TEXT ?? 'unknown');
?>
