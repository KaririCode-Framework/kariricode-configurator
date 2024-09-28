<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests\Loader;

use KaririCode\Configurator\Exception\ConfigurationException;
use KaririCode\Configurator\Loader\PhpLoader;
use PHPUnit\Framework\TestCase;

final class PhpLoaderTest extends TestCase
{
    private string $tempDir;
    private PhpLoader $phpLoader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/php_loader_test_' . uniqid();
        if (!mkdir($this->tempDir) && !is_dir($this->tempDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created', $this->tempDir));
        }
        $this->phpLoader = new PhpLoader();
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

    public function testLoadValidPhpFile(): void
    {
        $phpContent = <<<'PHP'
        <?php
        return [
            'key1' => 'value1',
            'key2' => 123,
            'key3' => true,
        ];
        PHP;
        $filePath = $this->createFile('valid.php', $phpContent);

        $result = $this->phpLoader->load($filePath);

        $this->assertIsArray($result);
        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 123,
            'key3' => true,
        ], $result);
    }

    public function testLoadPhpFileThatDoesNotReturnArrayThrowsException(): void
    {
        $phpContent = <<<'PHP'
        <?php
        return "not_an_array";
        PHP;
        $filePath = $this->createFile('not_array.php', $phpContent);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('PHP configuration file must return an array');

        $this->phpLoader->load($filePath);
    }

    public function testLoadNonExistentFileThrowsException(): void
    {
        $filePath = $this->tempDir . '/non_existent.php';

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Configuration file not found: {$filePath}");

        $this->phpLoader->load($filePath);
    }

    // public function testLoadPhpFileWithSyntaxErrorThrowsException(): void
    // {
    //     $phpContent = <<<'PHP'
    //     <?php
    //     return [
    //         'key1' => 'value1',
    //         'key2' => 123,
    //     // Missing closing bracket
    //     PHP;
    //     $filePath = $this->createFile('syntax_error.php', $phpContent);

    //     $this->expectException(ConfigurationException::class);
    //     $this->expectExceptionMessage('Failed to parse PHP file');

    //     $this->phpLoader->load($filePath);
    // }

    public function testGetTypes(): void
    {
        $types = $this->phpLoader->getTypes();
        $this->assertIsArray($types);
        $this->assertEquals(['php'], $types);
    }
}
