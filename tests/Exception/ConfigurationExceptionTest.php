<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests\Exception;

use KaririCode\Configurator\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

final class ConfigurationExceptionTest extends TestCase
{
    public function testConfigurationExceptionCanBeThrown(): void
    {
        $message = 'Test exception message';
        try {
            throw new ConfigurationException($message);
        } catch (ConfigurationException $e) {
            $this->assertInstanceOf(ConfigurationException::class, $e);
            $this->assertEquals($message, $e->getMessage());

            return;
        }

        $this->fail('ConfigurationException was not thrown.');
    }
}
