<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests\MergeStrategy;

use KaririCode\Configurator\Contract\Configurator\Storage;
use KaririCode\Configurator\Exception\ConfigurationException;
use KaririCode\Configurator\MergeStrategy\StrictMerge;
use KaririCode\Configurator\Storage\TreeMapStorage;
use PHPUnit\Framework\TestCase;

final class StrictMergeTest extends TestCase
{
    private StrictMerge $strictMerge;
    private Storage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strictMerge = new StrictMerge();
        $this->storage = new TreeMapStorage();
    }

    public function testMergeThrowsExceptionOnDuplicateKeys(): void
    {
        $this->storage->set('key1', 'original');

        $newConfig = [
            'key1' => 'new_value1',
            'key2' => 'new_value2',
        ];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Configuration key 'key1' already exists and cannot be overwritten in strict mode.");

        $this->strictMerge->merge($this->storage, $newConfig);
    }

    public function testMergeAllowsUniqueKeys(): void
    {
        $this->storage->set('key1', 'original');

        $newConfig = [
            'key2' => 'new_value2',
            'key3' => 'new_value3',
        ];

        $this->strictMerge->merge($this->storage, $newConfig);

        $this->assertEquals('original', $this->storage->get('key1'));
        $this->assertEquals('new_value2', $this->storage->get('key2'));
        $this->assertEquals('new_value3', $this->storage->get('key3'));
    }

    public function testMergeWithEmptyConfigDoesNothing(): void
    {
        $this->storage->set('key1', 'value1');

        $newConfig = [];

        $this->strictMerge->merge($this->storage, $newConfig);

        $this->assertEquals('value1', $this->storage->get('key1'));
    }

    public function testMergeWithNestedKeys(): void
    {
        $this->storage->set('parent.child.key1', 'original1');

        $newConfig = [
            'parent.child.key2' => 'new2',
            'parent.child.key3' => 'new3',
        ];

        $this->strictMerge->merge($this->storage, $newConfig);

        $this->assertEquals('original1', $this->storage->get('parent.child.key1'));
        $this->assertEquals('new2', $this->storage->get('parent.child.key2'));
        $this->assertEquals('new3', $this->storage->get('parent.child.key3'));
    }
}
