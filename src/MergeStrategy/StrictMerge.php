<?php

declare(strict_types=1);

namespace KaririCode\Configurator\MergeStrategy;

use KaririCode\Configurator\Contract\Configurator\MergeStrategy;
use KaririCode\Configurator\Contract\Configurator\Storage;
use KaririCode\Configurator\Exception\ConfigException;

class StrictMerge implements MergeStrategy
{
    public function merge(Storage $storage, array $newConfig): void
    {
        foreach ($newConfig as $key => $value) {
            if ($storage->has($key)) {
                throw new ConfigException("Configuration key '{$key}' already exists and cannot be overwritten in strict mode.");
            }
            $storage->set($key, $value);
        }
    }
}
