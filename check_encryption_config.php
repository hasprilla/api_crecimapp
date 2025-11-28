<?php
// Test if encryption keys are configured
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECKING ENCRYPTION CONFIGURATION ===\n\n";

$key = env('ENCRYPTION_KEY');
$iv = env('ENCRYPTION_IV');

if (!$key) {
    echo "❌ ERROR: ENCRYPTION_KEY is not set in .env file\n";
} else {
    echo "✅ ENCRYPTION_KEY is set\n";
    echo "   Length: " . strlen($key) . " characters";
    if (strlen($key) !== 32) {
        echo " ⚠️  WARNING: Should be 32 characters for AES-256\n";
    } else {
        echo " ✓\n";
    }
}

if (!$iv) {
    echo "❌ ERROR: ENCRYPTION_IV is not set in .env file\n";
} else {
    echo "✅ ENCRYPTION_IV is set\n";
    echo "   Length: " . strlen($iv) . " characters";
    if (strlen($iv) !== 16) {
        echo " ⚠️  WARNING: Should be 16 characters\n";
    } else {
        echo " ✓\n";
    }
}

if ($key && $iv && strlen($key) === 32 && strlen($iv) === 16) {
    echo "\n=== TESTING ENCRYPTION/DECRYPTION ===\n\n";
    
    $testData = ["email" => "test@test.com", "password" => "123456"];
    $jsonString = json_encode($testData);
    
    $encrypted = openssl_encrypt($jsonString, 'AES-256-CTR', $key, 0, $iv);
    echo "Encrypted: " . substr($encrypted, 0, 50) . "...\n";
    
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CTR', $key, 0, $iv);
    echo "Decrypted: " . $decrypted . "\n";
    
    if ($decrypted === $jsonString) {
        echo "\n✅ Encryption/Decryption is working correctly!\n";
    } else {
        echo "\n❌ ERROR: Encryption/Decryption failed!\n";
    }
}
