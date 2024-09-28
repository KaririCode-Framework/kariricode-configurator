<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests\MergeStrategy;

use KaririCode\Configurator\Contract\Configurator\Storage;
use KaririCode\Configurator\MergeStrategy\OverwriteMerge;
use KaririCode\Configurator\Storage\TreeMapStorage;
use PHPUnit\Framework\TestCase;

final class OverwriteMergeTest extends TestCase
{
    private OverwriteMerge $overwriteMerge;
    private Storage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->overwriteMerge = new OverwriteMerge();
        $this->storage = new TreeMapStorage();
    }

    public function testMergeOverwritesExistingKeys(): void
    {
        $this->storage->set('key1', 'original');
        $this->storage->set('key2', 'original');

        $newConfig = [
            'key1' => 'new_value1',
            'key3' => 'new_value3',
        ];

        $this->overwriteMerge->merge($this->storage, $newConfig);

        $this->assertEquals('new_value1', $this->storage->get('key1'));
        $this->assertEquals('original', $this->storage->get('key2'));
        $this->assertEquals('new_value3', $this->storage->get('key3'));
    }

    public function testMergeWithEmptyConfigDoesNothing(): void
    {
        $this->storage->set('key1', 'value1');

        $newConfig = [];

        $this->overwriteMerge->merge($this->storage, $newConfig);

        $this->assertEquals('value1', $this->storage->get('key1'));
    }

    public function testMergeWithNestedKeys(): void
    {
        $this->storage->set('parent.child.key1', 'original1');
        $this->storage->set('parent.child.key2', 'original2');

        $newConfig = [
            'parent.child.key1' => 'new1',
            'parent.child.key3' => 'new3',
        ];

        $this->overwriteMerge->merge($this->storage, $newConfig);

        $this->assertEquals('new1', $this->storage->get('parent.child.key1'));
        $this->assertEquals('original2', $this->storage->get('parent.child.key2'));
        $this->assertEquals('new3', $this->storage->get('parent.child.key3'));
    }
}
