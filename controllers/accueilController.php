<?php
require_once __DIR__ . '/baseController.php';

class accueilController extends baseController {

    public function index() {
        $this->loadView('accueil', [
            'titre' => 'Accueil',
            'message' => 'Bienvenue sur StudTraj'
        ]);
    }
}