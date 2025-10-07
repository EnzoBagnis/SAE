<?php
require_once __DIR__ . '/baseController.php';

class mailVerifController extends baseController {

    public function showView() {
        $this->loadView('verificationMail', ['titre' => 'mailVerif']);
    }
}