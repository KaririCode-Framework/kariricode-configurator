<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests\Loader;

use KaririCode\Configurator\Exception\ConfigurationException;
use KaririCode\Configurator\Loader\YamlLoader;
use PHPUnit\Framework\TestCase;

final class YamlLoaderTest extends TestCase
{
    private string $tempDir;
    private YamlLoader $yamlLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/yaml_loader_test_' . uniqid();
        if (!mkdir($this->tempDir) && !is_dir($this->tempDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created', $this->tempDir));
        }
        $this->yamlLoader = new YamlLoader();
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

    public function testLoadValidYamlFile(): void
    {
        if (!extension_loaded('yaml')) {
            $this->markTestSkipped('YAML extension is not loaded.');
        }

        $yamlContent = <<<YAML
        key1: value1
        key2: 123
        key3: true
        YAML;
        $filePath = $this->createFile('valid.yaml', $yamlContent);

        $result = $this->yamlLoader->load($filePath);

        $this->assertIsArray($result);
        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 123,
            'key3' => true,
        ], $result);
    }

    // public function testLoadInvalidYamlFileThrowsException(): void
    // {
    //     if (!extension_loaded('yaml')) {
    //         $this->markTestSkipped('YAML extension is not loaded.');
    //     }

    //     $invalidYamlContent = <<<YAML
    //     key1: value1
    //     key2: [unclosed
    //     YAML;
    //     $filePath = $this->createFile('invalid.yaml', $invalidYamlContent);

    //     $this->expectException(ConfigurationException::class);
    //     $this->expectExceptionMessage('Failed to parse YAML file');

    //     $this->yamlLoader->load($filePath);
    // }

    // public function testLoadYamlFileWithoutYamlExtensionThrowsException(): void
    // {
    //     if (extension_loaded('yaml')) {
    //         $this->markTestSkipped('YAML extension is loaded. Skipping the test.');
    //     }

    //     $yamlContent = <<<YAML
    //     key: value
    //     YAML;
    //     $filePath = $this->createFile('config.yaml', $yamlContent);

    //     $this->expectException(ConfigurationException::class);
    //     $this->expectExceptionMessage('YAML extension is not loaded');

    //     $this->yamlLoader->load($filePath);
    // }

    public function testLoadNonExistentFileThrowsException(): void
    {
        if (!extension_loaded('yaml')) {
            $this->markTestSkipped('YAML extension is not loaded.');
        }

        $filePath = $this->tempDir . '/non_existent.yaml';

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Configuration file not found: {$filePath}");

        $this->yamlLoader->load($filePath);
    }

    public function testGetTypes(): void
    {
        $types = $this->yamlLoader->getTypes();
        $this->assertIsArray($types);
        $this->assertEquals(['yml', 'yaml'], $types);
    }
}
