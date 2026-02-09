<?php

namespace Infrastructure\Persistence;

use PDO;
use PDOException;

/**
 * DatabaseConnection - Manages database connectivity
 *
 * This class provides a centralized database connection.
 */
class DatabaseConnection
{
    private static ?PDO $connection = null;

    /**
     * Get database connection
     *
     * @return PDO Database connection
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::createConnection();
        }

        return self::$connection;
    }

    /**
     * Create new database connection
     *
     * @return PDO Database connection
     */
    private static function createConnection(): PDO
    {
        $env = self::loadEnv();

        $host = $env['DB_HOST'] ?? 'localhost';
        $dbname = $env['DB_NAME'] ?? 'sae';
        $username = $env['DB_USER'] ?? 'root';
        $password = $env['DB_PASSWORD'] ?? '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return $pdo;
        } catch (PDOException $e) {
            $errorMessage = "Database connection failed: " . $e->getMessage();
            $errorDetails = "\nHost: {$host}\nDatabase: {$dbname}\nUsername: {$username}";
            error_log($errorMessage . $errorDetails);

            // Show detailed error only in development
            if (ini_get('display_errors')) {
                throw new \RuntimeException($errorMessage . $errorDetails);
            }
            throw new \RuntimeException("Could not connect to database");
        }
    }

    /**
     * Load environment variables
     *
     * @return array Environment variables
     */
    private static function loadEnv(): array
    {
        // Try config/.env outside the project root first (production)
        $envFile = __DIR__ . '/../../../../config/.env';
        if (file_exists($envFile)) {
            return parse_ini_file($envFile);
        }

        // Fallback to config/.env inside the project root (development)
        $envFile = __DIR__ . '/../../../config/.env';
        if (file_exists($envFile)) {
            return parse_ini_file($envFile);
        }

        return [];
    }
}
