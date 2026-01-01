<?php
// index.php à la racine du projet

// === CHARGEMENT DES FICHIERS ESSENTIELS ===
require_once __DIR__ . '/config.php';

// Vérifier que les constantes sont définies
if (!defined('ROOT_PATH') || !defined('PUBLIC_PATH')) {
    die('Erreur: config.php non chargé correctement');
}

// Charger les classes core
require_once ROOT_PATH . '/core/Request.php';
require_once ROOT_PATH . '/core/Router.php';
require_once ROOT_PATH . '/core/Database.php';
require_once ROOT_PATH . '/core/Controller.php';

// === INITIALISATION DU ROUTEUR ===
$router = new Router();

// === ROUTES PUBLIQUES ===
$router->get('/', 'HomeController@index');
$router->get('/produits', 'ProductController@index');
$router->get('/produits/{id}', 'ProductController@show');
$router->get('/categorie/{slug}', 'ProductController@category');
$router->get('/recherche', 'ProductController@search');

// === ROUTES AUTHENTIFICATION ===
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@loginPost');
$router->get('/register', 'AuthController@register');
$router->post('/register', 'AuthController@registerPost');
$router->get('/logout', 'AuthController@logout');
$router->get('/forgot-password', 'AuthController@forgotPassword');

// === ROUTES PANIER ===
$router->get('/panier', 'CartController@index');
$router->post('/panier/ajouter', 'CartController@add');
$router->post('/panier/mettre-a-jour', 'CartController@update');
$router->get('/panier/supprimer/{id}', 'CartController@remove');
$router->get('/panier/vider', 'CartController@clear');

// === ROUTES CHECKOUT ===
$router->get('/checkout', 'CheckoutController@index');
$router->post('/checkout/process', 'CheckoutController@process');
$router->get('/checkout/confirmation/{id}', 'CheckoutController@confirmation');

// === ROUTES UTILISATEUR ===
$router->get('/mon-compte', 'UserController@dashboard');
$router->get('/mes-commandes', 'UserController@orders');
$router->get('/ma-commande/{id}', 'UserController@orderDetails');
$router->get('/mon-profil', 'UserController@profile');
$router->post('/mon-profil', 'UserController@updateProfile');
$router->get('/mes-adresses', 'UserController@addresses');

// === ROUTES ADMIN ===
$router->get('/admin', 'AdminController@dashboard');
$router->get('/admin/produits', 'AdminController@products');
$router->get('/admin/produits/creer', 'AdminController@createProduct');
$router->post('/admin/produits/creer', 'AdminController@storeProduct');
$router->get('/admin/produits/editer/{id}', 'AdminController@editProduct');
$router->post('/admin/produits/editer/{id}', 'AdminController@updateProduct');
$router->get('/admin/produits/supprimer/{id}', 'AdminController@deleteProduct');

$router->get('/admin/categories', 'AdminController@categories');
$router->get('/admin/commandes', 'AdminController@orders');
$router->get('/admin/utilisateurs', 'AdminController@users');

// === ROUTE 404 ===
$router->notFound(function() {
    http_response_code(404);
    
    // Si le fichier 404.php existe dans public/errors
    $errorFile = PUBLIC_PATH . '/errors/404.php';
    if (file_exists($errorFile)) {
        require_once $errorFile;
    } else {
        // Message par défaut
        echo '<h1>404 - Page non trouvée</h1>';
        echo '<p>La page que vous cherchez n\'existe pas.</p>';
        echo '<a href="/">Retour à l\'accueil</a>';
    }
    
    exit;
});

// === DÉMARRAGE DU ROUTEUR ===
$router->dispatch();