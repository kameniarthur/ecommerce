<?php
// core/Database.php

/**
 * Classe Database - Gestionnaire de base de données PDO avec Singleton
 */
class Database
{
    // === PROPRIÉTÉS ===
    
    /**
     * @var string $host - Hôte de la base de données
     */
    private $host;
    
    /**
     * @var string $dbname - Nom de la base de données
     */
    private $dbname;
    
    /**
     * @var string $username - Nom d'utilisateur
     */
    private $username;
    
    /**
     * @var string $password - Mot de passe
     */
    private $password;
    
    /**
     * @var PDO|null $conn - Instance de connexion PDO
     */
    private $conn = null;
    
    /**
     * @var Database|null $instance - Instance unique de la classe (Singleton)
     */
    private static $instance = null;
    
    // === CONSTRUCTEUR PRIVÉ (Singleton) ===
    
    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct()
    {
        // Récupération des constantes depuis config.php
        $this->host = defined('DB_HOST') ? DB_HOST : 'localhost';
        $this->dbname = defined('DB_NAME') ? DB_NAME : 'ecommerce';
        $this->username = defined('DB_USER') ? DB_USER : 'root';
        $this->password = defined('DB_PASS') ? DB_PASS : '';
        
        $this->connect();
    }
    
    /**
     * Établit la connexion à la base de données
     * 
     * @throws PDOException Si la connexion échoue
     * @return void
     */
    private function connect(): void
{
    try {
        // 1. D'abord se connecter SANS spécifier la base de données
        $tempDsn = "mysql:host={$this->host};charset=utf8mb4";
        $tempPdo = new PDO($tempDsn, $this->username, $this->password);
        $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 2. Créer la base de données si elle n'existe pas
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->dbname}` 
                        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // 3. Maintenant se connecter AVEC la base de données
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Utilisez la constante adaptée à votre version de PHP :
            // Pour PHP 8.5+ :
            (defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? PDO::MYSQL_ATTR_INIT_COMMAND : \Pdo\Mysql::ATTR_INIT_COMMAND) 
                => "SET NAMES utf8mb4"
        ];
        
        $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        
    } catch (PDOException $e) {
        $this->handleError($e, "Connexion à la base de données");
        // Important : ne pas laisser $this->conn = null
        throw $e; // Propager l'exception
    }
}
    
    // === MÉTHODE SINGLETON ===
    
    /**
     * Récupère l'instance unique de la classe
     * 
     * @return Database Instance unique
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    // === MÉTHODES D'EXÉCUTION DE REQUÊTES ===
    
    /**
     * Exécute une requête SQL (INSERT, UPDATE, DELETE)
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres préparés
     * @return int Nombre de lignes affectées
     */
    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            $this->handleError($e, "Exécution de requête", $sql, $params);
            return 0;
        }
    }
    
    /**
     * Exécute une requête SQL et retourne le résultat
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres préparés
     * @return PDOStatement Statement exécuté
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
            
        } catch (PDOException $e) {
            $this->handleError($e, "Query SQL", $sql, $params);
            throw $e;
        }
    }
    
    /**
     * Récupère toutes les lignes d'un résultat
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres préparés
     * @return array Tableau de résultats
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $this->handleError($e, "Fetch All", $sql, $params);
            return [];
        }
    }
    
    /**
     * Récupère une seule ligne
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres préparés
     * @return array|null Ligne unique ou null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
            
        } catch (PDOException $e) {
            $this->handleError($e, "Fetch Single", $sql, $params);
            return null;
        }
    }
    
    /**
     * Récupère une seule valeur (première colonne de la première ligne)
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres préparés
     * @return mixed Valeur unique
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            $this->handleError($e, "Fetch Column", $sql, $params);
            return null;
        }
    }
    
    // === TRANSACTIONS ===
    
    /**
     * Démarre une transaction
     * 
     * @return bool Succès
     */
    public function beginTransaction(): bool
    {
        try {
            return $this->conn->beginTransaction();
        } catch (PDOException $e) {
            $this->handleError($e, "Begin Transaction");
            return false;
        }
    }
    
    /**
     * Valide une transaction
     * 
     * @return bool Succès
     */
    public function commit(): bool
    {
        try {
            return $this->conn->commit();
        } catch (PDOException $e) {
            $this->handleError($e, "Commit Transaction");
            return false;
        }
    }
    
    /**
     * Annule une transaction
     * 
     * @return bool Succès
     */
    public function rollback(): bool
    {
        try {
            return $this->conn->rollBack();
        } catch (PDOException $e) {
            $this->handleError($e, "Rollback Transaction");
            return false;
        }
    }
    
    /**
     * Exécute une fonction dans une transaction
     * 
     * @param callable $callback Fonction à exécuter
     * @return bool Succès
     */
    public function transaction(callable $callback): bool
    {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            
            if ($result === false) {
                $this->rollback();
                return false;
            }
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            $this->handleError($e, "Transaction");
            return false;
        }
    }
    
    // === MÉTHODES UTILITAIRES ===
    
    /**
     * Retourne le dernier ID inséré
     * 
     * @return string Dernier ID
     */
    public function lastInsertId(): string
    {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Retourne l'instance PDO interne
     * 
     * @return PDO Instance PDO
     */
    public function getConnection(): PDO
    {
        return $this->conn;
    }
    
    /**
     * Vérifie si une table existe
     * 
     * @param string $tableName Nom de la table
     * @return bool Existe ou non
     */
    public function tableExists(string $tableName): bool
    {
        $sql = "SHOW TABLES LIKE :table";
        $result = $this->fetch($sql, [':table' => $tableName]);
        return !empty($result);
    }
    
    /**
     * Échappe une valeur pour LIKE
     * 
     * @param string $value Valeur à échapper
     * @return string Valeur échappée
     */
    public function escapeLike(string $value): string
    {
        return str_replace(
            ['%', '_', '\\'],
            ['\%', '\_', '\\\\'],
            $value
        );
    }
    
    // === GESTION DES ERREURS ===
    
    /**
     * Gère les erreurs PDO de manière sécurisée
     * 
     * @param PDOException $e Exception
     * @param string $context Contexte de l'erreur
     * @param string|null $sql Requête SQL (optionnel)
     * @param array $params Paramètres (optionnel)
     * @return void
     */
    private function handleError(PDOException $e, string $context, ?string $sql = null, array $params = []): void
    {
        // Récupération des informations d'erreur
        $errorInfo = [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'context' => $context,
            'sql' => $sql,
            'params' => $params,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        
        // Log selon l'environnement
        $this->logError($errorInfo);
        
        // Affichage selon l'environnement
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            $this->displayErrorDev($errorInfo);
        } else {
            $this->displayErrorProd();
        }
    }
    
    /**
     * Log l'erreur dans un fichier
     * 
     * @param array $errorInfo Informations d'erreur
     * @return void
     */
    private function logError(array $errorInfo): void
    {
        $logMessage = sprintf(
            "[%s] ERREUR BDD (%s): %s\nSQL: %s\nParams: %s\nTrace: %s\n\n",
            date('Y-m-d H:i:s'),
            $errorInfo['context'],
            $errorInfo['message'],
            $errorInfo['sql'] ?? 'N/A',
            json_encode($errorInfo['params']),
            $errorInfo['trace']
        );
        
        $logFile = defined('ROOT_PATH') ? ROOT_PATH . '/logs/db_errors.log' : 'db_errors.log';
        
        // Crée le dossier logs s'il n'existe pas
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0777, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Affiche l'erreur en mode développement
     * 
     * @param array $errorInfo Informations d'erreur
     * @return void
     */
    private function displayErrorDev(array $errorInfo): void
    {
        echo '<div style="
            background: #ffebee;
            border: 2px solid #f44336;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            color: #b71c1c;
        ">';
        
        echo "<h3 style='margin-top:0;color:#d32f2f;'>⚠️ Erreur BDD : {$errorInfo['context']}</h3>";
        echo "<p><strong>Message :</strong> {$errorInfo['message']}</p>";
        echo "<p><strong>Code :</strong> {$errorInfo['code']}</p>";
        
        if ($errorInfo['sql']) {
            echo "<p><strong>Requête SQL :</strong><br><code>{$errorInfo['sql']}</code></p>";
        }
        
        if (!empty($errorInfo['params'])) {
            echo "<p><strong>Paramètres :</strong><br><pre>" . print_r($errorInfo['params'], true) . "</pre></p>";
        }
        
        echo "<p><strong>Fichier :</strong> {$errorInfo['file']}:{$errorInfo['line']}</p>";
        echo "</div>";
    }
    
    /**
     * Affiche une erreur générique en production
     * 
     * @return void
     */
    private function displayErrorProd(): void
    {
        if (!headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }
        
        echo '<div style="
            text-align: center;
            padding: 50px;
            font-family: Arial, sans-serif;
        ">';
        echo '<h2>Une erreur technique est survenue</h2>';
        echo '<p>Notre équipe technique a été notifiée. Veuillez réessayer ultérieurement.</p>';
        
        if (defined('ADMIN_EMAIL')) {
            echo '<p>Si le problème persiste, contactez : ' . ADMIN_EMAIL . '</p>';
        }
        echo '</div>';
    }
    
    // === MAGIC METHODS ===
    
    /**
     * Empêche le clonage (Singleton)
     */
    private function __clone() {}
    
    /**
     * Empêche la désérialisation (Singleton)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Vérifie la connexion
     * 
     * @return bool Connexion active
     */
    public function isConnected(): bool
    {
        try {
            $this->conn->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Réinitialise la connexion
     * 
     * @return bool Succès
     */
    public function reconnect(): bool
    {
        $this->conn = null;
        $this->connect();
        return $this->isConnected();
    }
    
    /**
     * Retourne des statistiques sur la base
     * 
     * @return array Statistiques
     */
    public function getStats(): array
    {
        return [
            'connected' => $this->isConnected(),
            'host' => $this->host,
            'database' => $this->dbname,
            'driver' => $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME),
            'server_version' => $this->conn->getAttribute(PDO::ATTR_SERVER_VERSION),
            'client_version' => $this->conn->getAttribute(PDO::ATTR_CLIENT_VERSION),
        ];
    }
}

// === FONCTION HELPER GLOBALE ===

/**
 * Fonction helper pour accéder rapidement à la base de données
 * 
 * @return Database Instance de Database
 */
function db(): Database
{
    return Database::getInstance();
}