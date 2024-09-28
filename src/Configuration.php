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
    private array $loaders = [];

    public function __construct(
        private Storage $storage = new TreeMapStorage(),
        private Validator $validator = new AutoValidator(),
        private MergeStrategy $mergeStrategy = new OverwriteMerge(),
    ) {
    }

    public function load(string $filePath): void
    {
        $loader = $this->getLoaderForFile($filePath);
        $config = $loader->load($filePath);
        $configName = pathinfo($filePath, PATHINFO_FILENAME);

        $this->validateConfig($config, $configName);
        $this->loadRecursive($config, $configName);
    }

    private function loadRecursive(array $config, string $prefix = ''): void
    {
        foreach ($config as $key => $value) {
            $fullKey = $this->buildFullKey($prefix, $key);
            if (is_array($value)) {
                $this->loadRecursive($value, $fullKey);
            } else {
                $this->storage->set($fullKey, $value);
            }
        }
    }

    public function loadDirectory(string $directoryPath): void
    {
        $configFiles = $this->getConfigFilesFromDirectory($directoryPath);
        foreach ($configFiles as $filePath) {
            $this->load($filePath);
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

    private function getLoaderForFile(string $filePath): Loader
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $isLoaderAvailable = isset($this->loaders[$fileExtension]);

        if (!$isLoaderAvailable) {
            throw new ConfigurationException("No loader registered for file type: {$fileExtension}");
        }

        return $this->loaders[$fileExtension];
    }

    private function getConfigFilesFromDirectory(string $directoryPath): array
    {
        if (!is_dir($directoryPath)) {
            throw new ConfigurationException("Directory not found: {$directoryPath}");
        }

        $allFiles = glob($directoryPath . '/*') ?: [];

        return array_filter($allFiles, [$this, 'isValidConfigFile']);
    }

    private function isValidConfigFile(string $filePath): bool
    {
        $isFile = is_file($filePath);
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        $isSupportedExtension = in_array($fileExtension, array_keys($this->loaders), true);

        return $isFile && $isSupportedExtension;
    }

    private function validateConfig(array $config, string $prefix = ''): void
    {
        foreach ($config as $key => $value) {
            $fullKey = $this->buildFullKey($prefix, $key);

            if (is_array($value)) {
                $this->validateConfig($value, $fullKey);
            } else {
                $this->validator->validate($value, $fullKey);
            }
        }
    }

    private function buildFullKey(string $prefix, mixed $key): string
    {
        return $prefix ? "$prefix.$key" : $key;
    }
}
