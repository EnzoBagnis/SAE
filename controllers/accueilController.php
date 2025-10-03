<?php
class accueilController {
    
    public function index() {
        $titre = "Accueil";
        $message = "Bienvenue sur StudTraj";
        
        $this->loadView('accueil', [
            'titre' => $titre,
            'message' => $message
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