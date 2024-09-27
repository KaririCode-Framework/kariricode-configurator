<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Loader;

use KaririCode\Configurator\Exception\ConfigException;

class PhpLoader extends FileLoader
{
    public function load(string $path): array
    {
        $this->validateFile($path);

        $config = require $path;

        if (!is_array($config)) {
            throw new ConfigException("PHP configuration file must return an array: {$path}");
        }

        return $config;
    }

    public function getType(): string
    {
        return 'php';
    }
}
