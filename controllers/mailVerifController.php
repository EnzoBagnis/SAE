<?php
require_once __DIR__ . '/BaseController.php';

class mailVerifController extends BaseController {

    public function showView() {
        $this->loadView('verificationMail', ['titre' => 'mailVerif']);
    }
}