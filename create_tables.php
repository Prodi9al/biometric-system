<?php
// Manual table creation script with debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simple .env parsing
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

loadEnv(__DIR__ . '/.env');

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

echo "<h2>Biometric ATM Database Setup</h2>";
echo "<pre>";

try {
    echo "Step 1: Connecting to MySQL...\n";
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ MySQL connection successful\n\n";
    
    echo "Step 2: Creating database...\n";
    $pdo->exec('CREATE DATABASE IF NOT EXISTS biometric_atm_db');
    echo "✅ Database 'biometric_atm_db' created/exists\n";
    
    $pdo->exec('USE biometric_atm_db');
    echo "✅ Using database 'biometric_atm_db'\n\n";
    
    echo "Step 3: Creating tables...\n";
    
    // Create customers table
    echo "Creating 'customers' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        account_number VARCHAR(20) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        pin_hash VARCHAR(255) NOT NULL,
        current_balance DECIMAL(15,2) DEFAULT 0.00,
        status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ 'customers' table created\n";
    
    // Create admin_users table
    echo "Creating 'admin_users' table...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
        admin_id INT PRIMARY KEY AUTO_INCREMENT,
        staff_id VARCHAR(20) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin', 'manager', 'operator') DEFAULT 'admin',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ 'admin_users' table created\n";
    
    // Create biometric_data table
    $pdo->exec("CREATE TABLE IF NOT EXISTS biometric_data (
        bio_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        face_enc TEXT,
        finger_enc TEXT,
        quality_score DECIMAL(5,2) DEFAULT 95.0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES customers(user_id) ON DELETE CASCADE
    )");
    
    // Create transaction_history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS transaction_history (
        trans_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        trans_type VARCHAR(50) NOT NULL,
        amount DECIMAL(15,2) DEFAULT 0.00,
        recipient_account VARCHAR(20),
        auth_status ENUM('Success', 'Failed', 'Pending') DEFAULT 'Success',
        match_confidence DECIMAL(5,2) DEFAULT 95.0,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES customers(user_id) ON DELETE CASCADE
    )");
    
    echo "Inserting sample data...\n";
    // Insert sample data
    $pdo->exec("INSERT IGNORE INTO customers (account_number, full_name, pin_hash, current_balance) VALUES 
        ('2023 8841 9920 1102', 'John Doe', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 5000.00),
        ('2023 7752 8831 2203', 'Jane Smith', '$2y$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3500.00)");
    echo "✅ Sample customers inserted\n";
    
    // Create proper password hash for 'admin123'
    echo "Creating security tables...\n";
    
    // Secure sessions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS secure_sessions (
        session_id VARCHAR(64) PRIMARY KEY,
        user_id INT NOT NULL,
        auth_method VARCHAR(20) NOT NULL,
        confidence_score DECIMAL(5,2),
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ 'secure_sessions' table created\n";
    
    // Auth logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS auth_logs (
        log_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        result ENUM('SUCCESS', 'FAILED', 'ERROR') NOT NULL,
        modality VARCHAR(20),
        details TEXT,
        session_id VARCHAR(64),
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ 'auth_logs' table created\n";
    
    echo "Creating admin user...\n";
    $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO admin_users (staff_id, full_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['ADM001', 'System Administrator', 'admin@ghanabank.com', $admin_hash, 'admin']);
    echo "✅ Admin user created\n";
    
    echo "\n\n✅ ALL SETUP COMPLETED SUCCESSFULLY!\n";
    echo "</pre>";
    echo "<h3>Test Login Credentials:</h3>";
    echo "<p><strong>Customer:</strong> Account: 2023 8841 9920 1102, PIN: 1234</p>";
    echo "<p><strong>Admin:</strong> Staff ID: ADM001, Password: admin123</p>";
    echo "<p><a href='public/login.php' style='margin-right:20px;'>Customer Login</a>";
    echo "<a href='admin/login-modern.php'>Admin Login</a></p>";
    
} catch (Exception $e) {
    echo "\n\n❌ SETUP FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "</pre>";
}
?>