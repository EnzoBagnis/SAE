<?php
require_once __DIR__ . '/baseController.php';

class connexionController extends baseController {

    public function showView() {
        $this->loadView('connexion', ['titre' => 'connexion']);
    }
}