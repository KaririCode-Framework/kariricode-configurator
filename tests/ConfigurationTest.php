<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests;

use KaririCode\Configurator\Configuration;
use KaririCode\Configurator\Contract\Configurator\Loader;
use KaririCode\Configurator\Exception\ConfigurationException;
use KaririCode\Configurator\Loader\JsonLoader;
use KaririCode\Configurator\Loader\PhpLoader;
use KaririCode\Configurator\Loader\YamlLoader;
use KaririCode\Configurator\MergeStrategy\OverwriteMerge;
use KaririCode\Configurator\MergeStrategy\StrictMerge;
use KaririCode\Configurator\Storage\TreeMapStorage;
use KaririCode\Configurator\Validator\AutoValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    private string $tempDir;
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = $this->createTemporaryDirectory();
        $this->configuration = $this->createConfiguredConfiguration();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->deleteDirectory($this->tempDir);
    }

    private function createTemporaryDirectory(): string
    {
        $dir = sys_get_temp_dir() . '/config_test_' . uniqid();
        if (!mkdir($dir) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" could not be created', $dir));
        }

        return $dir;
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

    private function createConfiguredConfiguration(): Configuration
    {
        $config = new Configuration(
            storage: new TreeMapStorage(),
            validator: new AutoValidator(),
            mergeStrategy: new OverwriteMerge()
        );

        $config->registerLoader(new JsonLoader());
        $config->registerLoader(new PhpLoader());

        if (extension_loaded('yaml')) {
            $config->registerLoader(new YamlLoader());
        }

        return $config;
    }

    /**
     * @dataProvider configurationFilesProvider
     */
    public function testLoadConfigurationFiles(string $filename, string $content, array $expectedKeys, bool $isYaml = false): void
    {
        $filePath = $this->createFile($filename, $content);
        $prefix = pathinfo($filename, PATHINFO_FILENAME);

        if ($isYaml && extension_loaded('yaml')) {
            $this->configuration->registerLoader(new YamlLoader());
        }

        $this->configuration->load($filePath);

        foreach ($expectedKeys as $key => $value) {
            $fullKey = "$prefix.$key";
            $this->assertTrue(
                $this->configuration->has($fullKey),
                "The key '$fullKey' should exist in the configuration."
            );
            $this->assertEquals(
                $value,
                $this->configuration->get($fullKey),
                "The value for key '$fullKey' does not match the expected value."
            );
        }
    }

    public static function configurationFilesProvider(): array
    {
        $yamlContent = <<<YAML
api:
  endpoint: "https://api.example.com"
  key: "abcdef123456"
  retries: 3

features:
  registration: true
  payments: false
YAML;

        $phpContent = <<<'PHP'
<?php
return [
    'service.name' => 'MyService',
    'service.timeout' => 30,
    'service.enabled' => true,
    'cache.driver' => 'redis',
    'cache.ttl' => 600,
];
PHP;

        $jsonContent = json_encode([
            'database.host' => 'localhost',
            'database.port' => 3306,
            'database.username' => 'root',
            'database.password' => 'secret',
            'app.debug' => true,
            'app.log_level' => 'info',
        ]);

        return [
            'JSON Configuration' => [
                'filename' => 'config.json',
                'content' => $jsonContent,
                'expectedKeys' => [
                    'database.host' => 'localhost',
                    'database.port' => 3306,
                    'database.username' => 'root',
                    'database.password' => 'secret',
                    'app.debug' => true,
                    'app.log_level' => 'info',
                ],
            ],
            'PHP Configuration' => [
                'filename' => 'config.php',
                'content' => $phpContent,
                'expectedKeys' => [
                    'service.name' => 'MyService',
                    'service.timeout' => 30,
                    'service.enabled' => true,
                    'cache.driver' => 'redis',
                    'cache.ttl' => 600,
                ],
            ],
            'YAML Configuration' => [
                'filename' => 'config.yaml',
                'content' => $yamlContent,
                'expectedKeys' => [
                    'api.endpoint' => 'https://api.example.com',
                    'api.key' => 'abcdef123456',
                    'api.retries' => 3,
                    'features.registration' => true,
                    'features.payments' => false,
                ],
                'isYaml' => true,
            ],
        ];
    }

    public function testLoadDirectoryWithMultipleFiles(): void
    {
        $files = [
            'database.json' => json_encode([
                'host' => 'localhost',
                'port' => 3306,
            ]),
            'app.php' => <<<'PHP'
<?php
return [
    'debug' => true,
    'log_level' => 'debug',
];
PHP,
        ];

        if (extension_loaded('yaml')) {
            $files['cache.yaml'] = <<<YAML
cache.driver: "memcached"
cache.ttl: 300
YAML;
        }

        foreach ($files as $filename => $content) {
            $this->createFile($filename, $content);
        }

        $this->configuration->loadDirectory($this->tempDir);

        $expected = [
            'database.host' => 'localhost',
            'database.port' => 3306,
            'app.debug' => true,
            'app.log_level' => 'debug',
        ];

        if (extension_loaded('yaml')) {
            $expected['cache.cache.driver'] = 'memcached';
            $expected['cache.cache.ttl'] = 300;
        }

        foreach ($expected as $key => $value) {
            $this->assertTrue(
                $this->configuration->has($key),
                "The key '$key' should exist in the configuration."
            );
            $this->assertEquals(
                $value,
                $this->configuration->get($key),
                "The value for key '$key' does not match the expected value."
            );
        }
    }

    private function isLoaderRegistered(string $extension): bool
    {
        $reflection = new \ReflectionClass($this->configuration);
        $property = $reflection->getProperty('loaders');
        $property->setAccessible(true);
        $loaders = $property->getValue($this->configuration);

        return isset($loaders[$extension]);
    }

    public function testGetConfigFilesFromNonexistentDirectoryThrowsException(): void
    {
        $nonexistentDir = '/path/to/nonexistent/directory';

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Directory not found: {$nonexistentDir}");

        $reflection = new \ReflectionClass($this->configuration);
        $method = $reflection->getMethod('getConfigFilesFromDirectory');
        $method->setAccessible(true);

        $method->invoke($this->configuration, $nonexistentDir);
    }

    public function testGetNonExistentKeyReturnsDefault(): void
    {
        $this->assertNull($this->configuration->get('non.existent.key'));
        $this->assertEquals(
            'default',
            $this->configuration->get('non.existent.key', 'default'),
            'The default value should be returned for the non-existent key.'
        );
    }

    public function testSetAndGetConfigurationValue(): void
    {
        $this->configuration->set('app.version', '1.0.0');

        $this->assertTrue(
            $this->configuration->has('app.version'),
            "The key 'app.version' should exist in the configuration."
        );

        $this->assertEquals(
            '1.0.0',
            $this->configuration->get('app.version'),
            "The value for 'app.version' should be '1.0.0'."
        );
    }

    public function testHasConfigurationKey(): void
    {
        $this->configuration->set('service.enabled', true);

        $this->assertTrue(
            $this->configuration->has('service.enabled'),
            "The key 'service.enabled' should exist in the configuration."
        );

        $this->assertFalse(
            $this->configuration->has('service.disabled'),
            "The key 'service.disabled' should not exist in the configuration."
        );
    }

    public function testAllReturnsAllConfigurations(): void
    {
        $phpContent = <<<'PHP'
<?php
return [
    'name' => 'TestApp',
    'version' => '2.1.0',
    'debug' => true,
];
PHP;

        $filePath = $this->createFile('app.php', $phpContent);
        $this->configuration->load($filePath);

        $expected = [
            'app.name' => 'TestApp',
            'app.version' => '2.1.0',
            'app.debug' => true,
        ];

        $this->assertEquals(
            $expected,
            $this->configuration->all(),
            'All configurations should be returned correctly.'
        );
    }

    public function testRegisterMultipleLoadersForSameExtension(): void
    {
        $jsonContent = json_encode([
            'key' => 'value',
        ]);

        $jsonFile = $this->createFile('config.json', $jsonContent);

        /** @var Loader&MockObject $secondJsonLoader */
        $secondJsonLoader = $this->createMock(Loader::class);
        $secondJsonLoader->method('getTypes')->willReturn(['json']);
        $secondJsonLoader->method('load')->willReturn(['key' => 'new_value']);

        $this->configuration->registerLoader(new JsonLoader());
        $this->configuration->registerLoader($secondJsonLoader);

        $this->configuration->load($jsonFile);

        $prefix = pathinfo('config.json', PATHINFO_FILENAME);
        $this->assertEquals(
            'new_value',
            $this->configuration->get("$prefix.key"),
            "The value for 'key' should be 'new_value' as per the second registered loader."
        );
    }

    public function testLoadUnsupportedFileThrowsException(): void
    {
        $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<config>
    <key>value</key>
</config>
XML;

        $xmlFile = $this->createFile('config.xml', $xmlContent);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('No loader registered for file type: xml');

        $this->configuration->load($xmlFile);
    }

    public function testLoadNonExistentFileThrowsException(): void
    {
        $nonExistentFile = $this->tempDir . '/missing.json';

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Configuration file not found: {$nonExistentFile}");

        $this->configuration->load($nonExistentFile);
    }

    public function testStrictMergeStrategyThrowsExceptionOnDuplicateKey(): void
    {
        $jsonContent1 = json_encode([
            'app' => [
                'name' => 'FirstApp',
            ],
        ]);

        $jsonContent2 = json_encode([
            'app' => [
                'name' => 'SecondApp',
            ],
        ]);

        $file1 = $this->createFile('app.json', $jsonContent1);
        $file2 = $this->createFile('app.json', $jsonContent2);

        $configuration = new Configuration(
            storage: new TreeMapStorage(),
            validator: new AutoValidator(),
            mergeStrategy: new StrictMerge()
        );

        $configuration->registerLoader(new JsonLoader());
        $configuration->load($file1);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Configuration key 'app.app.name' already exists and cannot be overwritten in strict mode.");

        $configuration->load($file2);
    }

    public function testOverwriteMergeStrategyAllowsDuplicateKeys(): void
    {
        $jsonContent1 = json_encode([
            'name' => 'FirstApp',
            'version' => '1.0.0',
        ]);

        $jsonContent2 = json_encode([
            'name' => 'SecondApp',
            'debug' => true,
        ]);

        $file1 = $this->createFile('app1.json', $jsonContent1);
        $file2 = $this->createFile('app2.json', $jsonContent2);

        $configuration = new Configuration(
            storage: new TreeMapStorage(),
            validator: new AutoValidator(),
            mergeStrategy: new OverwriteMerge()
        );

        $configuration->registerLoader(new JsonLoader());
        $configuration->load($file1);
        $configuration->load($file2);

        $this->assertEquals('SecondApp', $configuration->get('app2.name'));
        $this->assertEquals('1.0.0', $configuration->get('app1.version'));
        $this->assertTrue($configuration->get('app2.debug'));
    }

    public function testRegisterLoaderWithMultipleTypes(): void
    {
        /** @var Loader&MockObject $multiTypeLoader */
        $multiTypeLoader = $this->createMock(Loader::class);
        $multiTypeLoader->method('getTypes')->willReturn(['ini', 'conf']);
        $multiTypeLoader->method('load')->willReturn([
            'settings.mode' => 'production',
        ]);

        $this->configuration->registerLoader($multiTypeLoader);

        $iniContent = <<<INI
[settings]
mode = production
INI;

        $iniFile = $this->createFile('config.ini', $iniContent);
        $this->configuration->load($iniFile);

        $prefix = pathinfo('config.ini', PATHINFO_FILENAME);
        $this->assertTrue(
            $this->configuration->has("$prefix.settings.mode"),
            "The key 'settings.mode' should exist in the configuration with the prefix '$prefix'."
        );

        $this->assertEquals(
            'production',
            $this->configuration->get("$prefix.settings.mode"),
            "The value for 'settings.mode' should be 'production'."
        );
    }

    public function testLoadEmptyConfigurationFile(): void
    {
        $emptyJsonContent = json_encode([]);
        $emptyJsonFile = $this->createFile('empty.json', $emptyJsonContent);

        $this->configuration->load($emptyJsonFile);

        $this->assertEmpty(
            $this->configuration->all(),
            'The configuration should be empty after loading an empty file.'
        );
    }

    public function testLoadConfigurationWithNestedKeys(): void
    {
        $jsonContent = json_encode([
            'level2' => [
                'level3' => [
                    'key' => 'deep_value',
                ],
            ],
        ]);

        $jsonFile = $this->createFile('level1.json', $jsonContent);
        $this->configuration->load($jsonFile);

        $fullKey = 'level1.level2.level3.key';

        $this->assertTrue(
            $this->configuration->has($fullKey),
            "The key '$fullKey' should exist in the configuration."
        );

        $this->assertEquals(
            'deep_value',
            $this->configuration->get($fullKey),
            "The value for '$fullKey' should be 'deep_value'."
        );
    }

    public function testStorageAllReturnsFlattenedKeys(): void
    {
        $phpContent = <<<'PHP'
<?php
return [
    'database.host' => '127.0.0.1',
    'database.port' => 5432,
    'cache.enabled' => false,
    'cache.providers.redis.host' => 'localhost',
    'cache.providers.redis.port' => 6379,
];
PHP;

        $phpFile = $this->createFile('complex.php', $phpContent);
        $this->configuration->load($phpFile);

        $expected = [
            'complex.database.host' => '127.0.0.1',
            'complex.database.port' => 5432,
            'complex.cache.enabled' => false,
            'complex.cache.providers.redis.host' => 'localhost',
            'complex.cache.providers.redis.port' => 6379,
        ];

        $this->assertEquals(
            $expected,
            $this->configuration->all(),
            "The all() method should return all flattened keys correctly with the prefix 'complex'."
        );
    }

    public function testConfigurationAllEmptyInitially(): void
    {
        $newConfig = new Configuration(
            storage: new TreeMapStorage(),
            validator: new AutoValidator(),
            mergeStrategy: new OverwriteMerge()
        );

        $this->assertEmpty(
            $newConfig->all(),
            'A newly instantiated configuration should be empty.'
        );
    }

    public function testConfigurationWithMultipleLoaders(): void
    {
        $jsonContent = json_encode([
            'database.host' => 'json_host',
        ]);

        $phpContent = <<<'PHP'
<?php
return [
    'database.port' => 3306,
];
PHP;

        $jsonFile = $this->createFile('db.json', $jsonContent);
        $phpFile = $this->createFile('db.php', $phpContent);

        $this->configuration->load($jsonFile);
        $this->configuration->load($phpFile);

        $expected = [
            'db.database.host' => 'json_host',
            'db.database.port' => 3306,
        ];

        $this->assertEquals(
            $expected,
            $this->configuration->all(),
            "Configurations from multiple loaders should be loaded correctly with the prefix 'db'."
        );
    }

    public function testValidatorHandlesVariousTypes(): void
    {
        $jsonContent = json_encode([
            'string_key' => 'value',
            'int_key' => 123,
            'float_key' => 3.14,
            'bool_key' => true,
            'null_key' => null,
            'array_key' => ['a', 'b', 'c'],
        ]);

        $jsonFile = $this->createFile('types.json', $jsonContent);
        $this->configuration->load($jsonFile);

        $prefix = 'types';

        $this->assertEquals(
            'value',
            $this->configuration->get("$prefix.string_key"),
            "The key 'string_key' should be 'value'."
        );

        $this->assertEquals(
            123,
            $this->configuration->get("$prefix.int_key"),
            "The key 'int_key' should be 123."
        );

        $this->assertEquals(
            3.14,
            $this->configuration->get("$prefix.float_key"),
            "The key 'float_key' should be 3.14."
        );

        $this->assertTrue(
            $this->configuration->get("$prefix.bool_key"),
            "The key 'bool_key' should be true."
        );

        $this->assertNull(
            $this->configuration->get("$prefix.null_key"),
            "The key 'null_key' should be null."
        );

        $arrayKey = $this->configuration->get("$prefix.array_key");
        $this->assertInstanceOf(
            \KaririCode\DataStructure\Map\TreeMap::class,
            $arrayKey,
            "The key 'array_key' should be an instance of TreeMap."
        );
        $this->assertEquals(
            ['a', 'b', 'c'],
            $arrayKey->getItems(),
            "The content of 'array_key' should be ['a', 'b', 'c']."
        );
    }

    public function testInvalidJsonThrowsException(): void
    {
        $invalidJsonContent = '{"invalid_json": true,,,}';

        $invalidJsonFile = $this->createFile('invalid.json', $invalidJsonContent);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Failed to parse JSON file');

        $this->configuration->load($invalidJsonFile);
    }

    public function testPhpLoaderReturnsNonArrayThrowsException(): void
    {
        $phpContent = <<<'PHP'
<?php
return 'not_an_array';
PHP;

        $phpFile = $this->createFile('invalid.php', $phpContent);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('PHP configuration file must return an array');

        $this->configuration->load($phpFile);
    }

    //     public function testYamlLoaderWithoutExtensionThrowsException(): void
    //     {
    //         if (extension_loaded('yaml')) {
    //             $this->markTestSkipped('The YAML extension is loaded. Skipping the test.');
    //         }

    //         $yamlContent = <<<YAML
    // key: value
    // YAML;

    //         $yamlFile = $this->createFile('config.yaml', $yamlContent);
    //         $this->configuration->registerLoader(new YamlLoader());

    //         $this->expectException(ConfigurationException::class);
    //         $this->expectExceptionMessage('YAML extension is not loaded');

    //         $this->configuration->load($yamlFile);
    //     }
}
