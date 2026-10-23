<?php
// Local deployment settings. Set environment variables or create app/config.local.php.
// Never commit real passwords to version control.
if (is_file(__DIR__ . '/config.local.php')) require __DIR__ . '/config.local.php';
define('DB_HOST', getenv('PRINT_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('PRINT_DB_PORT') ?: '3306');
define('DB_NAME', getenv('PRINT_DB_NAME') ?: 'printing_system');
define('DB_USER', getenv('PRINT_DB_USER') ?: 'root');
define('DB_PASS', getenv('PRINT_DB_PASS') !== false ? getenv('PRINT_DB_PASS') : '');
define('APP_NAME', 'PrintFlow');
define('MAX_PDF_SIZE', 15 * 1024 * 1024);
define('PRIVATE_FILE_DIR', dirname(__DIR__) . '/storage/print_files');
