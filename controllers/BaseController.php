<?php
/**
 * BaseController - Controller de base avec méthodes communes
 */
abstract class BaseController {

    /**
     * Charger une vue avec des données
     */
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

