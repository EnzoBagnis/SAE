<?php
require_once __DIR__ . '/BaseController.php';

class connexionController extends BaseController {

    public function showView() {
        $this->loadView('connexion', ['titre' => 'connexion']);
    }
}