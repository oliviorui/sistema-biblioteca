<?php
// Ajusta conforme teu ambiente local/produção
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_biblioteca');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Cookies/sessão
define('COOKIE_SECURE', false); // true em produção com HTTPS
define('COOKIE_SAMESITE', 'Lax'); // 'Strict' ou 'None' (se usar Secure+cross-site)
define('COOKIE_DAYS', 30);
