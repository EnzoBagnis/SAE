<?php

namespace Core\Service;

/**
 * Session Service Interface
 * Defines contract for session management
 */
interface SessionServiceInterface
{
    /**
     * Start session
     *
     * @return void
     */
    public function start(): void;

    /**
     * Set session value
     *
     * @param string $key Session key
     * @param mixed $value Session value
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get session value
     *
     * @param string $key Session key
     * @param mixed $default Default value if key not found
     * @return mixed Session value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if session key exists
     *
     * @param string $key Session key
     * @return bool True if key exists
     */
    public function has(string $key): bool;

    /**
     * Remove session value
     *
     * @param string $key Session key
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Destroy session
     *
     * @return void
     */
    public function destroy(): void;

    /**
     * Regenerate session ID
     *
     * @return void
     */
    public function regenerate(): void;
}
