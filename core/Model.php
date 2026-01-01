<?php
// core/Model.php

/**
 * Classe Model - ORM léger pour les modèles de données
 * Classe abstraite, à étendre par chaque modèle
 */
abstract class Model
{
    // === PROPRIÉTÉS DE BASE ===
    
    /**
     * @var Database Instance de connexion à la base de données
     */
    protected $db;
    
    /**
     * @var string Nom de la table (défini dans la classe enfant)
     */
    protected $table;
    
    /**
     * @var string Nom de la clé primaire (défaut: 'id')
     */
    protected $primaryKey = 'id';
    
    /**
     * @var array Champs autorisés pour l'assignation de masse
     */
    protected $fillable = [];
    
    /**
     * @var array Champs à exclure de l'assignation de masse
     */
    protected $guarded = ['id'];
    
    /**
     * @var bool Active le timestamps automatiques
     */
    protected $timestamps = true;
    
    /**
     * @var array Attributs du modèle
     */
    protected $attributes = [];
    
    /**
     * @var array Attributs originaux (pour détecter les changements)
     */
    protected $original = [];
    
    /**
     * @var bool Indique si le modèle existe en base
     */
    protected $exists = false;
    
    // === PROPRIÉTÉS POUR LE CONSTRUCTEUR DE REQUÊTES ===
    
    /**
     * @var array Conditions WHERE
     */
    protected $wheres = [];
    
    /**
     * @var array Colonnes pour ORDER BY
     */
    protected $orders = [];
    
    /**
     * @var int|null Limite de résultats
     */
    protected $limit = null;
    
    /**
     * @var int Offset pour la pagination
     */
    protected $offset = 0;
    
    /**
     * @var array Colonnes à sélectionner
     */
    protected $columns = ['*'];
    
    // === CONSTRUCTEUR ET INITIALISATION ===
    
    /**
     * Constructeur
     * 
     * @param array $attributes Attributs initiaux
     */
    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        
        // Déterminer automatiquement le nom de la table si non défini
        if (empty($this->table)) {
            $this->table = $this->guessTableName();
        }
        
        // Remplir les attributs si fournis
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
        
