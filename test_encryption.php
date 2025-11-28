<?php
/**
 * Test script to verify encryption/decryption is working correctly
 * Run: php test_encryption.php
 */

$key = '12345678901234567890123456789012';
$iv = '1234567890123456';

// Test data
$testData = [
    "email" => "harveyasprilla@gmail.com",
    "password" => "123456"
];

echo "=== ENCRYPTION TEST ===\n\n";
echo "1. Original Data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Encrypt
$jsonString = json_encode($testData);
$encrypted = openssl_encrypt($jsonString, 'AES-256-CTR', $key, 0, $iv);

echo "2. Encrypted (what Flutter sends):\n";
$payload = ['data' => $encrypted];
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Simulate server receiving and decrypting
echo "3. Server receives and decrypts:\n";
$decrypted = openssl_decrypt($encrypted, 'AES-256-CTR', $key, 0, $iv);
$receivedData = json_decode($decrypted, true);
echo json_encode($receivedData, JSON_PRETTY_PRINT) . "\n\n";

// Verify
if ($receivedData === $testData) {
    echo "✅ SUCCESS: Encryption/Decryption working correctly!\n";
} else {
    echo "❌ ERROR: Data mismatch!\n";
}

echo "\n=== CURL COMMAND TO TEST ===\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"data\":\"$encrypted\"}'\n";
