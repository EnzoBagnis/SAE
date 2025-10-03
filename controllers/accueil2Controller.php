<?php
class accueil2Controller {
    
    public function showView() {
        $titre = "Accueil2";
        
        $this->loadView('page2', [
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