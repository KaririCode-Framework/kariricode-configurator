<?php

declare(strict_types=1);

namespace KaririCode\Configurator\Tests\Validator;

use KaririCode\Configurator\Exception\ConfigurationException;
use KaririCode\Configurator\Validator\AutoValidator;
use PHPUnit\Framework\TestCase;

final class AutoValidatorTest extends TestCase
{
    private AutoValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AutoValidator();
    }

    public function testValidateBoolean(): void
    {
        $this->validator->validate(true, 'bool_key');
        $this->validator->validate(false, 'bool_key');
        $this->addToAssertionCount(2); // If no exception is thrown
    }

    public function testValidateInteger(): void
    {
        $this->validator->validate(123, 'int_key');
        $this->addToAssertionCount(1);
    }

    public function testValidateFloat(): void
    {
        $this->validator->validate(3.14, 'float_key');
        $this->addToAssertionCount(1);
    }

    public function testValidateString(): void
    {
        $this->validator->validate('test', 'string_key');
        $this->addToAssertionCount(1);
    }

    public function testValidateArray(): void
    {
        $this->validator->validate(['a', 'b'], 'array_key');
        $this->addToAssertionCount(1);
    }

    public function testValidateNull(): void
    {
        $this->validator->validate(null, 'null_key');
        $this->addToAssertionCount(1);
    }

    public function testValidateUnsupportedTypeThrowsException(): void
    {
        $object = new \stdClass();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Unsupported configuration type 'mixed' for key 'object_key'.");

        $this->validator->validate($object, 'object_key');
    }
}
