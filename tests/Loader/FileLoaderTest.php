<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests\Loader;

use KaririCode\Configurator\Exception\ConfigurationException;
use KaririCode\Configurator\Loader\FileLoader;
use PHPUnit\Framework\TestCase;

final class FileLoaderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/file_loader_test_' . uniqid();
        if (!mkdir($this->tempDir) && !is_dir($this->tempDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created', $this->tempDir));
        }
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

    /**
     * A concrete subclass of FileLoader for testing purposes.
     */
    private function createConcreteFileLoader(): FileLoader
    {
        return new class extends FileLoader {
            public function load(string $path): array
            {
                $this->validateFile($path);

                // For testing, simply return an empty array
                return [];
            }

            public function getTypes(): array
            {
                return ['test'];
            }
        };
    }

    public function testValidateFileThrowsExceptionForNonExistentFile(): void
    {
        $loader = $this->createConcreteFileLoader();
        $filePath = $this->tempDir . '/non_existent.test';

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Configuration file not found: {$filePath}");

        $loader->load($filePath);
    }

    public function testValidateFileThrowsExceptionForUnreadableFile(): void
    {
        $loader = $this->createConcreteFileLoader();
        $filePath = $this->createFile('unreadable.test', 'content');
        chmod($filePath, 0000); // Make the file unreadable

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Configuration file is not readable: {$filePath}");

        $loader->load($filePath);
    }

    public function testValidateFileSucceedsForReadableFile(): void
    {
        $loader = $this->createConcreteFileLoader();
        $filePath = $this->createFile('readable.test', 'content');

        // Should not throw any exception
        $result = $loader->load($filePath);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
