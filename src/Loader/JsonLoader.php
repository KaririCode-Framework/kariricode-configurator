<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Loader;

use KaririCode\Configurator\Exception\ConfigurationException;

/**
 * Loader for JSON configuration files.
 */
class JsonLoader extends FileLoader
{
    public function load(string $path): array
    {
        $this->validateFile($path);

        $content = file_get_contents($path);

        if (false === $content) {
            throw new ConfigurationException("Failed to read JSON file: {$path}");
        }

        $config = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ConfigurationException('Failed to parse JSON file: ' . json_last_error_msg());
        }

        return $config;
    }

    public function getTypes(): array
    {
        return ['json'];
    }
}
