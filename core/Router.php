<?php
// core/Router.php

class Router
{
    /**
     * @var array Routes enregistrées
     */
    private $routes = [];
    
    /**
     * @var callable Callback 404
     */
    private $notFound;
    
    /**
     * Enregistre une route GET
     * 
     * @param string $uri URI de la route
     * @param callable|string $callback Fonction de callback
     * @return self
     */
    public function get($uri, $callback)
    {
        return $this->addRoute('GET', $uri, $callback);
    }
    
    /**
     * Enregistre une route POST
     * 
     * @param string $uri URI de la route
     * @param callable|string $callback Fonction de callback
     * @return self
     */
    public function post($uri, $callback)
    {
        return $this->addRoute('POST', $uri, $callback);
    }
    
    /**
     * Enregistre une route PUT
     * 
     * @param string $uri URI de la route
     * @param callable|string $callback Fonction de callback
     * @return self
     */
    public function put($uri, $callback)
    {
        return $this->addRoute('PUT', $uri, $callback);
    }
    
    /**
     * Enregistre une route DELETE
     * 
     * @param string $uri URI de la route
     * @param callable|string $callback Fonction de callback
     * @return self
     */
    public function delete($uri, $callback)
    {
        return $this->addRoute('DELETE', $uri, $callback);
    }
    
    /**
     * Enregistre une route pour toutes les méthodes
     * 
     * @param string $uri URI de la route
     * @param callable|string $callback Fonction de callback
     * @return self
     */
    public function any($uri, $callback)
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $uri, $callback);
    }
    
    /**
     * Définit le callback pour les erreurs 404
     * 
     * @param callable $callback Fonction de callback
     * @return self
     */
    public function notFound($callback)
    {
        $this->notFound = $callback;
        return $this;
    }
    
    /**
     * Traite la requête et exécute la route correspondante
     */
    public function dispatch()
    {
        // Récupérer l'URI et la méthode
        $request = Request::getInstance();
        $uri = $this->getCurrentUri();
        $method = $request->method();
        
        // Chercher une route correspondante
        $route = $this->match($uri, $method);
        
        if ($route) {
            // Extraire les paramètres
            $params = $this->extractParams($uri, $route['pattern']);
            
            // Appeler le callback
            $this->callCallback($route['callback'], $params);
        } else {
            // Aucune route trouvée
            $this->callNotFound();
        }
    }
    
    /**
     * Ajoute une route à la collection
     * 
     * @param string|array $methods Méthode(s) HTTP
     * @param string $uri URI de la route
     * @param callable|string $callback Fonction de callback
     * @return self
     */
    private function addRoute($methods, $uri, $callback)
    {
        // Normaliser les méthodes
        $methods = is_array($methods) ? $methods : [$methods];
        
        // Générer le pattern regex
        $pattern = $this->buildPattern($uri);
        
        foreach ($methods as $method) {
            $this->routes[$method][] = [
                'uri' => $uri,
                'callback' => $callback,
                'pattern' => $pattern
            ];
        }
        
        return $this;
    }
    
    /**
     * Construit un pattern regex à partir d'une URI avec paramètres
     * 
     * @param string $uri URI de la route
     * @return string Pattern regex
     */
    private function buildPattern($uri)
    {
        // Remplacer {param} par des groupes de capture regex
        $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $uri);
        
        // Échapper les slashs
        $pattern = str_replace('/', '\/', $pattern);
        
        return '#^' . $pattern . '$#';
    }
    
    /**
     * Récupère l'URI courante
     * 
     * @return string URI nettoyée
     */
    private function getCurrentUri()
    {
        $request = Request::getInstance();
        $uri = $request->uri();
        
        // Retirer le base path si nécessaire
        $basePath = $request->basePath();
        if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        
        return '/' . trim($uri, '/');
    }
    
    /**
     * Cherche une route correspondant à l'URI et la méthode
     * 
     * @param string $uri URI à matcher
     * @param string $method Méthode HTTP
     * @return array|null Route trouvée ou null
     */
    private function match($uri, $method)
    {
        // Vérifier les routes de la méthode spécifique
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                if (preg_match($route['pattern'], $uri)) {
                    return $route;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extrait les paramètres de l'URI
     * 
     * @param string $uri URI de la requête
     * @param string $pattern Pattern regex
     * @return array Paramètres extraits
     */
    private function extractParams($uri, $pattern)
    {
        $params = [];
        
        if (preg_match($pattern, $uri, $matches)) {
            // Récupérer les groupes nommés
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
        }
        
        return $params;
    }
    
    /**
     * Appelle le callback de la route
     * 
     * @param callable|string $callback Callback à appeler
     * @param array $params Paramètres à passer
     */
    private function callCallback($callback, $params)
    {
        if (is_callable($callback)) {
            // Si c'est une fonction anonyme
            call_user_func_array($callback, $params);
        } elseif (is_string($callback) && strpos($callback, '@') !== false) {
            // Format "Controller@method"
            list($controller, $method) = explode('@', $callback, 2);
            
            $this->callControllerMethod($controller, $method, $params);
        } elseif (is_string($callback) && class_exists($callback)) {
            // Si c'est une classe invokable
            $instance = new $callback();
            call_user_func_array([$instance, '__invoke'], $params);
        } else {
            throw new Exception("Callback invalide pour la route");
        }
    }
    
    /**
     * Instancie un contrôleur et appelle une méthode
     * 
     * @param string $controller Nom du contrôleur
     * @param string $method Méthode à appeler
     * @param array $params Paramètres à passer
     */
    private function callControllerMethod($controller, $method, $params)
    {
        // Ajouter le suffixe "Controller" si non présent
        if (substr($controller, -10) !== 'Controller') {
            $controller .= 'Controller';
        }
        
        // Charger le fichier du contrôleur
        $controllerFile = ROOT_PATH . '/app/controllers/' . $controller . '.php';
        
        if (!file_exists($controllerFile)) {
            throw new Exception("Contrôleur $controller non trouvé");
        }
        
        require_once $controllerFile;
        
        if (!class_exists($controller)) {
            throw new Exception("Classe $controller non trouvée");
        }
        
        // Instancier et appeler la méthode
        $instance = new $controller();
        
        if (!method_exists($instance, $method)) {
            throw new Exception("Méthode $method non trouvée dans $controller");
        }
        
        call_user_func_array([$instance, $method], $params);
    }
    
    /**
     * Appelle le callback 404
     */
    private function callNotFound()
    {
        if ($this->notFound) {
            $this->callCallback($this->notFound, []);
        } else {
            // 404 par défaut
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            echo '<h1>404 - Page non trouvée</h1>';
        }
    }
}