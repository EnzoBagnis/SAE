<?php

namespace Core\Config;

/**
 * Database Connection Manager
 * Singleton pattern for PDO database connection
 */
class DatabaseConnection
{
    private static ?DatabaseConnection $instance = null;
    private \PDO $pdo;

    /**
     * Private constructor to prevent direct instantiation
     *
     * @throws \PDOException If connection fails
     */
    private function __construct()
    {
        $host = EnvLoader::get('DB_HOST', 'localhost');
        $dbname = EnvLoader::get('DB_NAME');
        $user = EnvLoader::get('DB_USER');
        $pass = EnvLoader::get('DB_PASS', '');

        // Log loaded configuration (without password)
        error_log("INFO: DB Config - Host: {$host}, Database: {$dbname}, User: {$user}");

        if (empty($dbname)) {
            error_log("CRITICAL: DB_NAME est vide ou non défini dans le fichier .env");
            throw new \RuntimeException("Configuration manquante: DB_NAME non défini dans .env");
        }

        if (empty($user)) {
            error_log("CRITICAL: DB_USER est vide ou non défini dans le fichier .env");
            throw new \RuntimeException("Configuration manquante: DB_USER non défini dans .env");
        }

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new \PDO($dsn, $user, $pass, $options);
            error_log("INFO: Connexion à la base de données réussie");
        } catch (\PDOException $e) {
            error_log("CRITICAL: Database Connection Error - " . $e->getMessage());
            error_log("CRITICAL: DSN utilisé: mysql:host={$host};dbname={$dbname}");
            throw new \RuntimeException(
                "Erreur de connexion à la base de données. Vérifiez les logs pour plus de détails."
            );
        }
    }

    /**
     * Get singleton instance
     *
     * @return DatabaseConnection Singleton instance
     */
    public static function getInstance(): DatabaseConnection
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Get PDO connection
     *
     * @return \PDO PDO connection instance
     */
    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

