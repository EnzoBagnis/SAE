<?php
class mailVerifController {
    
    public function showView() {
        $titre = "mailVerif";
        
        $this->loadView('verificationMail', [
            'titre' => $titre
        ]);
    }
    
    protected function loadView($viewName, $data = []) {
        extract($data);
        
        $viewFile = __DIR__ . '/../views/' . $viewName . '.php';
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("Vue $viewName introuvable. Chemin testé: $viewFile");
        }
    }
}