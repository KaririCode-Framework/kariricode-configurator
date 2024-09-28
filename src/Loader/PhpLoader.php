<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Loader;

use KaririCode\Configurator\Exception\ConfigurationException;

class PhpLoader extends FileLoader
{
    public function load(string $path): array
    {
        $this->validateFile($path);

        $config = require $path;

        if (!is_array($config)) {
            throw new ConfigurationException("PHP configuration file must return an array: {$path}");
        }

        return $config;
    }

    public function getTypes(): array
    {
        return ['php'];
    }
}
