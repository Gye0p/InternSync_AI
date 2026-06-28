<?php

use App\Kernel;

// Fix OpenSSL config path for Windows — required for JWT RSA key signing
if (PHP_OS_FAMILY === 'Windows' && !getenv('OPENSSL_CONF')) {
    putenv('OPENSSL_CONF=C:\\xampp\\apache\\conf\\openssl.cnf');
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
