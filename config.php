<?php
// config.php à la racine du projet

// === MODE DÉVELOPPEMENT ===
define('ENVIRONMENT', 'development');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// === CONSTANTES DE CONFIGURATION ===

// Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'standbymall_dev');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site
define('SITE_NAME', 'StandByMall');
define('SITE_URL', 'http://localhost:8000');
define('ADMIN_EMAIL', 'admin@standbymall.com');

// Chemins
define('ROOT_PATH', __DIR__);  // Maintenant config.php est à la racine
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// === SESSION ===
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === AUTOLOADER POUR VOTRE STRUCTURE ===
spl_autoload_register(function ($className) {
    // Convertir le namespace en chemin
    $className = str_replace('\\', '/', $className);
    
    // Liste des répertoires selon votre structure
    $directories = [
        ROOT_PATH . '/core/',               // Classes core
        ROOT_PATH . '/app/controllers/',    // Controllers
        ROOT_PATH . '/app/models/',         // Models
        ROOT_PATH . '/app/helpers/',        // Helpers
        ROOT_PATH . '/app/',                // Au cas où
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    
    return false;
});

// === VÉRIFICATIONS DEV (optionnel) ===
if (ENVIRONMENT === 'development') {
    // Vérifier les dossiers d'écriture
    $writablePaths = [UPLOAD_PATH, ROOT_PATH . '/logs', ROOT_PATH . '/storage'];
    foreach ($writablePaths as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (!is_writable($path)) {
            echo "<div style='background:#ffcc00;padding:10px;margin:10px;'>
                  <strong>AVERTISSEMENT :</strong> Le dossier '$path' n'est pas accessible en écriture.
                  </div>";
        }
    }
}