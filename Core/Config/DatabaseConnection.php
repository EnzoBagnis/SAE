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

        if (empty($dbname) || empty($user)) {
            throw new \RuntimeException("Configuration de base de données incomplète. Vérifiez DB_NAME et DB_USER dans .env");
        }

        $dsn = "mysql:host={$host};dbname={$dbname}";

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new \RuntimeException("Impossible de se connecter à la base de données. Vérifiez vos identifiants.");
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

