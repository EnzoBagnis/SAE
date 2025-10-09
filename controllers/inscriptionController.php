<?php
require_once __DIR__ . '/baseController.php';

class inscriptionController extends baseController {

    public function showView() {
        $this->loadView('formulaire', ['titre' => 'inscription']);
    }
}