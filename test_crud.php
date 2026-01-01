<?php
// test_crud.php - Test complet du CRUD

// Démarrer le buffer de sortie pour éviter les problèmes de headers
ob_start();

echo "================================\n";
echo "🧪 TEST CRUD - MODÈLE PRODUCT\n";
echo "================================\n\n";

// Chemin vers config.php
require_once __DIR__ . '/config.php';

// Désactiver temporairement la session dans config.php pour les tests CLI
if (php_sapi_name() === 'cli') {
    ini_set('session.use_cookies', 0);
    ini_set('session.use_only_cookies', 0);
}

echo "================================\n";
echo "🧪 TEST CRUD - MODÈLE PRODUCT\n";
echo "================================\n\n";

// Charger la configuration
require_once './config.php';

// Fonction d'affichage
function showStep($message) {
    echo "➡️  $message\n";
}

function showSuccess($message) {
    echo "✅ $message\n";
}

function showError($message) {
    echo "❌ $message\n";
}

function showData($data) {
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}

try {
    // === 1. INSTANCIATION ===
    showStep("1. Instanciation du modèle Product");
    $product = new Product();
    showSuccess("Modèle Product chargé avec succès");
    
    // === 2. CRÉATION (CREATE) ===
    showStep("\n2. Test de création (CREATE)");
    
    $productData = [
        'category_id' => 1,
        'name' => 'iPhone 15 Pro Max - TEST',
        'slug' => 'iphone-15-pro-max-test',
        'description' => 'Smartphone Apple de test pour le CRUD',
        'price' => 1299.99,
        'sale_price' => 1199.99,
        'sku' => 'TEST-IPHONE-001',
        'stock_quantity' => 50,
        'main_image' => 'test-iphone.jpg',
        'is_active' => 1,
        'is_featured' => 1
    ];
    
    $newProduct = $product->create($productData);
    
    if ($newProduct) {
        $productId = $newProduct->id;
        showSuccess("Produit créé avec succès !");
        echo "   ID du produit : $productId\n";
        echo "   Slug : " . $newProduct->slug . "\n";
        echo "   Prix final : " . $newProduct->final_price . " €\n";
        echo "   Réduction : " . $newProduct->discount_percent . "%\n";
    } else {
        showError("Échec de la création du produit");
        exit;
    }
    
    // === 3. LECTURE (READ) ===
    showStep("\n3. Test de lecture (READ)");
    
    // a) Récupérer tous les produits
    showStep("   a) Récupération de tous les produits");
    $allProducts = $product->all();
    echo "   Nombre total de produits : " . count($allProducts) . "\n";
    
    // b) Récupérer un produit par ID
    showStep("   b) Récupération du produit par ID");
    $foundProduct = $product->find($productId);
    
    if ($foundProduct) {
        showSuccess("Produit trouvé !");
        echo "   Nom : " . $foundProduct->name . "\n";
        echo "   Prix : " . $foundProduct->price . " €\n";
        echo "   Prix promo : " . ($foundProduct->sale_price ?? 'Aucune') . " €\n";
        echo "   Stock : " . $foundProduct->stock_quantity . " unités\n";
        echo "   Actif : " . ($foundProduct->is_active ? 'Oui' : 'Non') . "\n";
    } else {
        showError("Produit non trouvé");
    }
    
    // c) Recherche par colonne
    showStep("   c) Recherche par SKU");
    $productBySku = $product->findBy('sku', 'TEST-IPHONE-001');
    if ($productBySku) {
        showSuccess("Produit trouvé par SKU");
        echo "   SKU trouvé : " . $productBySku->sku . "\n";
    }
    
    // d) Requête avec conditions
    showStep("   d) Produits actifs et en vedette");
    $featuredProducts = $product->where('is_active', 1)
                                ->where('is_featured', 1)
                                ->orderBy('price', 'ASC')
                                ->get();
    echo "   Produits en vedette : " . count($featuredProducts) . "\n";
    
    // e) Compter
    showStep("   e) Nombre de produits en stock");
    $inStockCount = $product->where('stock_quantity', '>', 0)->count();
    echo "   Produits en stock : $inStockCount\n";
    
    // f) Pagination
    showStep("   f) Test de pagination");
    $page = 1;
    $perPage = 2;
    $paginated = $product->paginate($page, $perPage);
    
    echo "   Page $page sur " . $paginated['last_page'] . "\n";
    echo "   Résultats : " . count($paginated['data']) . " sur " . $paginated['total'] . "\n";
    echo "   De " . $paginated['from'] . " à " . $paginated['to'] . "\n";
    
    // === 4. MISE À JOUR (UPDATE) ===
    showStep("\n4. Test de mise à jour (UPDATE)");
    
    $updateData = [
        'name' => 'iPhone 15 Pro Max - TEST MIS À JOUR',
        'price' => 1250.00,
        'stock_quantity' => 45,
        'description' => 'Description mise à jour après test CRUD'
    ];
    
    $updated = $product->update($productId, $updateData);
    
    if ($updated) {
        showSuccess("Produit mis à jour avec succès !");
        
        // Vérifier les modifications
        $updatedProduct = $product->find($productId);
        echo "   Nouveau nom : " . $updatedProduct->name . "\n";
        echo "   Nouveau prix : " . $updatedProduct->price . " €\n";
        echo "   Nouveau stock : " . $updatedProduct->stock_quantity . "\n";
        
        // Test de l'accesseur
        echo "   Prix formaté : " . $updatedProduct->formattedPrice() . "\n";
    } else {
        showError("Échec de la mise à jour");
    }
    
    // === 5. SUPPRESSION (DELETE) ===
    showStep("\n5. Test de suppression (DELETE)");
    
    // a) Soft delete (si colonne deleted_at existe)
    showStep("   a) Suppression logique");
    $deleted = $product->delete($productId);
    
    if ($deleted) {
        showSuccess("Produit marqué comme supprimé (soft delete)");
        
        // Vérifier qu'il n'apparaît plus dans les résultats normaux
        $allAfterDelete = $product->all();
        echo "   Produits après suppression : " . count($allAfterDelete) . "\n";
        
        // Mais on peut toujours le trouver par ID
        $deletedProduct = $product->find($productId);
        if (!$deletedProduct) {
            echo "   Produit non trouvé après suppression (soft delete fonctionne)\n";
        }
    } else {
        showError("Échec de la suppression");
    }
    
    // b) Force delete (suppression définitive)
    showStep("   b) Suppression définitive");
    $forceDeleted = $product->forceDelete($productId);
    
    if ($forceDeleted) {
        showSuccess("Produit supprimé définitivement de la base");
        
        // Vérifier qu'il n'existe plus
        $finalCheck = $product->find($productId);
        if (!$finalCheck) {
            showSuccess("Produit complètement supprimé");
        }
    }
    
    // === 6. TEST DES SCOPES ET MÉTHODES CUSTOM ===
    showStep("\n6. Test des scopes et méthodes personnalisées");
    
    // Test du scope active
    $activeProducts = $product->active()->get();
    echo "   Produits actifs : " . count($activeProducts) . "\n";
    
    // Test du scope featured
    $featuredProducts = $product->featured()->get();
    echo "   Produits en vedette : " . count($featuredProducts) . "\n";
    
    // Test de la méthode inStock
    if (isset($updatedProduct)) {
        echo "   Produit en stock ? : " . ($updatedProduct->inStock() ? 'Oui' : 'Non') . "\n";
    }
    
    // === 7. TEST DES MÉTHODES MAGIQUES ===
    showStep("\n7. Test des méthodes magiques et accesseurs");
    
    // Créer un nouveau produit pour les tests
    $testProduct = $product->create([
        'category_id' => 2,
        'name' => 'Produit Test Accesseurs',
        'slug' => 'produit-test-accesseurs',
        'price' => 99.99,
        'sale_price' => 79.99,
        'sku' => 'TEST-ACC-001',
        'stock_quantity' => 10
    ]);
    
    if ($testProduct) {
        // Accès aux attributs via __get
        echo "   Nom via __get : " . $testProduct->name . "\n";
        
        // Test des accesseurs
        echo "   Prix final (accesseur) : " . $testProduct->final_price . " €\n";
        echo "   Pourcentage réduction : " . $testProduct->discount_percent . "%\n";
        
        // Modification via __set
        $testProduct->name = 'Nom modifié via __set';
        echo "   Nom après modification : " . $testProduct->name . "\n";
        
        // Sauvegarde
        $testProduct->save();
        showSuccess("Produit de test sauvegardé");
        
        // Nettoyage
        $product->forceDelete($testProduct->id);
        showSuccess("Produit de test nettoyé");
    }
    
    // === 8. TEST DES TRANSACTIONS ===
    showStep("\n8. Test des transactions");
    
    $db = Database::getInstance();
    
    try {
        $db->beginTransaction();
        
        $product1 = $product->create([
            'name' => 'Produit Transaction 1',
            'slug' => 'produit-transaction-1',
            'price' => 50.00,
            'sku' => 'TRANS-001'
        ]);
        
        $product2 = $product->create([
            'name' => 'Produit Transaction 2',
            'slug' => 'produit-transaction-2',
            'price' => 75.00,
            'sku' => 'TRANS-002'
        ]);
        
        $db->commit();
        showSuccess("Transaction réussie - 2 produits créés");
        
        // Nettoyer
        $product->forceDelete($product1->id);
        $product->forceDelete($product2->id);
        
    } catch (Exception $e) {
        $db->rollback();
        showError("Transaction annulée : " . $e->getMessage());
    }
    
    // === 9. TEST DES ERREURS ===
    showStep("\n9. Test des cas d'erreur");
    
    // a) Produit inexistant
    showStep("   a) Recherche d'un produit inexistant");
    $nonExistent = $product->find(999999);
    if (!$nonExistent) {
        showSuccess("Produit inexistant correctement géré");
    }
    
    // b) Création avec données invalides
    showStep("   b) Création avec données manquantes");
    try {
        $invalidProduct = $product->create([
            // 'name' manquant intentionnellement
            'price' => 100
        ]);
        
        if (!$invalidProduct) {
            showSuccess("Création invalide correctement rejetée");
        }
    } catch (Exception $e) {
        showSuccess("Exception attrapée : " . $e->getMessage());
    }
    
    // === 10. RÉCAPITULATIF ===
    showStep("\n10. Récapitulatif final");
    
    $totalProducts = $product->count();
    $activeProducts = $product->where('is_active', 1)->count();
    $outOfStock = $product->where('stock_quantity', '<=', 0)->count();
    
    echo "   📊 STATISTIQUES FINALES :\n";
    echo "   • Produits totaux : $totalProducts\n";
    echo "   • Produits actifs : $activeProducts\n";
    echo "   • Produits en rupture : $outOfStock\n";
    
    echo "\n" . str_repeat("=", 40) . "\n";
    showSuccess("✅ TEST CRUD COMPLÉTÉ AVEC SUCCÈS !");
    echo str_repeat("=", 40) . "\n";
    
} catch (Exception $e) {
    echo "\n" . str_repeat("=", 40) . "\n";
    showError("❌ ERREUR CRITIQUE DURANT LE TEST");
    echo "Message : " . $e->getMessage() . "\n";
    echo "Fichier : " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo str_repeat("=", 40) . "\n";
}