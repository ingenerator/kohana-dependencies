<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2014 inGenerator Ltd
 * @licence   BSD
 */

namespace test\unit\Ingenerator\KohanaDependencies;

use ArrayObject;
use DateTime;
use Dependency_Compiler;
use Dependency_Definition_List;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use function class_exists;
use function file_exists;
use function file_get_contents;
use function method_exists;
use function preg_match;
use function uniqid;
use function var_dump;

/**
 *
 * @see \Dependency_Compiler
 */
class Dependency_CompilerTest extends TestCase
{

    function setUp(): void
    {
        vfsStream::setup('compiled');
    }

    function test_it_is_initializable()
    {
        $this->assertInstanceOf(Dependency_Compiler::class, $this->newSubject());
    }

    function test_it_creates_file_at_requested_path()
    {
        $file = $this->given_compiled_to_temp_file(uniqid('services'), new Dependency_Definition_List);
        $this->assertTrue(file_exists($file));
    }

    function test_it_compiles_class_definition_for_requested_class_name()
    {
        $this->given_compiled_to_unique_class(new Dependency_Definition_List);
    }

    function its_compiled_class_extends_kohana_dependency_container_if_classname_conflicts()
    {
        $file = $this->given_compiled_to_temp_file('SomeClass', new Dependency_Definition_List);
        $this->assertMatchesRegularExpression(
            '/class SomeClass extends \\\\Dependency_Container/',
            file_get_contents($file)
        );
    }


    function its_compiled_class_is_an_instantiable_dependency_container()
    {
        $dependencies = new Dependency_Definition_List;
        $class        = $this->given_compiled_to_unique_class(new Dependency_Definition_List);
        $subj         = new $class($dependencies);
        $this->assertInstanceOf($class, $subj);
        $this->assertInstanceOf(\Dependency_Container::class, $subj);
    }

    function test_it_adds_getter_for_each_defined_service()
    {
        $class = $this->given_compiled_to_unique_class(
            Dependency_Definition_List::factory()
                                      ->from_array(
                                          [
                                              'date'  => ['_settings' => ['class' => '\DateTime']],
                                              'array' => ['_settings' => ['class' => '\ArrayObject']],
                                          ]
                                      )
        );

        $this->assertTrue(method_exists($class, 'get_date'), 'has get_date');
        $this->assertTrue(method_exists($class, 'get_array'), 'has get_array');
    }

    function test_it_adds_getter_with_underscores_for_nested_services()
    {
        $class = $this->given_compiled_to_unique_class(
            Dependency_Definition_List::factory()
                                      ->from_array(
                                          [
                                              'date' => [
                                                  'time' => ['_settings' => ['class' => '\DateTime']],
                                              ],
                                          ]
                                      )
        );

        $this->assertTrue(method_exists($class, 'get_date_time'), 'has get_date_time');
    }

    function its_generated_service_container_can_create_services()
    {
        $definitions = Dependency_Definition_List::factory()
                                                 ->from_array(['date' => ['_settings' => ['class' => '\DateTime']]]);

        $class     = $this->given_compiled_to_unique_class($definitions);
        $container = new $class($definitions);
        $this->assertInstanceOf(\DateTime::class, $container->get_date());
    }

    function test_it_declares_correct_getter_phpdoc_return_type_with_for_simple_service()
    {
        $class = $this->given_compiled_to_unique_class(
            Dependency_Definition_List::factory()
                                      ->from_array(['date' => ['_settings' => ['class' => '\DateTime']]])
        );
        $this->assertDeclaresMethodReturnType('\DateTime', 'get_date', $class);
    }

    function test_it_declares_correct_getter_phpdoc_return_type_for_service_provided_by_static_factory_with_phpdoc()
    {
        $class = $this->given_compiled_to_unique_class(
            Dependency_Definition_List::factory()
                                      ->from_array(
                                          [
                                              'array' => [
                                                  '_settings' => [
                                                      'class'       => __NAMESPACE__.'\DocumentedArrayObjectFactory',
                                                      'constructor' => 'factory',
                                                  ],
                                              ],
                                          ]
                                      )
        );
        $this->assertDeclaresMethodReturnType(ArrayObject::class, 'get_array', $class);
    }

    function test_it_declares_mixed_return_type_for_service_provided_by_static_factory_with_no_phpdoc()
    {
        $class = $this->given_compiled_to_unique_class(
            Dependency_Definition_List::factory()
                                      ->from_array(
                                          [
                                              'array' => [
                                                  '_settings' => [
                                                      'class'       => __NAMESPACE__.'\UndocumentedFactory',
                                                      'constructor' => 'factory',
                                                  ],
                                              ],
                                          ]
                                      )
        );

        $this->assertDeclaresMethodReturnType('mixed', 'get_array', $class);
    }

