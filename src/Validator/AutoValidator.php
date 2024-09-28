<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Validator;

use KaririCode\Configurator\Contract\Validator\Validator;
use KaririCode\Configurator\Exception\ConfigurationException;

class AutoValidator implements Validator
{
    public function validate(mixed $value, string $key): void
    {
        $type = $this->detectType($value);
        $this->validateByType($value, $type, $key);
    }

    private function detectType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_string($value) => 'string',
            is_array($value) => 'array',
            is_null($value) => 'null',
            default => 'mixed',
        };
    }

    private function validateByType(mixed $value, string $type, string $key): void
    {
        $typeValidators = [
            'boolean' => fn ($v): bool => is_bool($v),
            'integer' => fn ($v): bool => is_int($v),
            'float' => fn ($v): bool => is_float($v),
            'string' => fn ($v): bool => is_string($v),
            'array' => fn ($v): bool => is_array($v),
            'null' => fn ($v): bool => is_null($v),
        ];

        if (isset($typeValidators[$type]) && !$typeValidators[$type]($value)) {
            throw new ConfigurationException("Configuration '$key' must be a $type.");
        }

        if ('array' === $type && is_array($value)) {
            foreach ($value as $subKey => $subValue) {
                $this->validate($subValue, "$key.$subKey");
            }
        }
    }
}
