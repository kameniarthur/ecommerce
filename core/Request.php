<?php
// core/Request.php

/**
 * Classe Request - Gestion sécurisée des requêtes HTTP
 */
class Request
{
    // === PROPRIÉTÉS PRIVÉES ===
    
    /**
     * @var array Paramètres GET
     */
    private $get = [];
    
    /**
     * @var array Paramètres POST
     */
    private $post = [];
    
    /**
     * @var array Fichiers uploadés
     */
    private $files = [];
    
    /**
     * @var array Variables serveur
     */
    private $server = [];
    
    /**
     * @var array Cookies
     */
    private $cookies = [];
    
    /**
     * @var array Données JSON (si Content-Type: application/json)
     */
    private $json = [];
    
    /**
     * @var string Méthode HTTP
     */
    private $method;
    
    /**
     * @var array Headers HTTP
     */
    private $headers = [];
    
    /**
     * @var string URI de la requête
     */
    private $uri;
    
    /**
     * @var array Données brutes du body (pour PUT, PATCH, DELETE)
     */
    private $body = null;
    
    /**
     * @var bool Activer le nettoyage automatique des données
     */
    private $autoSanitize = true;
    
    /**
     * @var array Liste des champs à ne pas nettoyer
     */
    private $skipSanitize = ['password', 'confirm_password', 'token', 'csrf_token'];
    
    // === SINGLETON PATTERN ===
    
    /**
     * @var Request Instance unique
     */
    private static $instance = null;
    
    /**
     * Constructeur privé (singleton)
     */
    private function __construct()
    {
        $this->initialize();
    }
    
    /**
     * Récupère l'instance unique
     * 
     * @return Request Instance de Request
     */
    public static function getInstance(): Request
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialise les données de la requête
     */
    private function initialize(): void
    {
        // Récupérer les données globales
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;
        
        // Déterminer la méthode HTTP
        $this->method = $this->determineMethod();
        
        // Récupérer les headers
        $this->headers = $this->getAllHeaders();
        
        // Récupérer l'URI
        $this->uri = $this->getUri();
        
        // Traiter les données JSON si nécessaire
        $this->processJson();
        
        // Traiter les données PUT/PATCH/DELETE
        $this->processBody();
        
        // Nettoyer automatiquement les données si activé
        if ($this->autoSanitize) {
            $this->sanitizeAll();
        }
    }
    
    // === MÉTHODES D'INITIALISATION ===
    
