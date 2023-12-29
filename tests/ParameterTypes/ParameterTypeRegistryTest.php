<?php

namespace Jbtronics\SettingsBundle\Tests\ParameterTypes;

use Jbtronics\SettingsBundle\ParameterTypes\BoolType;
use Jbtronics\SettingsBundle\ParameterTypes\IntType;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistry;
use Jbtronics\SettingsBundle\ParameterTypes\ParameterTypeRegistryInterface;
use Jbtronics\SettingsBundle\ParameterTypes\StringType;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParameterTypeRegistryTest extends KernelTestCase
{
    private ParameterTypeRegistryInterface $service;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = self::getContainer()->get(ParameterTypeRegistryInterface::class);
    }

    public function builtInTypesDataProvider(): array
    {
        return [
            [IntType::class],
            [BoolType::class],
            [StringType::class]
        ];
    }

    /**
     * @dataProvider builtInTypesDataProvider
     */
    public function testGetParameterType(string $class): void
    {
        $this->assertInstanceOf(ParameterTypeRegistryInterface::class, $this->service);
        $this->assertInstanceOf(ParameterTypeRegistry::class, $this->service);

        $type = $this->service->getParameterType($class);
        $this->assertInstanceOf($class, $type);
    }

    public function testGetRegisteredParameterTypes(): void
    {
        $types = $this->service->getRegisteredParameterTypes();
        $this->assertNotEmpty($types);
        $this->assertContains(IntType::class, $types);
    }
}
