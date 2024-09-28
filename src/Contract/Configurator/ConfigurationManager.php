<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Contract\Configurator;

interface ConfigurationManager
{
    /**
     * Load configuration from a file.
     */
    public function load(string $path): void;

    /**
     * Load all configuration files from a directory.
     */
    public function loadDirectory(string $directory): void;

    /**
     * Get a configuration value.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a configuration value.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Check if a configuration key exists.
     */
    public function has(string $key): bool;

    /**
     * Get all configuration values.
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Register a loader for a specific file type.
     */
    public function registerLoader(Loader $loader): void;
}
