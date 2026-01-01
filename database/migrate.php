<?php
// database/migrate_final.php
echo "================================\n";
echo "MIGRATION FINALE\n";
echo "================================\n\n";

require_once __DIR__ . '/../config.php';

// Désactiver temporairement les contraintes
Database::getInstance()->execute("SET FOREIGN_KEY_CHECKS = 0");

$migrations = [
    '001_create_users_table.sql',
    '002_create_categories_table.sql',
    '003_create_products_table.sql',
    '004_create_orders_table.sql', 
    '005_create_order_items_table.sql',
    '006_create_reviews_table.sql',
    '007_create_payments_table.sql'
];

$db = Database::getInstance();

foreach ($migrations as $migration) {
    $file = __DIR__ . '/migrations/' . $migration;
    
    if (!file_exists($file)) {
        echo "⚠️  Fichier non trouvé: $migration\n";
        continue;
    }
    
    echo "🔧 Exécution: $migration\n";
    
    $sql = file_get_contents($file);
    
    // Exécuter chaque requête séparément
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $success = 0;
    foreach ($queries as $query) {
        if (!empty($query) && !preg_match('/^--/', $query)) {
            try {
                $db->execute($query);
                $success++;
            } catch (Exception $e) {
                echo "   ❌ Erreur: " . $e->getMessage() . "\n";
                echo "   Requête: " . substr($query, 0, 100) . "...\n";
            }
        }
    }
    
    echo "   ✅ $success requêtes exécutées\n\n";
}

// Réactiver les contraintes
$db->execute("SET FOREIGN_KEY_CHECKS = 1");

echo "================================\n";
echo "MIGRATION TERMINÉE\n";
echo "================================\n\n";

// Vérification
try {
    $tables = $db->fetchAll("SHOW TABLES");
    echo "📊 TABLES CRÉÉES:\n";
    
    foreach ($tables as $table) {
        $tableName = reset($table);
        $result = $db->fetch("SHOW CREATE TABLE `$tableName`");
        echo "  - $tableName\n";
        
        // Vérifier le moteur
        preg_match('/ENGINE=(\w+)/', $result['Create Table'], $matches);
        if (isset($matches[1])) {
            echo "    Moteur: " . $matches[1] . "\n";
        }
        
        // Vérifier les clés étrangères
        preg_match_all('/CONSTRAINT `([^`]+)` FOREIGN KEY/', $result['Create Table'], $fkMatches);
        if (!empty($fkMatches[1])) {
            echo "    Clés étrangères: " . count($fkMatches[1]) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "⚠️  Erreur lors de la vérification: " . $e->getMessage() . "\n";
}