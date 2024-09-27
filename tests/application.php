<?php

require_once __DIR__ . '/../vendor/autoload.php';

use KaririCode\Configurator\Config;
use KaririCode\Configurator\Loader\JsonLoader;
use KaririCode\Configurator\Loader\PhpLoader;
use KaririCode\Configurator\Loader\YamlLoader;
use KaririCode\Configurator\MergeStrategy\OverwriteMerge;
use KaririCode\Configurator\Storage\TreeMapStorage;

$config = new Config(
    new TreeMapStorage(),
    new OverwriteMerge()
);

$config->registerLoader(new PhpLoader());
$config->registerLoader(new YamlLoader());
$config->registerLoader(new JsonLoader());

$configPath = __DIR__ . '/../config/';

$config->load($configPath . 'app.php');
$config->load($configPath . 'database.yaml');
$config->load($configPath . 'cache.json');

// Acessando configurações
$appName = $config->get('name');
$dbConfig = $config->get('database.production');
$cacheDriver = $config->get('cache.default');
