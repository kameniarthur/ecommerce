<?php
// index.php - Point d'entrée unique

session_start();
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/app/helpers/functions.php";

use App\Core\Request;
use App\Core\Router;

$request = new Request();
$router = new Router();

// --- Routes publiques ---
$router->get('/', 'HomeController@index');
$router->get('/produits', 'ProductController@index');

// --- Routes auth ---
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');

// --- Routes panier ---
$router->post('/cart/add', 'CartController@add');
$router->post('/cart/update', 'CartController@update');
$router->post('/cart/remove', 'CartController@remove');

// --- Routes checkout ---
$router->get('/checkout', 'CheckoutController@index');
$router->post('/checkout', 'CheckoutController@process');

// --- Routes utilisateur ---
$router->get('/dashboard', 'UserController@dashboard');
$router->get('/orders', 'UserController@orders');

// --- Routes admin ---
$router->get('/admin/users', 'AdminController@users');
$router->get('/admin/products', 'AdminController@products');

// --- 404 ---
$router->notFound(function () {
    http_response_code(404);
    echo "Page non trouvée";
});

// --- Dispatch ---
$router->dispatch($request);
