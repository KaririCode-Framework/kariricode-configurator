<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests\Storage;

use KaririCode\Configurator\Storage\TreeMapStorage;
use PHPUnit\Framework\TestCase;

final class TreeMapStorageTest extends TestCase
{
    private TreeMapStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new TreeMapStorage();
    }

    public function testSetAndGetSingleKey(): void
    {
        $this->storage->set('key1', 'value1');
        $this->assertTrue($this->storage->has('key1'));
        $this->assertEquals('value1', $this->storage->get('key1'));
    }

    public function testSetAndGetNestedKeys(): void
    {
        $this->storage->set('parent.child.key', 'nested_value');
        $this->assertTrue($this->storage->has('parent.child.key'));
        $this->assertEquals('nested_value', $this->storage->get('parent.child.key'));
    }

    public function testGetNonExistentKeyReturnsDefault(): void
    {
        $this->assertNull($this->storage->get('non.existent.key'));
        $this->assertEquals(
            'default',
            $this->storage->get('non.existent.key', 'default'),
            'The default value should be returned for the non-existent key.'
        );
    }

    public function testHasMethod(): void
    {
        $this->storage->set('key1', 'value1');
        $this->assertTrue($this->storage->has('key1'));
        $this->assertFalse($this->storage->has('key2'));
    }

    public function testAllReturnsFlattenedArray(): void
    {
        $this->storage->set('parent.child.key1', 'value1');
        $this->storage->set('parent.child.key2', 'value2');
        $this->storage->set('parent.key3', 'value3');

        $expected = [
            'parent.child.key1' => 'value1',
            'parent.child.key2' => 'value2',
            'parent.key3' => 'value3',
        ];

        $this->assertEquals($expected, $this->storage->all());
    }

    public function testOverwriteExistingKey(): void
    {
        $this->storage->set('key1', 'value1');
        $this->storage->set('key1', 'value2');

        $this->assertEquals('value2', $this->storage->get('key1'));
    }

    public function testSetMultipleNestedKeys(): void
    {
        $this->storage->set('level1.level2.level3.key', 'deep_value');

        $this->assertTrue($this->storage->has('level1.level2.level3.key'));
        $this->assertEquals('deep_value', $this->storage->get('level1.level2.level3.key'));
    }

    public function testAllReturnsEmptyArrayInitially(): void
    {
        $this->assertEmpty($this->storage->all());
    }

    public function testSetArrayAsValue(): void
    {
        $arrayValue = ['a', 'b', 'c'];
        $this->storage->set('array.key', $arrayValue);

        $this->assertTrue($this->storage->has('array.key'));
        $this->assertEquals($arrayValue, $this->storage->get('array.key'));
    }

    public function testSetMixedTypes(): void
    {
        $this->storage->set('string', 'value');
        $this->storage->set('integer', 123);
        $this->storage->set('float', 3.14);
        $this->storage->set('boolean', true);
        $this->storage->set('null', null);
        $this->storage->set('array', ['a', 'b']);

        $this->assertEquals('value', $this->storage->get('string'));
        $this->assertEquals(123, $this->storage->get('integer'));
        $this->assertEquals(3.14, $this->storage->get('float'));
        $this->assertTrue($this->storage->get('boolean'));
        $this->assertNull($this->storage->get('null'));
        $this->assertEquals(['a', 'b'], $this->storage->get('array'));
    }
}
