<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Loader;

use KaririCode\Configurator\Contract\Configurator\Loader;
use KaririCode\Configurator\Exception\ConfigurationException;

abstract class FileLoader implements Loader
{
    /**
     * Validate that the file exists and is readable.
     *
     * @throws ConfigurationException
     */
    protected function validateFile(string $path): void
    {
        if (!file_exists($path)) {
            throw new ConfigurationException("Configuration file not found: {$path}");
        }

        if (!is_readable($path)) {
            throw new ConfigurationException("Configuration file is not readable: {$path}");
        }
    }
}
