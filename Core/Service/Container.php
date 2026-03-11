<?php

namespace Core\Service;

/**
 * Simple Service Container
 *
 * A lightweight dependency injection container that maps class names
 * to factory callables. Used by the Router to resolve controller
 * dependencies instead of blindly calling `new $controllerClass()`.
 *
 * Controllers not registered in the container are still instantiated
 * directly (backward compatibility).
 */
class Container
{
    /**
     * Registered factory callables indexed by class name.
     *
     * @var array<string, callable>
     */
    private array $factories = [];

    /**
     * Cached singleton instances.
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Register a factory for a given class or interface name.
     *
     * @param string   $id      Fully qualified class/interface name
     * @param callable $factory Factory callable that returns the instance
     * @return void
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        // Clear cached instance when re-registering
        unset($this->instances[$id]);
    }

    /**
     * Resolve an instance by class or interface name.
     *
     * @param string $id Fully qualified class/interface name
     * @return object Resolved instance
     * @throws \RuntimeException If no factory is registered for the given ID
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new \RuntimeException("No factory registered for: {$id}");
        }

        $this->instances[$id] = ($this->factories[$id])($this);
        return $this->instances[$id];
    }

    /**
     * Check whether a factory is registered for the given ID.
     *
     * @param string $id Fully qualified class/interface name
     * @return bool True if registered
     */
    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }
}
