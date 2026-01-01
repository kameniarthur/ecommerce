<?php
// app/helpers/functions.php

if (!function_exists('formatPrice')) {
    /**
     * Formater un prix
     * 
     * @param float $price Prix à formater
     * @param string $currency Devise (par défaut: FCFA)
     * @param int $decimals Nombre de décimales
     * @return string Prix formaté
     */
    function formatPrice($price, $currency = 'FCFA', $decimals = 0): string
    {
        $price = (float) $price;
        
        // Format français : espace comme séparateur de milliers, virgule pour décimales
        $formatted = number_format($price, $decimals, ',', ' ');
        
        // Ajouter la devise
        switch (strtoupper($currency)) {
            case 'FCFA':
                return $formatted . ' FCFA';
            case 'EUR':
                return $formatted . ' €';
            case 'USD':
                return '$' . $formatted;
            case 'XAF':
                return $formatted . ' FCFA';
            default:
                return $formatted . ' ' . $currency;
        }
    }
}

if (!function_exists('formatDate')) {
    /**
     * Formater une date
     * 
     * @param string $date Date à formater
     * @param string $format Format de sortie
     * @return string Date formatée
     */
    function formatDate($date, $format = 'd/m/Y'): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return '';
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        if ($timestamp === false) {
            return $date;
        }
        
        // Formats prédéfinis
        $formats = [
            'short' => 'd/m/Y',
            'long' => 'd F Y',
            'datetime' => 'd/m/Y H:i',
            'time' => 'H:i',
            'iso' => 'Y-m-d',
            'humain' => 'l j F Y'
        ];
        
        $format = $formats[$format] ?? $format;
        
        // Traduction française des mois et jours
        $englishMonths = ['January', 'February', 'March', 'April', 'May', 'June', 
                         'July', 'August', 'September', 'October', 'November', 'December'];
        $frenchMonths = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 
                        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        
        $englishDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $frenchDays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        
        $dateString = date($format, $timestamp);
        $dateString = str_replace($englishMonths, $frenchMonths, $dateString);
        $dateString = str_replace($englishDays, $frenchDays, $dateString);
        
        return $dateString;
    }
}

if (!function_exists('sanitize')) {
    /**
     * Nettoyer les données
     * 
     * @param mixed $data Données à nettoyer
     * @return mixed Données nettoyées
     */
    function sanitize($data)
    {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        
        // Ne pas nettoyer les champs sensibles
        $sensitiveFields = ['password', 'confirm_password', 'csrf_token', 'token'];
        foreach ($sensitiveFields as $field) {
            if (stripos((string) $data, $field) !== false) {
                return $data;
            }
        }
        
        // Supprimer les balises HTML
        $data = strip_tags($data);
        
        // Supprimer les espaces inutiles
        $data = trim($data);
        
        // Échapper les caractères spéciaux
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $data;
    }
}

if (!function_exists('escape')) {
    /**
     * Échapper une chaîne pour l'affichage HTML
     * 
     * @param string $string Chaîne à échapper
     * @return string Chaîne échappée
     */
    function escape($string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
    }
}

if (!function_exists('redirect')) {
    /**
     * Rediriger vers une URL
     * 
     * @param string $url URL de destination
     */
    function redirect($url): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        }
        
        echo '<script>window.location.href = "' . $url . '";</script>';
        exit;
    }
}

if (!function_exists('old')) {
    /**
     * Récupérer une ancienne valeur de formulaire
     * 
     * @param string $key Clé de la valeur
     * @param mixed $default Valeur par défaut
     * @return mixed Ancienne valeur
     */
    function old($key, $default = null)
    {
        return $_SESSION['old_input'][$key] ?? $default;
    }
}

if (!function_exists('asset')) {
    /**
     * Générer l'URL d'un asset
     * 
     * @param string $path Chemin de l'asset
     * @return string URL complète
     */
    function asset($path): string
    {
        $baseUrl = defined('SITE_URL') ? SITE_URL : '';
        
        // Enlever le slash initial si présent
        $path = ltrim($path, '/');
        
        // Si le chemin commence déjà par public/, on l'enlève
        if (strpos($path, 'public/') === 0) {
            $path = substr($path, 7);
        }
        
        return rtrim($baseUrl, '/') . '/public/' . $path;
    }
}

