<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Loader;

use KaririCode\Configurator\Exception\ConfigurationException;

/**
 * Loader for YAML configuration files.
 */
class YamlLoader extends FileLoader
{
    public function load(string $path): array
    {
        $this->validateFile($path);

        if (!extension_loaded('yaml')) {
            throw new ConfigurationException('YAML extension is not loaded');
        }

        $config = yaml_parse_file($path);

        if (false === $config) {
            throw new ConfigurationException("Failed to parse YAML file: {$path}");
        }

        return $config;
    }

    public function getTypes(): array
    {
        return ['yml', 'yaml'];
    }
}
