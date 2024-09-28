<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests\Loader;

use KaririCode\Configurator\Exception\ConfigurationException;
use KaririCode\Configurator\Loader\JsonLoader;
use PHPUnit\Framework\TestCase;

final class JsonLoaderTest extends TestCase
{
    private string $tempDir;
    private JsonLoader $jsonLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/json_loader_test_' . uniqid();
        if (!mkdir($this->tempDir) && !is_dir($this->tempDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created', $this->tempDir));
        }
        $this->jsonLoader = new JsonLoader();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->deleteDirectory($this->tempDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createFile(string $filename, string $content): string
    {
        $filePath = $this->tempDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filePath, $content);

        return $filePath;
    }

    public function testLoadValidJsonFile(): void
    {
        $jsonContent = json_encode([
            'key1' => 'value1',
            'key2' => 123,
            'key3' => true,
        ]);
        $filePath = $this->createFile('valid.json', $jsonContent);

        $result = $this->jsonLoader->load($filePath);

        $this->assertIsArray($result);
        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 123,
            'key3' => true,
        ], $result);
    }

    public function testLoadInvalidJsonFileThrowsException(): void
    {
        $invalidJsonContent = '{"key1": "value1", "key2": 123,,,}';
        $filePath = $this->createFile('invalid.json', $invalidJsonContent);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Failed to parse JSON file');

        $this->jsonLoader->load($filePath);
    }

    public function testLoadNonExistentFileThrowsException(): void
    {
        $filePath = $this->tempDir . '/non_existent.json';

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Configuration file not found: {$filePath}");

        $this->jsonLoader->load($filePath);
    }

    public function testLoadPhpFileThatDoesNotReturnArrayThrowsException(): void
    {
        // Create a PHP file that returns a string instead of an array
        $phpContent = <<<'PHP'
        <?php
        return "not_an_array";
        PHP;
        $filePath = $this->createFile('not_array.json', $phpContent);

        // Rename to .json to mimic misnamed file
        rename($filePath, $this->tempDir . '/not_array.json');

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Failed to parse JSON file');

        $this->jsonLoader->load($this->tempDir . '/not_array.json');
    }

    public function testGetTypes(): void
    {
        $types = $this->jsonLoader->getTypes();
        $this->assertIsArray($types);
        $this->assertEquals(['json'], $types);
    }
}
