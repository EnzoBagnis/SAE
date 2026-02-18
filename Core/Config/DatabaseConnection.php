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
        $port = EnvLoader::get('DB_PORT', '3306');
        $dbname = EnvLoader::get('DB_NAME');
        $user = EnvLoader::get('DB_USER');
        $pass = EnvLoader::get('DB_PASS');
        $charset = EnvLoader::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new \PDO($dsn, $user, $pass, $options);
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

