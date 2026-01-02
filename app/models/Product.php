<?php
// app/models/Product.php
namespace App\Models;
use App\Core\Model;
class Product extends Model
{
    protected $table = 'products';
    protected $fillable = ['name', 'slug', 'price', 'description', 'category_id', 'stock', 'is_active'];

    // --- CRUD ---
    public function getActive()
    {
        return $this->db->query("SELECT * FROM {$this->table} WHERE is_active = 1")->fetchAll();
    }

    // --- Recherche et filtres ---
    public function search($keyword)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE name LIKE ? AND is_active = 1");
        $stmt->execute(["%$keyword%"]);
        return $stmt->fetchAll();
    }

    public function getByCategory($categoryId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE category_id = ? AND is_active = 1");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }

    public function getFeatured($limit = 6)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_featured = 1 AND is_active = 1 LIMIT ?");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getNew($limit = 6)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOnSale($limit = 6)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE sale_price IS NOT NULL AND is_active = 1 LIMIT ?");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function filterByPrice($min, $max)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE price BETWEEN ? AND ? AND is_active = 1");
        $stmt->execute([$min, $max]);
        return $stmt->fetchAll();
    }

    // --- Stock ---
    public function updateStock($id, $quantity)
    {
        return $this->update($id, ['stock' => $quantity]);
    }

    public function decreaseStock($id, $quantity)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET stock = stock - ? WHERE id = ? AND stock >= ?");
        return $stmt->execute([$quantity, $id, $quantity]);
    }

    public function increaseStock($id, $quantity)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET stock = stock + ? WHERE id = ?");
        return $stmt->execute([$quantity, $id]);
    }

    public function checkStock($id, $quantity)
    {
        $stmt = $this->db->prepare("SELECT stock FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        return $product && $product['stock'] >= $quantity;
    }

    // --- Relations ---
    public function getCategory($productId)
    {
        $sql = "SELECT c.* FROM categories c JOIN products p ON p.category_id = c.id WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }

    public function getReviews($productId)
    {
        $stmt = $this->db->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function getAverageRating($productId)
    {
        $stmt = $this->db->prepare("SELECT AVG(rating) as avg FROM reviews WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetch()['avg'] ?? 0;
    }

    // --- Utilitaires ---
    public function incrementViews($id)
    {
        $this->db->query("UPDATE {$this->table} SET views = views + 1 WHERE id = ?", [$id]);
    }

    public function getRelated($productId, $limit = 4)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE category_id = (SELECT category_id FROM {$this->table} WHERE id = ?) 
            AND id != ? AND is_active = 1 
            ORDER BY RAND() LIMIT ?
        ");
        $stmt->bindValue(1, $productId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $productId, \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
