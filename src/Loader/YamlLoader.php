<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Loader;

use KaririCode\Configurator\Exception\ConfigException;

/**
 * Loader for YAML configuration files.
 */
class YamlLoader extends FileLoader
{
    public function load(string $path): array
    {
        $this->validateFile($path);

        if (!extension_loaded('yaml')) {
            throw new ConfigException('YAML extension is not loaded');
        }

        $config = yaml_parse_file($path);

        if (false === $config) {
            throw new ConfigException("Failed to parse YAML file: {$path}");
        }

        return $config;
    }

    public function getType(): string
    {
        return 'yaml';
    }
}
