<?php

/**
 * Database class - Database connection manager
 * Handles PDO connection with configuration from .env file
 */
class Database
{
    /**
     * Get PDO database connection
     * @return PDO Database connection instance
     */
    public static function getConnection()
    {
        // Load environment variables from .env file
        $possiblePaths = [
            __DIR__ . '/../config/.env',       // Standard structure: models/../config/.env (SAE/config/.env)
            __DIR__ . '/../../config/.env',    // Old structure: models/../../config/.env (htdocs/config/.env)
            __DIR__ . '/../.env',              // Root structure: models/../.env (SAE/.env)
        ];

        $envPath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $envPath = $path;
                break;
            }
        }

        if (!$envPath) {
            // Fallback to default path if none found, to allow error to be thrown later or handled
            $envPath = __DIR__ . '/../config/.env';
            if (!file_exists($envPath)) {
                 // Try the other one as fallback for error message
                 $envPath = __DIR__ . '/../../config/.env';
            }
        }

        if (!file_exists($envPath)) {
            throw new Exception("Configuration file .env not found. Checked paths: " . implode(', ', $possiblePaths));
        }

        $env = parse_ini_file($envPath);

        if ($env === false) {
            throw new Exception("Error parsing configuration file");
        }

        $serverName = $env['DB_HOST'] ?? 'localhost';
        $username = $env['DB_USER'] ?? 'root';
        $password = $env['DB_PASS'] ?? '';
        $databaseName = $env['DB_NAME'] ?? 'studtraj';

        try {
            // Create PDO connection with UTF-8 charset
            $pdo = new PDO(
                "mysql:host=$serverName;dbname=$databaseName;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            // Log l'erreur et lancer une exception au lieu de die()
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }
}