        $this->original = $this->attributes;
    }
    
    /**
     * Devine le nom de la table à partir du nom de la classe
     * 
     * @return string Nom de la table
     */
    private function guessTableName(): string
    {
        // Ex: "User" devient "users", "ProductCategory" devient "product_categories"
        $className = (new ReflectionClass($this))->getShortName();
        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        
        // Ajouter 's' pour le pluriel
        if (substr($name, -1) !== 's') {
            $name .= 's';
        }
        
        return $name;
    }
    
    // === MÉTHODES MAGIQUES ===
    
    /**
     * Getter magique pour les attributs
     * 
     * @param string $key Nom de l'attribut
     * @return mixed Valeur de l'attribut
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Setter magique pour les attributs
     * 
     * @param string $key Nom de l'attribut
     * @param mixed $value Valeur à définir
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Vérifie si un attribut existe
     * 
     * @param string $key Nom de l'attribut
     * @return bool True si l'attribut existe
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }
    
    /**
     * Supprime un attribut
     * 
     * @param string $key Nom de l'attribut
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
    
    // === GESTION DES ATTRIBUTS ===
    
    /**
     * Récupère un attribut
     * 
     * @param string $key Nom de l'attribut
     * @return mixed Valeur de l'attribut
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
        
        // Essayer d'appeler une méthode accesseur
        $method = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        if (method_exists($this, $method)) {
            return $this->$method();
        }
        
        return null;
    }
    
    /**
     * Définit un attribut
     * 
     * @param string $key Nom de l'attribut
     * @param mixed $value Valeur à définir
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        // Vérifier si le champ est fillable
        if ($this->isFillable($key)) {
            $this->attributes[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Remplit les attributs avec un tableau
     * 
     * @param array $attributes Attributs à remplir
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * Vérifie si un champ est "fillable"
     * 
     * @param string $key Nom du champ
     * @return bool True si le champ est fillable
     */
    protected function isFillable($key): bool
    {
        // Si guarded contient '*', tout est protégé sauf fillable
        if (in_array('*', $this->guarded)) {
            return in_array($key, $this->fillable);
        }
        
        // Si fillable n'est pas vide, on utilise fillable
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }
        
        // Sinon, on exclut seulement les champs guarded
        return !in_array($key, $this->guarded);
    }
    
    /**
     * Récupère tous les attributs
     * 
     * @return array Tous les attributs
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Convertit le modèle en tableau
     * 
     * @return array Tableau des attributs
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
    
    /**
     * Convertit le modèle en JSON
     * 
     * @return string Représentation JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
    
    // === MÉTHODES CRUD DE BASE ===
    
    /**
     * Récupère tous les enregistrements
     * 
     * @return array Tableau d'instances du modèle
     */
    public function all(): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `deleted_at` IS NULL";
        $results = $this->db->fetchAll($sql);
        
        return $this->hydrate($results);
    }
    
    /**
     * Récupère un enregistrement par son ID
     * 
     * @param int $id ID de l'enregistrement
     * @return static|null Instance du modèle ou null
     */
    public function find($id): ?self
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id AND `deleted_at` IS NULL";
        $result = $this->db->fetch($sql, [':id' => $id]);
        
        if ($result) {
            return $this->newFromBuilder($result);
        }
        
        return null;
    }
    
    /**
     * Récupère un enregistrement ou échoue
     * 
     * @param int $id ID de l'enregistrement
     * @return static Instance du modèle
     * @throws Exception Si non trouvé
     */
    public function findOrFail($id): self
    {
        $model = $this->find($id);
        
        if (!$model) {
            throw new Exception("{$this->table} avec ID {$id} non trouvé");
        }
        
        return $model;
    }
    
    /**
     * Récupère le premier enregistrement correspondant aux conditions
     * 
     * @param string $column Colonne
     * @param mixed $value Valeur
     * @return static|null Instance du modèle ou null
     */
    public function first(): ?self
    {
        $this->limit(1);
        $results = $this->get();
        
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Récupère par colonne et valeur
     * 
     * @param string $column Colonne
     * @param mixed $value Valeur
     * @return static|null Instance du modèle ou null
     */
    public function findBy($column, $value): ?self
    {
        return $this->where($column, $value)->first();
    }
    
    /**
     * Récupère tous les enregistrements par colonne et valeur
     * 
     * @param string $column Colonne
     * @param mixed $value Valeur
     * @return array Tableau d'instances du modèle
     */
    public function findAllBy($column, $value): array
    {
        return $this->where($column, $value)->get();
    }
    
    /**
     * Sauvegarde le modèle (création ou mise à jour)
     * 
     * @return bool Succès de l'opération
     */
    public function save(): bool
    {
        // Préparer les données pour l'insertion/mise à jour
        $data = $this->getAttributes();
        
        // Gérer les timestamps
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            
            if (!$this->exists) {
                $data['created_at'] = $now;
            }
            $data['updated_at'] = $now;
        }
        
        // Filtrer les données NULL pour l'insertion
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });
        
        if ($this->exists) {
            // Mise à jour
            $id = $this->getAttribute($this->primaryKey);
            return $this->updateModel($id, $data);
        } else {
            // Création
            return $this->createModel($data);
        }
    }
    
    /**
     * Crée un nouvel enregistrement
     * 
     * @param array $data Données à insérer
     * @return static|null Instance du modèle créé
     */
    public function create(array $data): ?self
    {
        $model = new static($data);
        
        if ($model->save()) {
            return $model;
        }
        
        return null;
    }
    
    /**
     * Met à jour un enregistrement
     * 
     * @param int $id ID de l'enregistrement
     * @param array $data Données à mettre à jour
     * @return bool Succès de l'opération
     */
    public function update($id, array $data): bool
    {
        // Nettoyer les données
        $cleanData = [];
        foreach ($data as $key => $value) {
            if ($this->isFillable($key)) {
                $cleanData[$key] = $value;
            }
        }
        
        // Ajouter updated_at si timestamps activés
        if ($this->timestamps && !isset($cleanData['updated_at'])) {
            $cleanData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->updateModel($id, $cleanData);
    }
    
    /**
     * Supprime un enregistrement
     * 
     * @param int $id ID de l'enregistrement
     * @return bool Succès de l'opération
     */
    public function delete($id = null): bool
    {
        // Si pas d'ID fourni et modèle existe, supprimer ce modèle
        if ($id === null && $this->exists) {
            $id = $this->getAttribute($this->primaryKey);
        }
        
        if ($id) {
            // Soft delete si la colonne existe
            $columns = $this->getColumns();
            
            if (in_array('deleted_at', $columns)) {
                $sql = "UPDATE `{$this->table}` SET `deleted_at` = NOW() WHERE `{$this->primaryKey}` = :id";
            } else {
                $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
            }
            
            return $this->db->execute($sql, [':id' => $id]) > 0;
        }
        
        return false;
    }
    
    /**
     * Supprime définitivement un enregistrement
     * 
     * @param int $id ID de l'enregistrement
     * @return bool Succès de l'opération
     */
    public function forceDelete($id): bool
    {
        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        return $this->db->execute($sql, [':id' => $id]) > 0;
    }
    
    // === CONSTRUCTEUR DE REQUÊTES (FLUENT INTERFACE) ===
    
    /**
     * Ajoute une condition WHERE
     * 
     * @param string $column Colonne
     * @param mixed $operator Opérateur ou valeur
     * @param mixed $value Valeur (si opérateur fourni)
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        // Si seulement 2 arguments: where('colonne', 'valeur')
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Ajoute une condition OR WHERE
     * 
     * @param string $column Colonne
     * @param mixed $operator Opérateur ou valeur
     * @param mixed $value Valeur (si opérateur fourni)
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        // Si seulement 2 arguments: orWhere('colonne', 'valeur')
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];
        
        return $this;
    }
    
    /**
     * Ajoute une condition WHERE IN
     * 
     * @param string $column Colonne
     * @param array $values Valeurs
     * @return $this
     */
    public function whereIn($column, array $values)
    {
        $this->wheres[] = [
            'column' => $column,
            'operator' => 'IN',
            'value' => $values,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * Ajoute un ORDER BY
     * 
     * @param string $column Colonne
     * @param string $direction Direction (ASC|DESC)
     * @return $this
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        
        return $this;
    }
    
    /**
     * Ajoute une limite
     * 
     * @param int $limit Nombre maximum de résultats
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }
    
    /**
     * Ajoute un offset
     * 
     * @param int $offset Offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }
    
    /**
     * Sélectionne des colonnes spécifiques
     * 
     * @param mixed $columns Colonnes à sélectionner
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    /**
     * Exécute la requête construite et récupère les résultats
     * 
     * @return array Tableau d'instances du modèle
     */
    public function get(): array
    {
        $query = $this->buildQuery();
        $results = $this->db->fetchAll($query['sql'], $query['params']);
        
        // Réinitialiser le constructeur de requêtes
        $this->resetBuilder();
        
        return $this->hydrate($results);
    }
    
    /**
     * Compte le nombre de résultats
     * 
     * @return int Nombre de résultats
     */
    public function count(): int
    {
        $query = $this->buildQuery(true);
        return (int) $this->db->fetchColumn($query['sql'], $query['params']);
    }
    
    /**
     * Pagine les résultats
     * 
     * @param int $page Numéro de page
     * @param int $perPage Nombre d'éléments par page
     * @return array Résultats paginés
     */
    public function paginate($page = 1, $perPage = 15): array
    {
        $page = max(1, (int) $page);
        $offset = ($page - 1) * $perPage;
        
        $total = $this->count();
        $this->limit($perPage)->offset($offset);
        
        $items = $this->get();
        
        return [
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => ($total > 0) ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    // === MÉTHODES INTERNES ===
    
    /**
     * Construit la requête SQL
     * 
     * @param bool $countOnly Si true, construit un COUNT(*)
     * @return array SQL et paramètres
     */
    private function buildQuery(bool $countOnly = false): array
    {
        $params = [];
        $sql = 'SELECT ';
        
        if ($countOnly) {
            $sql .= 'COUNT(*) as count';
        } else {
            $sql .= implode(', ', $this->columns);
        }
        
        $sql .= " FROM `{$this->table}` WHERE `deleted_at` IS NULL";
        
        // Ajouter les conditions WHERE
        if (!empty($this->wheres)) {
            $whereClauses = [];
            
            foreach ($this->wheres as $index => $where) {
                $paramName = ':where_' . $index;
                
                if ($where['operator'] === 'IN') {
                    // Gérer WHERE IN
                    $inParams = [];
                    foreach ($where['value'] as $inIndex => $value) {
                        $inParamName = $paramName . '_' . $inIndex;
                        $inParams[] = $inParamName;
                        $params[$inParamName] = $value;
                    }
                    $whereClauses[] = "{$where['boolean']} `{$where['column']}` IN (" . implode(', ', $inParams) . ")";
                } else {
                    // Gérer les opérateurs normaux
                    $whereClauses[] = "{$where['boolean']} `{$where['column']}` {$where['operator']} {$paramName}";
                    $params[$paramName] = $where['value'];
                }
            }
            
            // Supprimer le premier AND/OR
            $firstWhere = array_shift($whereClauses);
            $firstWhere = preg_replace('/^(AND|OR) /', '', $firstWhere);
            
            $sql .= ' AND ' . $firstWhere;
            
            if (!empty($whereClauses)) {
                $sql .= ' ' . implode(' ', $whereClauses);
            }
        }
        
        // Ajouter ORDER BY
        if (!$countOnly && !empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = "`{$order['column']}` {$order['direction']}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }
        
        // Ajouter LIMIT et OFFSET
        if (!$countOnly && $this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            
            if ($this->offset > 0) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        
        return ['sql' => $sql, 'params' => $params];
    }
    
    /**
     * Réinitialise le constructeur de requêtes
     */
    private function resetBuilder(): void
    {
        $this->wheres = [];
        $this->orders = [];
        $this->limit = null;
        $this->offset = 0;
        $this->columns = ['*'];
    }
    
    /**
     * Crée un modèle à partir d'un résultat de base de données
     * 
     * @param array $attributes Attributs
     * @return static Instance du modèle
     */
    private function newFromBuilder(array $attributes): self
    {
        $model = new static();
        $model->fill($attributes);
        $model->exists = true;
        $model->original = $attributes;
        
        return $model;
    }
    
    /**
     * Hydrate plusieurs résultats
     * 
     * @param array $results Résultats de la base
     * @return array Tableau d'instances du modèle
     */
    private function hydrate(array $results): array
    {
        $models = [];
        
        foreach ($results as $result) {
            $models[] = $this->newFromBuilder($result);
        }
        
        return $models;
    }
    
    /**
     * Crée un nouvel enregistrement en base
     * 
     * @param array $data Données à insérer
     * @return bool Succès de l'opération
     */
    private function createModel(array $data): bool
    {
        $columns = implode('`, `', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO `{$this->table}` (`{$columns}`) VALUES ({$placeholders})";
        
        try {
            $this->db->execute($sql, $data);
            
            // Récupérer l'ID inséré
            $lastId = $this->db->lastInsertId();
            if ($lastId) {
                $this->setAttribute($this->primaryKey, $lastId);
                $this->exists = true;
                
                // Recharger depuis la base pour avoir toutes les valeurs
                $fresh = $this->find($lastId);
                if ($fresh) {
                    $this->attributes = $fresh->attributes;
                    $this->original = $fresh->original;
                }
            }
            
            return true;
        } catch (Exception $e) {
            // Log l'erreur
            error_log("Erreur création {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour un enregistrement en base
     * 
     * @param int $id ID de l'enregistrement
     * @param array $data Données à mettre à jour
     * @return bool Succès de l'opération
     */
    private function updateModel($id, array $data): bool
    {
        if (empty($data)) {
            return true; // Rien à mettre à jour
        }
        
        $set = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $set[] = "`{$key}` = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        $setClause = implode(', ', $set);
        $sql = "UPDATE `{$this->table}` SET {$setClause} WHERE `{$this->primaryKey}` = :id";
        
        try {
            $success = $this->db->execute($sql, $params) > 0;
            
            if ($success) {
                // Mettre à jour les attributs
                foreach ($data as $key => $value) {
                    $this->setAttribute($key, $value);
                }
                
                // Mettre à jour l'original
                $this->original = array_merge($this->original, $data);
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Erreur mise à jour {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère les colonnes de la table
     * 
     * @return array Liste des colonnes
     */
    private function getColumns(): array
    {
        $sql = "SHOW COLUMNS FROM `{$this->table}`";
        $columns = $this->db->fetchAll($sql);
        
        return array_column($columns, 'Field');
    }
    
    // === MÉTHODES STATIQUES POUR FACILITÉ D'UTILISATION ===
    
    /**
     * Crée une nouvelle instance du modèle
     * 
     * @return static Nouvelle instance
     */
    public static function make(): self
    {
        return new static();
    }
    
    /**
     * Récupère tous les enregistrements (méthode statique)
     * 
     * @return array Tableau d'instances
     */
    public static function allStatic(): array
    {
        return (new static())->all();
    }
    
    /**
     * Récupère un enregistrement par ID (méthode statique)
     * 
     * @param int $id ID de l'enregistrement
     * @return static|null Instance ou null
     */
    public static function findStatic($id): ?self
    {
        return (new static())->find($id);
    }
    
    /**
     * Crée un nouvel enregistrement (méthode statique)
     * 
     * @param array $data Données à insérer
     * @return static|null Instance créée ou null
     */
    public static function createStatic(array $data): ?self
    {
        return (new static())->create($data);
    }
}