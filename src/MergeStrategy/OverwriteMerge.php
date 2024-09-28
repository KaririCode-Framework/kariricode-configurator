<?php

declare(strict_types=1);

namespace KaririCode\Configurator\MergeStrategy;

use KaririCode\Configurator\Contract\Configurator\MergeStrategy;
use KaririCode\Configurator\Contract\Configurator\Storage;

class OverwriteMerge implements MergeStrategy
{
    public function merge(Storage $storage, array $newConfig): void
    {
        foreach ($newConfig as $key => $value) {
            $storage->set($key, $value);
        }
    }
}
