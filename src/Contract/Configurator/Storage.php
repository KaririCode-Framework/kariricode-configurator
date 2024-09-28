<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Contract\Configurator;

interface Storage
{
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
}
