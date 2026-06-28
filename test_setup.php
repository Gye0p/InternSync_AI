<?php
// Set OPENSSL_CONF for Windows
putenv('OPENSSL_CONF=C:\\xampp\\apache\\conf\\openssl.cnf');

require 'vendor/autoload.php';

// 1. Test private key loading
$privateKeyPem = file_get_contents('config/jwt/private.pem');
$publicKeyPem  = file_get_contents('config/jwt/public.pem');

echo "Private key file size: " . strlen($privateKeyPem) . " bytes\n";

$pk = openssl_pkey_get_private($privateKeyPem, '');
if (!$pk) {
    echo "FAILED to load private key: " . openssl_error_string() . "\n";
} else {
    echo "Private key loaded: OK\n";
}

$pub = openssl_pkey_get_public($publicKeyPem);
if (!$pub) {
    echo "FAILED to load public key: " . openssl_error_string() . "\n";
} else {
    echo "Public key loaded: OK\n";
}

// 2. Test DB connection
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=internsync_db', 'root', '');
    $stmt = $pdo->query("SELECT COUNT(*) FROM user");
    $count = $stmt->fetchColumn();
    echo "Database connection: OK ({$count} users)\n";
} catch (Exception $e) {
    echo "Database FAILED: " . $e->getMessage() . "\n";
}

echo "\nAll checks complete.\n";
