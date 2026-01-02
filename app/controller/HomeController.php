<?php
// app/Controller/HomeController.php

namespace App\Controller;

use App\Core\Controller;
use App\Models\Product;

class HomeController extends Controller
{
    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }

    public function index()
    {
        $featured = $this->productModel->getFeatured(8);
        $new = $this->productModel->getNew(8);
        $data = [
            'title' => 'Accueil',
            'featuredProducts' => $featured,
            'newProducts' => $new
        ];
        $this->view('home/index', $data);
    }

    public function about()
    {
        $this->view('home/about', ['title' => 'À propos']);
    }

    public function contact()
    {
        if ($this->request->isPost()) {
            $this->setFlash('success', 'Message envoyé avec succès.');
            $this->redirect('/contact');
        }
        $this->view('home/contact', ['title' => 'Contact']);
    }
}
