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

    public function get(string $key, mixed $default = null): mixed
    {
        $keyParts = explode('.', $key);
        $currentNode = $this->treeMap;

        foreach ($keyParts as $keyPart) {
            if (!$this->isValidTreeMapNode($currentNode, $keyPart)) {
                return $default;
            }
            $currentNode = $currentNode->get($keyPart);
        }

        return $currentNode;
    }

    public function set(string $key, mixed $value): void
    {
        $keyParts = explode('.', $key);
        $currentNode = $this->treeMap;

        foreach ($keyParts as $index => $keyPart) {
            $isLastKeyPart = $index === count($keyParts) - 1;

            if ($isLastKeyPart) {
                $currentNode->put($keyPart, $value);
            } else {
                $this->ensureNodeExists($currentNode, $keyPart);
                $currentNode = $currentNode->get($keyPart);
            }
        }
    }

    public function has(string $key): bool
    {
        $keyParts = explode('.', $key);
        $currentNode = $this->treeMap;

        foreach ($keyParts as $keyPart) {
            if (!$this->isValidTreeMapNode($currentNode, $keyPart)) {
                return false;
            }
            $currentNode = $currentNode->get($keyPart);
        }

        return true;
    }

    public function all(): array
    {
        return $this->flattenTreeMap($this->treeMap);
    }

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

    private function isValidTreeMapNode(mixed $node, string $key): bool
    {
        return $node instanceof TreeMap && $node->containsKey($key);
    }

    private function ensureNodeExists(TreeMap $node, string $key): void
    {
        if (!$node->containsKey($key)) {
            $node->put($key, new TreeMap());
        }
    }
}
