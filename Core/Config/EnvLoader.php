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

        // Chemin depuis SAE/Core/Config/ vers htdocs/config/
        // SAE/Core/Config/ -> Core/ -> SAE/ -> htdocs/ -> config/
        $envPath = __DIR__ . '/../../../config/.env';

        if (!file_exists($envPath)) {
            throw new \RuntimeException("Fichier .env introuvable à : {$envPath}");
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

