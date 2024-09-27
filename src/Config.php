<?php

declare(strict_types=1);

namespace KaririCode\Configurator;

use KaririCode\Configurator\Contract\Configurator\ConfigManager;
use KaririCode\Configurator\Contract\Configurator\Loader;
use KaririCode\Configurator\Contract\Configurator\MergeStrategy;
use KaririCode\Configurator\Contract\Configurator\Storage;
use KaririCode\Configurator\Exception\ConfigException;

final class Config implements ConfigManager
{
    /**
     * @var array<string, Loader>
     */
    private array $loaders = [];

    public function __construct(
        private readonly Storage $storage,
        private readonly MergeStrategy $mergeStrategy
    ) {
    }

    public function load(string $path): void
    {
        $loader = $this->getLoaderForFile($path);
        $config = $loader->load($path);
        $this->mergeStrategy->merge($this->storage, $config);
    }

    public function loadDirectory(string $directory): void
    {
        $files = $this->getConfigFilesFromDirectory($directory);
        foreach ($files as $file) {
            $this->load($file);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->storage->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->storage->set($key, $value);
    }

    public function has(string $key): bool
    {
        return $this->storage->has($key);
    }

    public function all(): array
    {
        return $this->storage->all();
    }

    public function registerLoader(Loader $loader): void
    {
        $this->loaders[$loader->getType()] = $loader;
    }

    /**
     * Get the appropriate loader for a given file.
     *
     * @throws ConfigException
     */
    private function getLoaderForFile(string $path): Loader
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (!isset($this->loaders[$extension])) {
            throw new ConfigException("No loader registered for file type: {$extension}");
        }

        return $this->loaders[$extension];
    }

    /**
     * Get all configuration files from a directory.
     *
     * @throws ConfigException
     *
     * @return array<string>
     */
    private function getConfigFilesFromDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new ConfigException("Directory not found: {$directory}");
        }

        return array_filter(
            glob($directory . '/*') ?: [],
            fn ($file) => is_file($file) && in_array(pathinfo($file, PATHINFO_EXTENSION), array_keys($this->loaders), true)
        );
    }
}
