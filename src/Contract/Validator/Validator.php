<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Contract\Validator;

interface Validator
{
    public function validate(mixed $value, string $key): void;
}
