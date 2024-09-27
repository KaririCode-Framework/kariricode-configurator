<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Storage;

use KaririCode\Configurator\Contract\Configurator\Storage;

class ArrayStorage implements Storage
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getNestedValue($this->data, explode('.', $key), $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->setNestedValue($this->data, explode('.', $key), $value);
    }

    public function has(string $key): bool
    {
        return $this->getNestedValue($this->data, explode('.', $key), $this) !== $this;
    }

    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get a nested value from an array using an array of keys.
     *
     * @param array<string, mixed> $array
     * @param array<int, string> $keys
     */
    private function getNestedValue(array $array, array $keys, mixed $default): mixed
    {
        foreach ($keys as $key) {
            if (!is_array($array) || !array_key_exists($key, $array)) {
                return $default;
            }
            $array = $array[$key];
        }

        return $array;
    }

    /**
     * Set a nested value in an array using an array of keys.
     *
     * @param array<string, mixed> $array
     * @param array<int, string> $keys
     */
    private function setNestedValue(array &$array, array $keys, mixed $value): void
    {
        $lastKey = array_pop($keys);
        foreach ($keys as $key) {
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[$lastKey] = $value;
    }
}
