<?php
require_once __DIR__ . '/BaseController.php';

class inscriptionController extends BaseController {

    public function showView() {
        $this->loadView('formulaire', ['titre' => 'inscription']);
    }
}