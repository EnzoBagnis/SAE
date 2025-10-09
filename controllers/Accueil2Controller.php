<?php
require_once __DIR__ . '/BaseController.php';

class accueil2Controller extends baseController {

    public function showView() {
        $this->loadView('page2', ['titre' => 'Accueil2']);
    }
}