<?php
// app/controllers/ProductController.php

class ProductController extends Controller
{
    private $productModel;
    
    public function __construct()
    {
        // À créer : app/models/Product.php
        // $this->productModel = new Product();
    }
    
    public function index()
    {
        $data = [
            'title' => 'Nos Produits',
            'products' => [] // $this->productModel->all()
        ];
        
        $this->view('products/index', $data);
    }
    
    public function show($id)
    {
        $data = [
            'title' => 'Détails Produit',
            'product' => [] // $this->productModel->find($id)
        ];
        
        $this->view('products/show', $data);
    }
}