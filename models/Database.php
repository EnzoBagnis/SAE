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
        $env = parse_ini_file(__DIR__ . '/../../config/.env');

        $serverName = $env['DB_HOST'];
        $username = $env['DB_USER'];
        $password = $env['DB_PASS'];
        $databaseName = $env['DB_NAME'];

        try {
            // Create PDO connection with UTF-8 charset
            $pdo = new PDO(
                "mysql:host=$serverName;dbname=$databaseName;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
            return $pdo;
        } catch (PDOException $e) {
            // Log l'erreur et lancer une exception au lieu de die()
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Erreur de connexion à la base de données");
        }
    }
}
