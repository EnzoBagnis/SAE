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

        // Path to .env file - Alwaysdata: www/config/.env (from www/SAE/Core/Config/)
        $envPath = __DIR__ . '/../../../config/.env';

        if (!file_exists($envPath)) {
            // Log the error with more details
            $debugInfo = [
                'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
                'current_dir' => __DIR__,
                'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
                'env_path' => $envPath,
            ];
            error_log("CRITICAL: Fichier .env introuvable. Debug: " . json_encode($debugInfo));

            throw new \RuntimeException(
                "Fichier .env introuvable. Veuillez créer un fichier .env à partir de .env.example."
            );
        }

        // Use parse_ini_file to parse the .env file
        $config = parse_ini_file($envPath, false, INI_SCANNER_RAW);

        if ($config === false) {
            throw new \RuntimeException("Erreur lors du parsing du fichier .env");
        }

        self::$config = $config;
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

