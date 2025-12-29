<?php
require_once 'config/config.php';
require_once 'core/BiometricAuth.php';

echo "Testing PHP Invariants...\n";

// Test 1: Short Secret
putenv('SHARED_SECRET=short');
try {
    // Access private method via reflection or just trigger a public method that uses it
    // getSecret is private, but verifyBiometric calls generateNonce then... wait, verifyBiometric calls sendSecureRequest...
    // verifyResponse calls getSecret.
    // We need to trigger getSecret.
    // verifyResponse is private.
    // We can use Reflection to test private method strictly.
    
    $method = new ReflectionMethod('BiometricAuth', 'getSecret');
    $method->setAccessible(true);
    $method->invoke(null);
    
    echo "FAIL: Did not throw on short secret\n";
    exit(1);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'CRITICAL') !== false && strpos($e->getMessage(), 'too short') !== false) {
        echo "PASS: Threw CRITICAL on short secret\n";
    } else {
        echo "FAIL: Wrong exception: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>
