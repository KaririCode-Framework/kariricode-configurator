<?php

declare(strict_types=1);

namespace KaririCode\Configurator;

use KaririCode\Configurator\Contract\Configurator\ConfigurationManager;
use KaririCode\Configurator\Contract\Configurator\Loader;
use KaririCode\Configurator\Contract\Configurator\MergeStrategy;
use KaririCode\Configurator\Contract\Configurator\Storage;
use KaririCode\Configurator\Contract\Validator\Validator;
use KaririCode\Configurator\Exception\ConfigurationException;
use KaririCode\Configurator\MergeStrategy\OverwriteMerge;
use KaririCode\Configurator\Storage\TreeMapStorage;
use KaririCode\Configurator\Validator\AutoValidator;

final class Configuration implements ConfigurationManager
{
    /**
     * @var array<string, Loader>
     */
    private array $loaders = [];

    public function __construct(
        private readonly Storage $storage = new TreeMapStorage(),
        private readonly Validator $validator = new AutoValidator(),
        private readonly MergeStrategy $mergeStrategy = new OverwriteMerge(),
    ) {
    }

    public function load(string $path): void
    {
        $loader = $this->getLoaderForFile($path);
        $config = $loader->load($path);
        $prefix = pathinfo($path, PATHINFO_FILENAME);

        $this->validateConfig($config, $prefix);
        $this->loadRecursive($config, $prefix);
    }

    private function loadRecursive(array $config, string $prefix = ''): void
    {
        foreach ($config as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $this->loadRecursive($value, $fullKey);
            } else {
                $this->storage->set($fullKey, $value);
            }
        }
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
        $types = $loader->getTypes();
        foreach ($types as $type) {
            $this->loaders[$type] = $loader;
        }
    }

    private function getLoaderForFile(string $path): Loader
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (!isset($this->loaders[$extension])) {
            throw new ConfigurationException("No loader registered for file type: {$extension}");
        }

        return $this->loaders[$extension];
    }

    private function getConfigFilesFromDirectory(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new ConfigurationException("Directory not found: {$directory}");
        }

        return array_filter(
            glob($directory . '/*') ?: [],
            fn ($file) => is_file($file) && in_array(pathinfo($file, PATHINFO_EXTENSION), array_keys($this->loaders), true)
        );
    }

    private function validateConfig(array $config, string $prefix = ''): void
    {
        foreach ($config as $key => $value) {
            $fullKey = $prefix ? "$prefix.$key" : $key;
            $this->validator->validate($value, $fullKey);
        }
    }
}
