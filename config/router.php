<?php

/**
 * Router class - Main application router
 * Handles URL routing and controller dispatching with namespaces
 */
class Router
{
    /**
     * Route the request to the appropriate controller
     */
    public function route()
    {
        $action = $_GET['action'] ?? 'home';

        switch ($action) {
            // ========== HOME ==========
            case 'index':
            case 'home':
                $this->loadController('HomeController', 'index');
                break;

            // ========== AUTH - LOGIN ==========
            case 'login':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\LoginController', 'authenticate');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\LoginController', 'index');
                }
                break;

            // ========== AUTH - REGISTER ==========
            case 'signup':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\RegisterController', 'register');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\RegisterController', 'index');
                }
                break;

            // ========== AUTH - EMAIL VERIFICATION ==========
            case 'emailverification':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\EmailVerificationController', 'verify');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\EmailVerificationController', 'index');
                }
                break;

            // ========== AUTH - RESEND CODE ==========
            case 'resendcode':
                $this->loadNamespacedController('Controllers\Auth\EmailVerificationController', 'resendCode');
                break;

            // ========== AUTH - FORGOT PASSWORD ==========
            case 'forgotpassword':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\PasswordResetController', 'requestReset');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\PasswordResetController', 'forgotPassword');
                }
                break;

            // ========== AUTH - RESET PASSWORD ==========
            case 'resetpassword':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->loadNamespacedController('Controllers\Auth\PasswordResetController', 'resetPassword');
                } else {
                    $this->loadNamespacedController('Controllers\Auth\PasswordResetController', 'showResetForm');
                }
                break;

            // ========== AUTH - LOGOUT ==========
            case 'logout':
                $this->loadNamespacedController('Controllers\Auth\LogoutController', 'logout');
                break;

            // ========== USER - DASHBOARD ==========
            case 'dashboard':
                $this->loadNamespacedController('Controllers\User\DashboardController', 'index');
                break;

            // ========== API - STUDENTS ==========
            case 'students':
                $this->loadNamespacedController('Controllers\User\StudentsController', 'getStudents');
                break;

            case 'student':
                $this->loadNamespacedController('Controllers\User\StudentsController', 'getStudent');
                break;

            // ========== API - EXERCISES ==========
            case 'exercises':
                $this->loadNamespacedController('Controllers\User\ExercisesController', 'getExercises');
                break;

            case 'exercise':
                $this->loadNamespacedController('Controllers\User\ExercisesController', 'getExercise');
                break;

            // ========== PAGES ==========
            case 'mentions':
                $this->loadView('pages/mentions-legales');
                break;


        // ========== RESOURCES LIST ==========
            case 'resources_list':
                require_once __DIR__ . '/../views/user/resources_list.php';
                break;

            case 'save_resource':
                if (!isset($_SESSION['id'])) {
                    header('Location: index.php?action=login');
                    exit;
                }

                require_once __DIR__ . '/../models/Database.php';
                $db = Database::getConnection();

                $id = $_POST['resource_id'] ?? '';
                $nom = $_POST['name'] ?? 'Sans nom';
                $desc = $_POST['description'] ?? '';
                $userId = $_SESSION['id'];
                $sharedUsers = $_POST['shared_users'] ?? []; // Liste des IDs des partenaires

                // 1. Upload Image
                $imagePath = null;
                // Si on est en modification, on peut vouloir récupérer l'ancienne image si pas de nouvelle
                // Mais ici on fait simple : si pas d'image envoyée, on laisse null (insert) ou on touche pas (update)

                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $uploadDir = __DIR__ . '/../images/'; // Dossier /images à la racine
                    // Créer le dossier s'il n'existe pas
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $fileName = time() . '_' . uniqid() . '.' . $extension;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
                        $imagePath = $fileName;
                    }
                }

                if (empty($id)) {
                    // --- CREATION ---
                    // Table: resources / Colonnes: resource_name, description, image_path, owner_user_id
                    $sql = "INSERT INTO resources (resource_name, description, image_path, owner_user_id) 
                            VALUES (:nom, :desc, :img, :uid)";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([
                        ':nom' => $nom,
                        ':desc' => $desc,
                        ':img' => $imagePath, // Peut être null
                        ':uid' => $userId
                    ]);
                    $resourceId = $db->lastInsertId();
                } else {
                    // --- MODIFICATION ---
                    // On vérifie que c'est bien MA ressource (owner_user_id)
                    $sqlInfo = "UPDATE resources SET resource_name = :nom, description = :desc";
                    if ($imagePath) {
                        $sqlInfo .= ", image_path = :img";
                    }
                    $sqlInfo .= " WHERE resource_id = :id AND owner_user_id = :uid";

                    $params = [':nom' => $nom, ':desc' => $desc, ':id' => $id, ':uid' => $userId];
                    if ($imagePath) {
                        $params[':img'] = $imagePath;
                    }

                    $stmt = $db->prepare($sqlInfo);
                    $stmt->execute($params);
                    $resourceId = $id;
                }

                // --- GESTION DES PARTENAIRES (Table 'resource_professors_access') ---

                // 1. On supprime les anciens partages pour cette ressource
                $delStmt = $db->prepare("DELETE FROM resource_professors_access WHERE resource_id = :rid");
                $delStmt->execute([':rid' => $resourceId]);

                // 2. On ajoute les nouveaux
                if (!empty($sharedUsers)) {
                    $insStmt = $db->prepare(
                        "INSERT INTO resource_professors_access (resource_id, user_id) VALUES (:rid, :uid)"
                    );
                    foreach ($sharedUsers as $partenaireId) {
                        // On évite de se partager à soi-même
                        if ($partenaireId != $userId) {
                            $insStmt->execute([':rid' => $resourceId, ':uid' => $partenaireId]);
                        }
                    }
                }

                header('Location: index.php?action=resources_list');
                exit;

            case 'delete_resource':
                // 1. Vérification sécurité
                if (!isset($_SESSION['id'])) {
                    header('Location: index.php?action=login');
                    exit;
                }

                require_once __DIR__ . '/../models/Database.php';
                $db = Database::getConnection();

                // On récupère l'ID via POST pour la sécurité (évite les suppressions accidentelles par lien)
                $resourceId = $_POST['resource_id'] ?? null;
                $userId = $_SESSION['id'];

                if (!$resourceId) {
                    header('Location: index.php?action=resources_list');
                    exit;
                }

                try {
                    // Démarrage de la transaction (tout ou rien)
                    $db->beginTransaction();

                    // A. Vérifier que l'utilisateur est bien le PROPRIÉTAIRE
                    $checkStmt = $db->prepare("SELECT resource_id, image_path FROM resources WHERE resource_id = :rid AND owner_user_id = :uid");
                    $checkStmt->execute([':rid' => $resourceId, ':uid' => $userId]);
                    $resource = $checkStmt->fetch(PDO::FETCH_OBJ);

                    if (!$resource) {
                        // Si pas trouvé ou pas propriétaire -> on annule tout
                        $db->rollBack();
                        die("Action non autorisée ou ressource introuvable.");
                    }

                    // B. Supprimer les TENTATIVES (attempts) liées aux exercices de cette ressource
                    // On doit d'abord trouver les IDs des exercices
                    $sqlExoIds = "SELECT exercise_id FROM exercises WHERE resource_id = :rid";
                    $stmtExo = $db->prepare($sqlExoIds);
                    $stmtExo->execute([':rid' => $resourceId]);
                    $exerciseIds = $stmtExo->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($exerciseIds)) {
                        // Création d'une chaîne pour le IN (ex: "1,2,5")
                        $placeholders = implode(',', array_fill(0, count($exerciseIds), '?'));

                        // Suppression des attempts
                        $sqlDelAttempts = "DELETE FROM attempts WHERE exercise_id IN ($placeholders)";
                        $stmtDelAttempts = $db->prepare($sqlDelAttempts);
                        $stmtDelAttempts->execute($exerciseIds);

                        // Suppression des test_cases (bonne pratique si la table existe, basé sur votre diagnostic)
                        $sqlDelTests = "DELETE FROM test_cases WHERE exercise_id IN ($placeholders)";
                        $stmtDelTests = $db->prepare($sqlDelTests);
                        $stmtDelTests->execute($exerciseIds);
                    }

                    // C. Supprimer les EXERCICES
                    $delExoStmt = $db->prepare("DELETE FROM exercises WHERE resource_id = :rid");
                    $delExoStmt->execute([':rid' => $resourceId]);

                    // D. Supprimer les PARTAGES (resource_professors_access)
                    $delShareStmt = $db->prepare("DELETE FROM resource_professors_access WHERE resource_id = :rid");
                    $delShareStmt->execute([':rid' => $resourceId]);

                    // E. Supprimer la RESSOURCE elle-même
                    $delResStmt = $db->prepare("DELETE FROM resources WHERE resource_id = :rid");
                    $delResStmt->execute([':rid' => $resourceId]);

                    // F. Supprimer l'image du serveur si elle existe
                    if (!empty($resource->image_path)) {
                        $imageFullPath = __DIR__ . '/../images/' . $resource->image_path;
                        if (file_exists($imageFullPath)) {
                            unlink($imageFullPath);
                        }
                    }

                    // Validation finale de la transaction
                    $db->commit();

                } catch (Exception $e) {
                    // En cas d'erreur, on annule tout ce qui a été fait
                    $db->rollBack();
                    error_log("Erreur suppression ressource: " . $e->getMessage());
                    die("Erreur lors de la suppression : " . $e->getMessage());
                }

                header('Location: index.php?action=resources_list');
                exit;
                break;


        // ========== RESOURCE DETAILS ==========
            case 'resource_details':
                require_once __DIR__ . '/../views/user/resource_details.php';
                break;

            // ========== ANALYSIS - VECTOR GENERATION ==========
            case 'generate-vectors':
                $this->loadNamespacedController('Controllers\Analysis\VectorController', 'generate');
                break;

            case 'processing':
                $this->loadNamespacedController('Controllers\Analysis\VectorController', 'processing');
                break;

            case 'status':
                $this->loadNamespacedController('Controllers\Analysis\VectorController', 'status');
                break;

            case 'vectors':
                $this->loadNamespacedController('Controllers\Analysis\VectorController', 'getVectors');
                break;

            // ========== ANALYSIS - VISUALIZATION ==========
            case 'visualization':
                $this->loadNamespacedController('Controllers\Analysis\VisualizationController', 'index');
                break;

            case 'visualization-data':
                $this->loadNamespacedController('Controllers\Analysis\VisualizationController', 'getData');
                break;

            default:
                $this->loadController('HomeController', 'index');
                break;
        }
    }

    /**
     * Load and execute controller method (legacy - old controllers)
     */
    private function loadController($controllerName, $method)
    {
        $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';

        if (file_exists($controllerFile)) {
            require_once $controllerFile;

            $controllerInstance = new $controllerName();

            if (method_exists($controllerInstance, $method)) {
                $controllerInstance->$method();
            } else {
                die("Method $method not found in $controllerName");
            }
        } else {
            die("Controller $controllerName not found. Path tested: $controllerFile");
        }
    }

    /**
     * Load and execute namespaced controller method (new MVC structure)
     */
    private function loadNamespacedController($fullyQualifiedClassName, $method)
    {
        // Load BaseController if not already loaded
        $baseControllerFile = __DIR__ . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'controllers'
            . DIRECTORY_SEPARATOR . 'BaseController.php';
        if (file_exists($baseControllerFile)) {
            require_once $baseControllerFile;
        }

        // Convert namespace to file path
        // Replace Controllers\ with controllers\ and all remaining backslashes with directory separator
        $classPath = str_replace('Controllers\\', 'controllers' . DIRECTORY_SEPARATOR, $fullyQualifiedClassName);
        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $classPath);
        $controllerFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $classPath . '.php';

        if (file_exists($controllerFile)) {
            require_once $controllerFile;

            $controllerInstance = new $fullyQualifiedClassName();

            if (method_exists($controllerInstance, $method)) {
                $controllerInstance->$method();
            } else {
                die("Method $method not found in $fullyQualifiedClassName");
            }
        } else {
            die("Controller $fullyQualifiedClassName not found. Path tested: $controllerFile");
        }
    }

    /**
     * Load a static view directly
     */
    private function loadView($viewPath)
    {
        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View $viewPath not found.");
        }
    }
}
