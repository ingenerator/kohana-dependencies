<?php

namespace test\unit\Ingenerator\KohanaDependencies\Dependency;

use PHPUnit\Framework\TestCase;

class DefinitionTest extends TestCase
{
    public function test_it_populates_from_array()
    {
        $def = \Dependency_Definition::factory()->from_array([
            'class'       => '\ArrayObject',
            'constructor' => 'from_factory',
            'arguments'   => ['arg1', '%svc%'],
            'shared'      => true,
            'methods'     => [
                ['setSomething', ['val1', '%svc%']],
                ['init'],
            ],
        ]);

        $this->assertSame('\ArrayObject', $def->getClass());
        $this->assertSame('from_factory', $def->getConstructor());
        $this->assertTrue($def->is_shared());

        $args = $def->getArguments();
        $this->assertSame('arg1', $args[0]);
        $this->assertInstanceOf(\Dependency_Reference_Container::class, $args[1]);

        $methods = $def->getMethods();
        $this->assertArrayHasKey('setSomething', $methods);
        $this->assertSame('val1', $methods['setSomething'][0]);
        $this->assertInstanceOf(\Dependency_Reference_Container::class, $methods['setSomething'][1]);
        $this->assertArrayHasKey('init', $methods);
        $this->assertSame([], $methods['init']);
    }

    public function test_it_handles_empty_from_array()
    {
        $def = \Dependency_Definition::factory()->from_array([]);
        $this->assertNull($def->getClass());
        $this->assertNull($def->getPath());
        $this->assertNull($def->getConstructor());
        $this->assertSame([], $def->getArguments());
        $this->assertFalse($def->is_shared());
        $this->assertSame([], $def->getMethods());
    }
}