if (!function_exists('url')) {
    /**
     * Générer une URL
     * 
     * @param string $path Chemin
     * @param array $query Paramètres de requête
     * @return string URL complète
     */
    function url($path = '', array $query = []): string
    {
        $baseUrl = defined('SITE_URL') ? SITE_URL : '';
        
        // Construire l'URL de base
        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        
        // Ajouter les paramètres de requête
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        
        return $url;
    }
}

if (!function_exists('isActive')) {
    /**
     * Vérifier si une route est active
     * 
     * @param string $route Route à vérifier
     * @param string $output Classe CSS à retourner si active
     * @return string Classe CSS ou chaîne vide
     */
    function isActive($route, $output = 'active'): string
    {
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        $currentPath = parse_url($currentUri, PHP_URL_PATH);
        
        // Nettoyer les slashes
        $route = '/' . trim($route, '/');
        $currentPath = '/' . trim($currentPath, '/');
        
        // Vérifier l'égalité exacte
        if ($currentPath === $route) {
            return $output;
        }
        
        // Vérifier si la route commence par le chemin courant (pour les sous-routes)
        if ($route !== '/' && strpos($currentPath, $route . '/') === 0) {
            return $output;
        }
        
        return '';
    }
}

if (!function_exists('generateSlug')) {
    /**
     * Générer un slug URL-friendly
     * 
     * @param string $string Chaîne à convertir
     * @param string $separator Séparateur
     * @return string Slug généré
     */
    function generateSlug($string, $separator = '-'): string
    {
        // Convertir en minuscules
        $string = mb_strtolower($string, 'UTF-8');
        
        // Remplacer les accents
        $accents = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ÿ' => 'y',
            'æ' => 'ae', 'œ' => 'oe'
        ];
        
        $string = strtr($string, $accents);
        
        // Remplacer tout ce qui n'est pas alphanumérique ou espace par le séparateur
        $string = preg_replace('/[^a-z0-9\s]/', '', $string);
        
        // Remplacer les espaces par le séparateur
        $string = preg_replace('/\s+/', $separator, trim($string));
        
        // Supprimer les séparateurs multiples
        $string = preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $string);
        
        return $string;
    }
}

if (!function_exists('truncate')) {
    /**
     * Tronquer un texte
     * 
     * @param string $text Texte à tronquer
     * @param int $length Longueur maximale
     * @param string $suffix Suffixe à ajouter
     * @return string Texte tronqué
     */
    function truncate($text, $length = 100, $suffix = '...'): string
    {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }
        
        $text = mb_substr($text, 0, $length, 'UTF-8');
        
        // S'assurer qu'on ne coupe pas un mot au milieu
        $lastSpace = mb_strrpos($text, ' ', 0, 'UTF-8');
        
        if ($lastSpace !== false) {
            $text = mb_substr($text, 0, $lastSpace, 'UTF-8');
        }
        
        return $text . $suffix;
    }
}

if (!function_exists('timeAgo')) {
    /**
     * Afficher "il y a X temps"
     * 
     * @param string $date Date à comparer
     * @return string Texte formaté
     */
    function timeAgo($date): string
    {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        if ($timestamp === false) {
            return $date;
        }
        
        $now = time();
        $diff = $now - $timestamp;
        
        // Moins d'une minute
        if ($diff < 60) {
            return 'à l\'instant';
        }
        
        // Moins d'une heure
        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return 'il y a ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        
        // Moins d'un jour
        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return 'il y a ' . $hours . ' heure' . ($hours > 1 ? 's' : '');
        }
        
        // Moins d'une semaine
        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return 'il y a ' . $days . ' jour' . ($days > 1 ? 's' : '');
        }
        
        // Moins d'un mois
        if ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return 'il y a ' . $weeks . ' semaine' . ($weeks > 1 ? 's' : '');
        }
        
        // Moins d'un an
        if ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return 'il y a ' . $months . ' mois';
        }
        
        // Plus d'un an
        $years = floor($diff / 31536000);
        return 'il y a ' . $years . ' an' . ($years > 1 ? 's' : '');
    }
}

