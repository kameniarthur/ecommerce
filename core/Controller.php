<?php
// core/Controller.php

/**
 * Classe Controller de base
 */
class Controller
{
    // === PROPRIÉTÉS ===
    
    /**
     * @var Database Instance de base de données
     */
    protected $db;
    
    /**
     * @var Request Instance de requête
     */
    protected $request;
    
    /**
     * @var array Données à passer à la vue
     */
    protected $data = [];
    
    /**
     * @var array Layout à utiliser
     */
    protected $layout = 'layouts/main';
    
    // === CONSTRUCTEUR ===
    
    /**
     * Constructeur
     */
    public function __construct()
    {
        // Initialiser la base de données
        $this->db = Database::getInstance();
        
        // Initialiser la requête
        $this->request = Request::getInstance();
        
        // Données par défaut pour toutes les vues
        $this->data['site_title'] = SITE_NAME ?? 'Mon Site';
        $this->data['user'] = $_SESSION['user'] ?? null;
        $this->data['errors'] = $_SESSION['errors'] ?? [];
        $this->data['old_input'] = $_SESSION['old_input'] ?? [];
        
        // Nettoyer les sessions après utilisation
        unset($_SESSION['errors'], $_SESSION['old_input']);
    }
    
    // === MÉTHODES DE BASE ===
    
    /**
     * Afficher une vue
     * 
     * @param string $view Chemin de la vue (sans extension)
     * @param array $data Données supplémentaires
     */
    protected function view($view, $data = [])
    {
        // Fusionner les données
        $viewData = array_merge($this->data, $data);
        
        // Chemin complet du fichier de vue
        $viewFile = ROOT_PATH . "/app/views/{$view}.php";
        
        if (!file_exists($viewFile)) {
            die("Vue non trouvée : {$view}");
        }
        
        // Extraire les données pour les rendre accessibles dans la vue
        extract($viewData);
        
        // Démarrer la capture du contenu
        ob_start();
        
        // Inclure la vue
        include $viewFile;
        
        // Récupérer le contenu
        $content = ob_get_clean();
        
        // Inclure le layout si spécifié
        if ($this->layout) {
            $layoutFile = ROOT_PATH . "/app/views/{$this->layout}.php";
            
            if (file_exists($layoutFile)) {
                // Le layout aura accès à $content
                include $layoutFile;
            } else {
                // Pas de layout, afficher directement le contenu
                echo $content;
            }
        } else {
            echo $content;
        }
    }
    
    /**
     * Rediriger vers une URL
     * 
     * @param string $url URL de destination
     */
    protected function redirect($url)
    {
        if (!headers_sent()) {
            header("Location: {$url}");
        } else {
            echo "<script>window.location.href = '{$url}';</script>";
        }
        exit;
    }
    
    /**
     * Retourner à la page précédente
     */
    protected function back()
    {
        $referer = $this->request->server('HTTP_REFERER', '/');
        $this->redirect($referer);
    }
    
    /**
     * Retourner une réponse JSON
     * 
     * @param mixed $data Données à encoder
     * @param int $status Code HTTP
     */
    protected function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    // === MIDDLEWARE SIMPLE ===
    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    protected function isAuthenticated()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->setFlash('error', 'Vous devez être connecté pour accéder à cette page');
            $this->redirect('/login');
        }
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     */
    protected function isAdmin()
    {
        $this->isAuthenticated();
        
        $user = $_SESSION['user'] ?? null;
        
        if (!$user || $user['role'] !== 'admin') {
            $this->setFlash('error', 'Accès réservé aux administrateurs');
            $this->redirect('/');
        }
    }
    
    /**
     * Vérifier si l'utilisateur est invité (non connecté)
     */
    protected function isGuest()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
    }
    
    // === HELPERS ===
    
    /**
     * Définir un message flash
     * 
     * @param string $type Type du message (success, error, warning, info)
     * @param string $message Contenu du message
     */
    protected function setFlash($type, $message)
    {
        $_SESSION['flash'][$type] = $message;
    }
    
    /**
     * Récupérer et supprimer les messages flash
     * 
     * @return array Messages flash
     */
    protected function getFlash()
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }
    
    /**
     * Définir des erreurs de validation
     * 
     * @param array $errors Tableau d'erreurs
     */
    protected function setValidationErrors(array $errors)
    {
        $_SESSION['errors'] = $errors;
    }
    
    /**
     * Sauvegarder les données du formulaire
     * 
     * @param array $data Données à sauvegarder
     */
    protected function withInput(array $data)
    {
        $_SESSION['old_input'] = $data;
    }
    
    /**
     * Afficher un message d'erreur 404
     */
    protected function notFound()
    {
        http_response_code(404);
        $this->view('errors/404');
        exit;
    }
    
    /**
     * Afficher un message d'erreur 403 (interdit)
     */
    protected function forbidden()
    {
        http_response_code(403);
        $this->view('errors/403');
        exit;
    }
    
    /**
     * Définir le titre de la page
     * 
     * @param string $title Titre de la page
     */
    protected function setTitle($title)
    {
        $this->data['page_title'] = $title;
    }
    
    /**
     * Charger un modèle
     * 
     * @param string $model Nom du modèle
     * @return mixed Instance du modèle
     */
    protected function model($model)
    {
        $modelFile = ROOT_PATH . "/app/models/{$model}.php";
        
        if (file_exists($modelFile)) {
            require_once $modelFile;
            $modelClass = $model;
            return new $modelClass();
        }
        
        die("Modèle {$model} non trouvé");
    }
}