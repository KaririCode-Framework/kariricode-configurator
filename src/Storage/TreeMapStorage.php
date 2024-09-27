<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Storage;

use KaririCode\Configurator\Contract\Configurator\Storage;
use KaririCode\DataStructure\Map\TreeMap;

class TreeMapStorage implements Storage
{
    public function __construct(
        private TreeMap $treeMap = new TreeMap()
    ) {
    }

    public function get(string $key, mixed $default = null): TreeMap
    {
        $keys = explode('.', $key);
        $current = $this->treeMap;

        foreach ($keys as $subKey) {
            if (!$current->containsKey($subKey)) {
                return $default;
            }
            $current = $current->get($subKey);
            if (!$current instanceof TreeMap) {
                return $current;
            }
        }

        return $current;
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = $this->treeMap;

        foreach ($keys as $i => $subKey) {
            if ($i === count($keys) - 1) {
                $current->put($subKey, $value);
            } else {
                if (!$current->containsKey($subKey) || !$current->get($subKey) instanceof TreeMap) {
                    $current->put($subKey, new TreeMap());
                }
                $current = $current->get($subKey);
            }
        }
    }

    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $current = $this->treeMap;

        foreach ($keys as $subKey) {
            if (!$current->containsKey($subKey)) {
                return false;
            }
            $current = $current->get($subKey);
            if (!$current instanceof TreeMap) {
                return true;
            }
        }

        return true;
    }

    public function all(): array
    {
        return $this->flattenTreeMap($this->treeMap);
    }

    /**
     * Flatten a TreeMap into an associative array.
     *
     * @return array<string, mixed>
     */
    private function flattenTreeMap(TreeMap $treeMap, string $prefix = ''): array
    {
        $result = [];
        foreach ($treeMap->getItems() as $key => $value) {
            $fullKey = $prefix ? "$prefix.$key" : $key;
            if ($value instanceof TreeMap) {
                $result = array_merge($result, $this->flattenTreeMap($value, $fullKey));
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }
}
