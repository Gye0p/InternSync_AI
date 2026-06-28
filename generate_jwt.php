<?php
$config = ['private_key_bits' => 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
$res = openssl_pkey_new($config);
if (!$res) { echo 'ERROR: ' . openssl_error_string(); exit(1); }
openssl_pkey_export($res, $privateKey);
$details = openssl_pkey_get_details($res);
if (!is_dir(__DIR__ . '/config/jwt')) { mkdir(__DIR__ . '/config/jwt', 0755, true); }
file_put_contents(__DIR__ . '/config/jwt/private.pem', $privateKey);
file_put_contents(__DIR__ . '/config/jwt/public.pem', $details['key']);
echo "JWT keys generated successfully.\n";
echo "Private key length: " . strlen($privateKey) . " bytes\n";
