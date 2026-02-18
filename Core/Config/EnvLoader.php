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
            // Affichage direct de l'erreur pour débogage immédiat
            die("CRITICAL: Fichier .env introuvable. Chemin testé : " . realpath($envPath) . " (Brut: " . $envPath . ")");
        }

        // Use parse_ini_file to parse the .env file
        $config = parse_ini_file($envPath, false, INI_SCANNER_RAW);

        if ($config === false) {
            error_log("CRITICAL: Erreur lors du parsing du fichier .env à: {$envPath}");
            throw new \RuntimeException("Erreur lors du parsing du fichier .env");
        }

        // Log successful loading for debugging
        error_log("INFO: Fichier .env chargé avec succès. Clés trouvées: " . implode(', ', array_keys($config)));

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
    public static function set(string $key, $value): void
    {
        self::$config[$key] = $value;
    }
}