    function test_it_alpha_sorts_getters()
    {
        $file = $this->given_compiled_to_temp_file(
            uniqid('services'),
            Dependency_Definition_List::factory()
                                      ->from_array(
                                          [
                                              'date'  => ['_settings' => ['class' => '\DateTime']],
                                              'array' => ['_settings' => ['class' => '\ArrayObject']],
                                          ]
                                      )
        );

        $this->assertMatchesRegularExpression(
            '/get_array.+?get_date/s',
            file_get_contents($file)
        );
    }

    function test_it_throws_if_service_returns_unexpected_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('date');
        $this->given_compiled_to_unique_class(
            Dependency_Definition_List::factory()
                                      ->from_array(
                                          [
                                              'date' => [
                                                  '_settings' => [
                                                      'class'       => __NAMESPACE__.'\BadlyDocumentedFactory',
                                                      'constructor' => 'factory',
                                                  ],
                                              ],
                                          ]
                                      )
        );
    }

    function test_it_throws_if_service_refers_to_undefined_class()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('undefined');
        $this->given_compiled_to_unique_class(
            Dependency_Definition_List::factory()
                                      ->from_array(
                                          [
                                              'undefined' => [
                                                  '_settings' => [
                                                      'class' => __NAMESPACE__
                                                                 .'\\UndefinedClass',
                                                  ],
                                              ],
                                          ]
                                      )
        );
    }

    function test_it_throws_if_service_dependencies_cannot_be_met_from_the_container()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid');
        $this->given_compiled_to_unique_class(
            Dependency_Definition_List::factory()
                                      ->from_array(
                                          [
                                              'invalid' => [
                                                  '_settings' => [
                                                      'class'     => '\DateTime',
                                                      'arguments' => ['%undefined%'],
                                                  ],
                                              ],
                                          ]
                                      )
        );
    }

    /**
     * @param string                     $class
     * @param Dependency_Definition_List $definitions
     *
     * @return string the filename
     */
    function given_compiled_to_temp_file(string $class, Dependency_Definition_List $definitions)
    {
        $file = vfsStream::url('compiled/'.$class.'.php');
        $this->newSubject()->compile($class, $file, $definitions);

        return $file;
    }

    /**
     * @param Dependency_Definition_List $definitions
     *
     * @return object
     */
    protected function given_compiled_to_unique_class(Dependency_Definition_List $definitions)
    {
        $class = uniqid('services');
        $this->assertFalse(class_exists($class), 'Should not exist before built');
        $this->given_compiled_to_temp_file($class, $definitions);
        $this->assertTrue(class_exists($class), 'Should exist after built');

        return $class;
    }

    public function getMatchers(): array
    {
        $matchers = parent::getMatchers();


        $matchers['declareMethodReturnType'] = function ($class, $method_name, $expect_type) {
            $reflection = new ReflectionClass($class);
            $method     = $reflection->getMethod($method_name);
            $comment    = $method->getDocComment();

            if ( ! preg_match('/^\s+\* @return (.+?)$/m', $comment, $matches)) {
                throw new FailureException(
                    "Expected $method_name doc comment to define @return tag but it does not: ".$comment
                );
            }
            if ($expect_type !== $matches[1]) {
                var_dump($matches[1]);
                throw new FailureException("Expected $method_name @return of ".$expect_type.", got ".$matches[1]);
            }

            return TRUE;
        };

        return $matchers;
    }

    private function assertDeclaresMethodReturnType(
        string $expect_type,
        string $method_name,
        string $container_class
    ) {
        $reflection = new ReflectionClass($container_class);
        $method     = $reflection->getMethod($method_name);
        $comment    = $method->getDocComment();

        $this->assertTrue(
            (bool) preg_match('/^\s+\* @return (.+?)$/m', $comment, $matches),
            "Expected $method_name doc comment to define @return tag but it does not: ".$comment
        );
        $this->assertSame($expect_type, $matches[1]);
    }

    protected function newSubject(): Dependency_Compiler
    {
        return new Dependency_Compiler;
    }

}

class DocumentedArrayObjectFactory
{
    /**
     * @return ArrayObject
     */
    public static function factory()
    {
        return new ArrayObject;
    }
}

class UndocumentedFactory
{

    public static function factory()
    {
        return new ArrayObject;
    }
}

class BadlyDocumentedFactory
{

    /**
     * @return DateTime
     */
    public static function factory()
    {
        return new ArrayObject;
    }
}

class ClassWithDependencies
{
    public function __construct(DateTime $time)
    {

    }
}