    /**
     * Détermine la méthode HTTP réelle
     * Supporte les méthodes spoofées (_method)
     * 
     * @return string Méthode HTTP
     */
    private function determineMethod(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';
        
        // Support pour les méthodes spoofées (Laravel style)
        if ($method === 'POST') {
            $spoofedMethod = strtoupper($this->post['_method'] ?? $this->server['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '');
            
            if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'])) {
                $method = $spoofedMethod;
            }
        }
        
        return strtoupper($method);
    }
    
    /**
     * Récupère tous les headers HTTP
     * 
     * @return array Headers
     */
    private function getAllHeaders(): array
    {
        $headers = [];
        
        // Si getallheaders() existe (Apache)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback pour Nginx/autres serveurs
            foreach ($this->server as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $header = str_replace('_', '-', substr($key, 5));
                    $headers[$header] = $value;
                } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                    $header = str_replace('_', '-', $key);
                    $headers[$header] = $value;
                }
            }
        }
        
        return array_change_key_case($headers, CASE_LOWER);
    }
    
    /**
     * Récupère l'URI de la requête
     * 
     * @return string URI nettoyée
     */
    private function getUri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        
        // Retirer la query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Nettoyer les doubles slashes
        $uri = preg_replace('#/+#', '/', $uri);
        
        return trim($uri, '/') ?: '/';
    }
    
    /**
     * Traite les données JSON
     */
    private function processJson(): void
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            
            if ($json !== false) {
                $this->json = json_decode($json, true) ?? [];
                
                // Fusionner avec POST pour une compatibilité
                if ($this->method === 'POST' && !empty($this->json)) {
                    $this->post = array_merge($this->post, $this->json);
                }
            }
        }
    }
    
    /**
     * Traite les données du body pour PUT/PATCH/DELETE
     */
    private function processBody(): void
    {
        if (in_array($this->method, ['PUT', 'PATCH', 'DELETE'])) {
            $input = file_get_contents('php://input');
            
            if (!empty($input)) {
                parse_str($input, $this->body);
                
                // Fusionner avec POST pour compatibilité
                if (!empty($this->body)) {
                    $this->post = array_merge($this->post, $this->body);
                }
            }
        }
    }
    
    /**
     * Nettoie automatiquement toutes les données
     */
    private function sanitizeAll(): void
    {
        $this->get = $this->sanitize($this->get);
        $this->post = $this->sanitize($this->post);
        
        // Ne pas nettoyer les cookies pour éviter les problèmes de session
        // $this->cookies = $this->sanitize($this->cookies);
    }
    
    // === MÉTHODES D'ACCÈS AUX DONNÉES ===
    
    /**
     * Récupère une valeur GET
     * 
     * @param string|null $key Clé (null pour toutes)
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur ou tableau
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        
        return $this->get[$key] ?? $default;
    }
    
    /**
     * Récupère une valeur POST
     * 
     * @param string|null $key Clé (null pour toutes)
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur ou tableau
     */
    public function post($key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        
        return $this->post[$key] ?? $default;
    }
    
    /**
     * Récupère une valeur (GET puis POST)
     * 
     * @param string|null $key Clé (null pour toutes)
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur ou tableau
     */
    public function input($key = null, $default = null)
    {
        if ($key === null) {
            return array_merge($this->get, $this->post);
        }
        
        return $this->get($key, $this->post($key, $default));
    }
    
    /**
     * Récupère toutes les données (GET, POST, FILES)
     * 
     * @return array Toutes les données
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->files);
    }
    
    /**
     * Récupère une valeur JSON
     * 
     * @param string|null $key Clé (null pour toutes)
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur ou tableau
     */
    public function json($key = null, $default = null)
    {
        if ($key === null) {
            return $this->json;
        }
        
        return $this->json[$key] ?? $default;
    }
    
    /**
     * Récupère un fichier uploadé
     * 
     * @param string $key Clé du fichier
     * @return array|null Informations du fichier
     */
    public function file($key)
    {
        return $this->files[$key] ?? null;
    }
    
    /**
     * Récupère un cookie
     * 
     * @param string $key Clé du cookie
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur du cookie
     */
    public function cookie($key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }
    
    /**
     * Récupère un header
     * 
     * @param string $key Clé du header
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur du header
     */
    public function header($key, $default = null)
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }
    
    /**
     * Récupère une variable serveur
     * 
     * @param string $key Clé serveur
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur serveur
     */
    public function server($key, $default = null)
    {
        $key = strtoupper($key);
        return $this->server[$key] ?? $default;
    }
    
    // === VÉRIFICATIONS ===
    
    /**
     * Vérifie si une clé existe
     * 
     * @param string $key Clé à vérifier
     * @return bool True si existe
     */
    public function has($key): bool
    {
        return isset($this->get[$key]) || isset($this->post[$key]);
    }
    
    /**
     * Vérifie si une clé existe et n'est pas vide
     * 
     * @param string $key Clé à vérifier
     * @return bool True si remplie
     */
    public function filled($key): bool
    {
        $value = $this->input($key);
        return !empty($value) || $value === '0';
    }
    
    /**
     * Vérifie si une clé est manquante
     * 
     * @param string $key Clé à vérifier
     * @return bool True si manquante
     */
    public function missing($key): bool
    {
        return !$this->has($key);
    }
    
    /**
     * Vérifie si des fichiers ont été uploadés
     * 
     * @param string|null $key Clé spécifique (optionnel)
     * @return bool True si fichiers existent
     */
    public function hasFile($key = null): bool
    {
        if ($key === null) {
            return !empty($this->files);
        }
        
        return isset($this->files[$key]) && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }
    
    // === MÉTHODES HTTP ===
    
    /**
     * Récupère la méthode HTTP
     * 
     * @return string Méthode HTTP
     */
    public function method(): string
    {
        return $this->method;
    }
    
    /**
     * Vérifie si la méthode est GET
     * 
     * @return bool True si GET
     */
    public function isGet(): bool
    {
        return $this->method === 'GET';
    }
    
    /**
     * Vérifie si la méthode est POST
     * 
     * @return bool True si POST
     */
    public function isPost(): bool
    {
        return $this->method === 'POST';
    }
    
    /**
     * Vérifie si la méthode est PUT
     * 
     * @return bool True si PUT
     */
    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }
    
    /**
     * Vérifie si la méthode est PATCH
     * 
     * @return bool True si PATCH
     */
    public function isPatch(): bool
    {
        return $this->method === 'PATCH';
    }
    
    /**
     * Vérifie si la méthode est DELETE
     * 
     * @return bool True si DELETE
     */
    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }
    
    /**
     * Vérifie si la méthode est AJAX
     * 
     * @return bool True si requête AJAX
     */
    public function isAjax(): bool
    {
        $header = $this->header('X-Requested-With');
        return !empty($header) && strtolower($header) === 'xmlhttprequest';
    }
    
    /**
     * Vérifie si la requête est sécurisée (HTTPS)
     * 
     * @return bool True si HTTPS
     */
    public function isSecure(): bool
    {
        $https = $this->server('HTTPS');
        return !empty($https) && strtolower($https) !== 'off';
    }
    
    // === SÉCURITÉ ===
    
    /**
     * Nettoie une valeur ou un tableau
     * 
     * @param mixed $data Données à nettoyer
     * @return mixed Données nettoyées
     */
    public function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        // Ne pas nettoyer certains champs sensibles
        foreach ($this->skipSanitize as $field) {
            if (is_string($data) && stripos($data, $field) !== false) {
                return $data; // Ne pas nettoyer pour éviter de casser les hashs
            }
        }
        
        // Supprimer les tags HTML/XML/PHP
        $data = strip_tags($data);
        
        // Échapper les caractères spéciaux SQL
        $data = $this->escapeSql($data);
        
        // Supprimer les caractères de contrôle
        $data = preg_replace('/[\x00-\x1F\x7F]/u', '', $data);
        
        // Normaliser les espaces
        $data = preg_replace('/\s+/', ' ', trim($data));
        
        return $data;
    }
    
    /**
     * Échappe une chaîne pour l'affichage HTML
     * 
     * @param string $string Chaîne à échapper
     * @return string Chaîne échappée
     */
    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
    }
    
    /**
     * Échappe une chaîne pour SQL (basique)
     * 
     * @param string $string Chaîne à échapper
     * @return string Chaîne échappée
     */
    private function escapeSql(string $string): string
    {
        // Utiliser PDO ou mysqli_real_escape_string si disponible
        if (function_exists('mysqli_real_escape_string') && isset($GLOBALS['db_connection'])) {
            return mysqli_real_escape_string($GLOBALS['db_connection'], $string);
        }
        
        // Fallback basique (à utiliser avec des requêtes préparées de toute façon)
        $search = ['\\', "\0", "\n", "\r", "'", '"', "\x1a"];
        $replace = ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'];
        
        return str_replace($search, $replace, $string);
    }
    
    /**
     * Valide un email
     * 
     * @param string $email Email à valider
     * @return bool True si email valide
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Valide une URL
     * 
     * @param string $url URL à valider
     * @return bool True si URL valide
     */
    public function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Valide une adresse IP
     * 
     * @param string $ip Adresse IP à valider
     * @return bool True si IP valide
     */
    public function validateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Vérifie un token CSRF
     * 
     * @param string $token Token à vérifier
     * @return bool True si token valide
     */
    public function verifyCsrfToken(string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        return hash_equals($sessionToken, $token);
    }
    
    // === UTILITAIRES ===
    
    /**
     * Récupère l'URI actuelle
     * 
     * @return string URI
     */
    public function uri(): string
    {
        return $this->uri;
    }
    
    /**
     * Récupère le chemin de base
     * 
     * @return string Chemin de base
     */
    public function basePath(): string
    {
        $scriptName = $this->server('SCRIPT_NAME');
        return dirname($scriptName);
    }
    
    /**
     * Récupère l'URL complète
     * 
     * @return string URL complète
     */
    public function fullUrl(): string
    {
        $protocol = $this->isSecure() ? 'https://' : 'http://';
        $host = $this->server('HTTP_HOST');
        $uri = $this->server('REQUEST_URI');
        
        return $protocol . $host . $uri;
    }
    
    /**
     * Récupère l'adresse IP du client
     * 
     * @return string Adresse IP
     */
    public function ip(): string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($keys as $key) {
            if ($ip = $this->server($key)) {
                // Peut y avoir plusieurs IPs séparées par des virgules
                $ipList = explode(',', $ip);
                $ip = trim($ipList[0]);
                
                if ($this->validateIp($ip)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Récupère le User-Agent
     * 
     * @return string User-Agent
     */
    public function userAgent(): string
    {
        return $this->server('HTTP_USER_AGENT', '');
    }
    
    /**
     * Récupère le token Bearer d'authentification
     * 
     * @return string|null Token ou null
     */
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');
        
        if (strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }
        
        return null;
    }
    
    /**
     * Récupère les données d'un formulaire spécifique
     * 
     * @param array $fields Champs à récupérer
     * @return array Données filtrées
     */
    public function only(array $fields): array
    {
        $data = $this->all();
        $result = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $result[$field] = $data[$field];
            }
        }
        
        return $result;
    }
    
    /**
     * Récupère toutes les données SAUF celles spécifiées
     * 
     * @param array $fields Champs à exclure
     * @return array Données filtrées
     */
    public function except(array $fields): array
    {
        $data = $this->all();
        
        foreach ($fields as $field) {
            unset($data[$field]);
        }
        
        return $data;
    }
    
    /**
     * Active/désactive le nettoyage automatique
     * 
     * @param bool $enabled État
     */
    public function setAutoSanitize(bool $enabled): void
    {
        $this->autoSanitize = $enabled;
    }
    
    /**
     * Ajoute des champs à ne pas nettoyer
     * 
     * @param array $fields Champs à exclure
     */
    public function addSkipSanitize(array $fields): void
    {
        $this->skipSanitize = array_merge($this->skipSanitize, $fields);
    }
    
    // === MAGIC METHODS ===
    
    /**
     * Getter magique pour un accès rapide
     * 
     * @param string $key Clé
     * @return mixed Valeur
     */
    public function __get($key)
    {
        return $this->input($key);
    }
    
    /**
     * Setter magique (non autorisé)
     * 
     * @param string $key Clé
     * @param mixed $value Valeur
     * @throws Exception
     */
    public function __set($key, $value)
    {
        throw new Exception("Cannot set request data directly");
    }
    
    /**
     * Vérifie si une clé existe (magic)
     * 
     * @param string $key Clé
     * @return bool True si existe
     */
    public function __isset($key)
    {
        return $this->has($key);
    }
}