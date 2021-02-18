<?php


namespace test\unit\Ingenerator\KohanaDependencies;


use PHPUnit\Framework\TestCase;

class Dependency_ReferenceTest extends TestCase
{
    public function test_its_factory_returns_raw_arg_if_unexpected_format()
    {
        $this->assertSame('anything-atall', \Dependency_Reference::factory('anything-atall'));
    }

    public function test_it_factories_service_reference_which_resolves_to_expected_service()
    {
        $ref = \Dependency_Reference::factory('%some.service%');
        $this->assertInstanceOf(\Dependency_Reference_Container::class, $ref);
        $container = new class extends \Dependency_Container {
            public function __construct() { }

            public function get($key)
            {
                $svc                = new \stdClass();
                $svc->requested_key = $key;

                return $svc;
            }
        };
        $this->assertSame('some.service', $ref->resolve($container)->requested_key);
    }

    public function test_it_factories_config_ref_to_config_group()
    {
        $cfg = \Kohana::$config->load('ref-to-group');
        $cfg->set('foo', 'bar');
        $ref = \Dependency_Reference::factory('@ref-to-group@');
        $this->assertSame($cfg, $ref->resolve($this->getDummyExpectingNoCalls(\Dependency_Container::class)));
    }

    /**
     * @testWith [[], null]
     *           [{"foo": null}, null]
     *           [{"foo": "whatever"}, "whatever"]
     *           [{"foo": {"bar": "stuff"}}, {"bar": "stuff"}]
     *
     */
    public function test_it_factories_config_ref_to_config_value_whether_defined_or_null_or_undefined($vars, $expect)
    {
        $cfg = \Kohana::$config->load('ref-to-value');
        $cfg->exchangeArray($vars);
        $ref = \Dependency_Reference::factory('@ref-to-value.foo@');
        $this->assertSame($expect, $ref->resolve($this->getDummyExpectingNoCalls(\Dependency_Container::class)));
    }


    /**
     * @testWith [[], null]
     *           [{"foo": null}, null]
     */
    public function test_it_factories_required_config_ref_which_throws_if_value_is_undefined_or_null($vars)
    {
        $cfg = \Kohana::$config->load('ref-to-value');
        $cfg->exchangeArray($vars);
        $ref = \Dependency_Reference::factory('@!ref-to-value.foo!@');
        $this->expectException(\Dependency_Exception::class);
        $this->expectExceptionMessage('config variable ref-to-value.foo');
        $ref->resolve($this->getDummyExpectingNoCalls(\Dependency_Container::class));
    }

    /**
     * @testWith [{"foo": "whatever"}, "whatever"]
     *           [{"foo": {"bar": "stuff"}}, {"bar": "stuff"}]
     *           [{"foo": false}, false]
     *
     */
    public function test_it_factories_required_config_ref_which_returns_value_if_defined($vars, $expect)
    {
        $cfg = \Kohana::$config->load('ref-to-value');
        $cfg->exchangeArray($vars);
        $ref = \Dependency_Reference::factory('@!ref-to-value.foo!@');
        $this->assertSame($expect, $ref->resolve($this->getDummyExpectingNoCalls(\Dependency_Container::class)));
    }

    protected function getDummyExpectingNoCalls(string $class)
    {
        $mock = $this->getDummy($class);
        $mock->expects($this->never())->method($this->anything());

        return $mock;
    }

    protected function getDummy(string $class)
    {
        return $this->getMockBuilder($class)
                    ->disableOriginalConstructor()
                    ->disableOriginalClone()
                    ->disableProxyingToOriginalMethods()
                    ->getMock();
    }

}
