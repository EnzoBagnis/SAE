<?php
class Router {
    
    public function route() {
        $action = $_GET['action'] ?? 'accueil';
        
        switch($action) {
            case 'index':
            case 'accueil':
                $this->loadController('accueilController', 'index');
                break;
                
            case 'inscription':
                $this->loadController('inscriptionController', 'showView');
                break;

            case 'connexion':
                $this->loadController('connexionController', 'showView');
                break;

            case 'accueil2':
                $this->loadController('accueil2Controller', 'showView');
                break;

            case 'mailverif':
                $this->loadController('mailVerifController', 'showView');
                break;
                
            default:
                $this->loadController('accueilController', 'index');
                break;
        }
    }
    
    private function loadController($controllerName, $method) {
        $controllerFile = $_SERVER['DOCUMENT_ROOT'] . 'controllers/' . $controllerName . '.php';
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            
            $controllerInstance = new $controllerName();
            
            if (method_exists($controllerInstance, $method)) {
                $controllerInstance->$method();
            } else {
                die("Méthode $method introuvable dans $controllerName");
            }
        } else {
            die("Controller $controllerName introuvable. Chemin testé: $controllerFile");
        }
    }
}