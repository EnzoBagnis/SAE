<?php

namespace Core\Config;

/**
 * Environment Configuration Loader
 * Loads and manages configuration from .env file located at ../config/.env
 */
class EnvLoader
{
    private static array $config = [];
    private static bool $loaded = false;

    /**
     * Load configuration from .env file
     *
     * @throws \RuntimeException If .env file is not found
     * @return void
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        // Try multiple possible paths for .env file
        // Structure locale (XAMPP): htdocs/SAE/Core/Config/
        // Structure Alwaysdata: www/SAE/Core/Config/ et .env dans www/config/
        $possiblePaths = [
            __DIR__ . '/../../../config/.env',  // Alwaysdata: www/config/.env (depuis www/SAE/Core/Config/)
            $_SERVER['DOCUMENT_ROOT'] . '/../config/.env', // Alwaysdata alternative path
        ];

        $envPath = null;
        foreach ($possiblePaths as $path) {
            $realPath = realpath($path);
            if ($realPath && file_exists($realPath)) {
                $envPath = $realPath;
                break;
            }
        }

        if ($envPath === null) {
            // Log the error with more details
            $debugInfo = [
                'tested_paths' => $possiblePaths,
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
                'current_dir' => __DIR__,
                'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
            ];
            error_log("CRITICAL: Fichier .env introuvable. Debug: " . json_encode($debugInfo));

            // Set default values for minimal functionality
            self::$config = [
                'DB_HOST' => getenv('DB_HOST') ?: 'localhost',
                'DB_PORT' => getenv('DB_PORT') ?: '3306',
                'DB_NAME' => getenv('DB_NAME') ?: '',
                'DB_USER' => getenv('DB_USER') ?: '',
                'DB_PASS' => getenv('DB_PASS') ?: '',
                'DB_CHARSET' => 'utf8mb4',
                'APP_ENV' => getenv('APP_ENV') ?: 'production',
                'APP_DEBUG' => getenv('APP_DEBUG') ?: 'false',
            ];
            self::$loaded = true;

            throw new \RuntimeException("Fichier .env introuvable. Veuillez créer un fichier .env à partir de .env.example. Chemins testés: " . implode(', ', $possiblePaths));
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            self::$config[$key] = $value;
        }

        self::$loaded = true;
    }

    /**
     * Get configuration value by key
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value or default
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config[$key] ?? $default;
    }

    /**
     * Get all configuration values
     *
     * @return array All configuration values
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }

    /**
     * Check if configuration key exists
     *
     * @param string $key Configuration key
     * @return bool True if key exists
     */
    public static function has(string $key): bool
    {
        if (!self::$loaded) {
            self::load();
        }

        return isset(self::$config[$key]);
    }

    /**
     * Set configuration value (runtime only)
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::$config[$key] = $value;
    }
}

