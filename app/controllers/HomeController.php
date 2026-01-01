<?php
// app/controllers/HomeController.php

class HomeController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Bienvenue sur StandByMall',
            'products' => [] // À remplacer avec le modèle Product
        ];
        
        $this->view('home/index', $data);
    }
}