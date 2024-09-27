<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Contract\Configurator;

interface MergeStrategy
{
    /**
     * Merge new configuration into existing storage.
     *
     * @param array<string, mixed> $newConfig
     */
    public function merge(Storage $storage, array $newConfig): void;
}
