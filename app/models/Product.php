<?php
// app/models/Product.php

class Product extends Model
{
    /**
     * Nom de la table
     */
    protected $table = 'products';
    
    /**
     * Clé primaire
     */
    protected $primaryKey = 'id';
    
    /**
     * Champs autorisés pour l'assignation de masse
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'sale_price',
        'sku',
        'stock_quantity',
        'main_image',
        'is_active',
        'is_featured'
    ];
    
    /**
     * Champs protégés
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    /**
     * Timestamps automatiques
     */
    protected $timestamps = true;
    
    /**
     * Accesseur pour le prix final
     */
    public function getFinalPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }
    
    /**
     * Accesseur pour le pourcentage de réduction
     */
    public function getDiscountPercentAttribute()
    {
        if ($this->sale_price && $this->price > 0) {
            return round((($this->price - $this->sale_price) / $this->price) * 100);
        }
        return 0;
    }
    
    /**
     * Scope pour les produits actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
    
    /**
     * Scope pour les produits en vedette
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', 1);
    }
    
    /**
     * Relation avec la catégorie
     */
    public function category()
    {
        $categoryModel = new Category();
        return $categoryModel->find($this->category_id);
    }
    
    /**
     * Vérifie si le produit est en stock
     */
    public function inStock()
    {
        return $this->stock_quantity > 0;
    }
    
    /**
     * Formate le prix pour l'affichage
     */
    public function formattedPrice()
    {
        return number_format($this->final_price, 2, ',', ' ') . ' €';
    }
}