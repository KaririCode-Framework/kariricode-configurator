<?php

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Configurator\Configuration;
use KaririCode\Configurator\Loader\JsonLoader;
use KaririCode\Configurator\Loader\PhpLoader;
use KaririCode\Configurator\Loader\YamlLoader;

$config = new Configuration();

$config->registerLoader(new PhpLoader());
$config->registerLoader(new YamlLoader());
$config->registerLoader(new JsonLoader());

$configPath = __DIR__ . '/../config/';
$config->load($configPath . 'app.php');
$config->load($configPath . 'database.yml');
$config->load($configPath . 'cache.json');

echo "Testing configuration access:\n";
echo "-----------------------------\n";

$testCases = [
    'name' => 'Application Name',
    'environment' => 'Environment',
    'debug' => 'Debug Mode',
    'database.default.driver' => 'Default Database Driver',
    'database.connections.mysql.host' => 'MySQL Host',
    'cache.default' => 'Default Cache Driver',
    'cache.stores.file.driver' => 'File Cache Driver',
    'cache.stores.redis.connection' => 'Redis Connection',
    'non.existent.key' => 'Non-existent Key',
];

foreach ($testCases as $key => $description) {
    $value = $config->get($key, 'Not found');
    echo "{$description}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}

echo "\nDumping full configuration structure:\n";
echo "-------------------------------------\n";
print_r($config->all());
