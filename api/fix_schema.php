<?php
require_once '../config/config.php';
header('Content-Type: text/plain');

try {
    $pdo = getConnection();
    
    echo "Attempting to resize biometric columns...\n";
    
    // Change face_enc to LONGTEXT
    $pdo->exec("ALTER TABLE biometric_data MODIFY face_enc LONGTEXT");
    echo "SUCCESS: face_enc resized to LONGTEXT.\n";
    
    // Change finger_enc to LONGTEXT
    $pdo->exec("ALTER TABLE biometric_data MODIFY finger_enc LONGTEXT");
    echo "SUCCESS: finger_enc resized to LONGTEXT.\n";
    
    echo "\nVerification:\n";
    $stmt = $pdo->query("DESCRIBE biometric_data");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if (in_array($col['Field'], ['face_enc', 'finger_enc'])) {
            echo $col['Field'] . ": " . $col['Type'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
