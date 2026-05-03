<?php
// Debug test to check what's causing the 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Test Starting...<br>";

// Test 1: Basic PHP
echo "1. Basic PHP: OK<br>";

// Test 2: Session
try {
    session_start();
    echo "2. Session: OK<br>";
} catch (Exception $e) {
    echo "2. Session Error: " . $e->getMessage() . "<br>";
}

// Test 3: Include basic file
try {
    require_once 'includes/security.php';
    echo "3. Security include: OK<br>";
} catch (Exception $e) {
    echo "3. Security include Error: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "3. Security include PHP Error: " . $e->getMessage() . "<br>";
}

// Test 4: Test redirect function
try {
    if (function_exists('redirect_to')) {
        echo "4. redirect_to function: OK<br>";
    } else {
        echo "4. redirect_to function: Missing<br>";
    }
} catch (Exception $e) {
    echo "4. redirect_to function Error: " . $e->getMessage() . "<br>";
}

echo "Debug Test Complete<br>";
?>
