<?php
// database/reset.php
require_once __DIR__ . '/../config.php';

$db = Database::getInstance();

echo "🔧 Réinitialisation de la base de données...\n\n";

// Désactiver les contraintes de clés étrangères
$db->execute("SET FOREIGN_KEY_CHECKS = 0");

// Liste des tables dans l'ordre inverse (pour éviter les contraintes)
$tables = [
    'payments',
    'reviews', 
    'order_items',
    'orders',
    'products',
    'categories',
    'users'
];

foreach ($tables as $table) {
    try {
        $db->execute("DROP TABLE IF EXISTS `$table`");
        echo "✓ Table $table supprimée\n";
    } catch (Exception $e) {
        echo "⚠️  Erreur avec $table: " . $e->getMessage() . "\n";
    }
}

// Réactiver les contraintes
$db->execute("SET FOREIGN_KEY_CHECKS = 1");

echo "\n✅ Base de données réinitialisée.\n";
echo "Exécutez 'php database/migrate.php' pour recréer les tables.\n";