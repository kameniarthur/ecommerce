<?php
namespace App\Core;

use App\Core\Request;
class Controller
{
    protected $db;
    protected $request;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->request = new Request();
    }

    public function view($view, $data = [])
    {
        extract($data);
        $viewFile = __DIR__ . "/../views/{$view}.php";
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            throw new \Exception("Vue non trouvée : {$view}");
        }
    }

    public function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    public function back()
    {
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    public function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // --- Middleware ---
    public function isAuthenticated()
    {
        return isset($_SESSION['user']);
    }

    public function isAdmin()
    {
        return $this->isAuthenticated() && $_SESSION['user']['role'] === 'admin';
    }

    public function isGuest()
    {
        return !$this->isAuthenticated();
    }

    // --- Flash Messages ---
    public function setFlash($type, $message)
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public function getFlash()
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
