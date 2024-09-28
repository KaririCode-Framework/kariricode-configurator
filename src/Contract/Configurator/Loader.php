<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Contract\Configurator;

interface Loader
{
    /**
     * Load configuration from a file.
     *
     * @return array<string, mixed>
     */
    public function load(string $path): array;

    /**
     * Get the file type this loader handles.
     */
    public function getTypes(): array;
}
