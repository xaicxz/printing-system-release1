<?php
// Copy to config.local.php and edit for your local MySQL user/password.
// This file is loaded before database constants are defined.
putenv('PRINT_DB_HOST=127.0.0.1');
putenv('PRINT_DB_PORT=3306');
putenv('PRINT_DB_NAME=printing_system');
putenv('PRINT_DB_USER=root');
putenv('PRINT_DB_PASS=');
// Only for reverse proxies or custom mounts, if auto-detection fails:
// putenv('PRINT_BASE_URL=/printing-system-release1/public');
