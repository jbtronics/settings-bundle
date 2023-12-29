<?php

namespace Jbtronics\SettingsBundle\Tests\Storage;

use Jbtronics\SettingsBundle\Storage\InMemoryStorageAdapter;
use PHPUnit\Framework\TestCase;

class InMemoryStorageAdapterTest extends TestCase
{
    private InMemoryStorageAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new InMemoryStorageAdapter();
    }

    public function testLoadAndSave(): void
    {
        $this->adapter->save("test", ["test" => "test"]);
        $this->adapter->save('test2', ['test2' => true]);

        $this->assertEquals(["test" => "test"], $this->adapter->load("test"));
        $this->assertEquals(['test2' => true], $this->adapter->load('test2'));
    }
}
