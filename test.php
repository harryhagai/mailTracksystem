<?php
// Simple test to isolate the error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP is working!";

// Test if we can include security files
try {
    require_once 'includes/security.php';
    echo "Security files loaded successfully!";
} catch (Exception $e) {
    echo "Error loading security files: " . $e->getMessage();
} catch (Error $e) {
    echo "PHP Error: " . $e->getMessage();
}

phpinfo();
?>