if (!function_exists('generateToken')) {
    /**
     * Générer un token CSRF
     * 
     * @param bool $storeInSession Stocker dans la session
     * @return string Token généré
     */
    function generateToken($storeInSession = true): string
    {
        $token = bin2hex(random_bytes(32));
        
        if ($storeInSession) {
            $_SESSION['csrf_token'] = $token;
        }
        
        return $token;
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Générer un champ CSRF hidden
     * 
     * @return string HTML du champ
     */
    function csrf_field(): string
    {
        $token = $_SESSION['csrf_token'] ?? generateToken();
        
        return '<input type="hidden" name="csrf_token" value="' . escape($token) . '">';
    }
}

if (!function_exists('validate_csrf')) {
    /**
     * Valider un token CSRF
     * 
     * @param string $token Token à valider
     * @return bool True si valide
     */
    function validate_csrf($token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        
        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die - Fonction de debug
     * 
     * @param mixed $data Données à afficher
     */
    function dd($data): void
    {
        echo '<pre style="
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 5px;
            font-family: Consolas, monospace;
            font-size: 14px;
            line-height: 1.5;
            margin: 20px;
            overflow: auto;
            border-left: 5px solid #569cd6;
        ">';
        
        if (is_bool($data)) {
            echo $data ? 'true' : 'false';
        } elseif (is_null($data)) {
            echo 'null';
        } else {
            print_r($data);
        }
        
        echo '</pre>';
        
        die();
    }
}

if (!function_exists('config')) {
    /**
     * Récupérer une configuration
     * 
     * @param string $key Clé de configuration
     * @param mixed $default Valeur par défaut
     * @return mixed Valeur de configuration
     */
    function config($key, $default = null)
    {
        static $config = null;
        
        if ($config === null) {
            $configFile = ROOT_PATH . '/config/app.php';
            
            if (file_exists($configFile)) {
                $config = require $configFile;
            } else {
                $config = [];
            }
        }
        
        return $config[$key] ?? $default;
    }
}

if (!function_exists('session')) {
    /**
     * Gérer la session
     * 
     * @param string $key Clé de session
     * @param mixed $value Valeur à définir (optionnel)
     * @return mixed Valeur de session
     */
    function session($key, $value = null)
    {
        if ($value === null) {
            return $_SESSION[$key] ?? null;
        }
        
        $_SESSION[$key] = $value;
        return $value;
    }
}

if (!function_exists('flash')) {
    /**
     * Gérer les messages flash
     * 
     * @param string $type Type du message
     * @param string $message Message (optionnel)
     * @return mixed Message ou tableau de messages
     */
    function flash($type = null, $message = null)
    {
        if ($type === null && $message === null) {
            return $_SESSION['flash'] ?? [];
        }
        
        if ($message === null) {
            $message = $_SESSION['flash'][$type] ?? null;
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        
        $_SESSION['flash'][$type] = $message;
        return $message;
    }
}

if (!function_exists('method_field')) {
    /**
     * Générer un champ pour les méthodes HTTP spoofées
     * 
     * @param string $method Méthode HTTP (PUT, PATCH, DELETE)
     * @return string HTML du champ
     */
    function method_field($method): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('getFileExtension')) {
    /**
     * Récupérer l'extension d'un fichier
     * 
     * @param string $filename Nom du fichier
     * @return string Extension
     */
    function getFileExtension($filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}

if (!function_exists('isImage')) {
    /**
     * Vérifier si un fichier est une image
     * 
     * @param string $filename Nom du fichier
     * @return bool True si c'est une image
     */
    function isImage($filename): bool
    {
        $ext = getFileExtension($filename);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        
        return in_array($ext, $imageExtensions);
    }
}

if (!function_exists('getCurrentUrl')) {
    /**
     * Récupérer l'URL courante
     * 
     * @param bool $withQuery Inclure la query string
     * @return string URL courante
     */
    function getCurrentUrl($withQuery = true): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        if (!$withQuery && ($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        return $protocol . $host . $uri;
    }
}