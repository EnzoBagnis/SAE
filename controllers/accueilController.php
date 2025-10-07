<?php
require_once __DIR__ . '/BaseController.php';

class accueilController extends BaseController {

    public function index() {
        $this->loadView('accueil', [
            'titre' => 'Accueil',
            'message' => 'Bienvenue sur StudTraj'
        ]);
    }
}