<?php
// Database configuration
define("DB_HOST", "localhost");
define("DB_NAME", "ecommerce");
define("DB_USER", "root");
define("DB_PASS", "");
// Site configuration
define("SITE_NAME", "Standbymall");
define("SITE_URL", "http://localhost/ecommerce");
define("ADMIN_EMAIL", "admin@gmail.com");
// Other configurations
define("ROOT_PATH", __DIR__);
define("PUBLIC_PATH", ROOT_PATH . "/public");
define("UPLOADS_PATH", PUBLIC_PATH . "/uploads");

ini_set('session.cookie_httponly', 1);
session_start();

  
?>
