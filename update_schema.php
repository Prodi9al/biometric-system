<?php
require_once 'config/config.php';

echo "<h2>Database Schema Update</h2>";
echo "<pre>";

try {
    $pdo = getConnection();
    
    // Check if 'pending' is already in the ENUM
    $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'status'");
    $row = $stmt->fetch();
    $type = $row['Type'];
    
    if (strpos($type, "'pending'") !== false) {
        echo "✅ 'pending' status already exists in customers table.\n";
    } else {
        echo "Adding 'pending' status to customers table...\n";
        // Modify ENUM to include 'pending' and set it as new default
        $pdo->exec("ALTER TABLE customers MODIFY COLUMN status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'pending'");
        echo "✅ Schema updated successfully.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
