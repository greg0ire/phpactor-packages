<?php

namespace Phpactor\WorseReflection\Tests\Integration\Bridge\TolerantParser\Reflection;

use Phpactor\WorseReflection\Core\Visibility;
use Phpactor\WorseReflection\Tests\Integration\IntegrationTestCase;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionConstant;
use Phpactor\WorseReflection\Core\NameImports;

class ReflectionClassTest extends IntegrationTestCase
{
    /**
     * @expectedException Phpactor\WorseReflection\Core\Exception\ClassNotFound
     */
    public function testExceptionOnClassNotFound()
    {
        $this->createReflector('')->reflectClassLike(ClassName::fromString('Foobar'));
    }

    /**
     * @dataProvider provideReflectionClass
     */
    public function testReflectClass(string $source, string $class, \Closure $assertion)
    {
        $class = $this->createReflector($source)->reflectClassLike(ClassName::fromString($class));
        $assertion($class);
    }

    public function provideReflectionClass()
    {
        yield 'It reflects an empty class' => [
            <<<'EOT'
<?php

class Foobar
{
}
EOT
        ,
            'Foobar',
            function ($class) {
                $this->assertEquals('Foobar', (string) $class->name()->short());
                $this->assertInstanceOf(ReflectionClass::class, $class);
                $this->assertFalse($class->isInterface());
            },
        ];

        yield 'It reflects a class which extends another' => [
            <<<'EOT'
<?php
class Barfoo
{
}

class Foobar extends Barfoo
{
}
EOT
        ,
            'Foobar',
            function ($class) {
                $this->assertEquals('Foobar', (string) $class->name()->short());
                $this->assertEquals('Barfoo', (string) $class->parent()->name()->short());
            },
        ];

        yield 'It reflects class constants' => [
            <<<'EOT'
<?php

class Class1
{
    const EEEBAR = 'eeebar';
}

class Class2 extends Class1
{
    const FOOBAR = 'foobar';
    const BARFOO = 'barfoo';
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertCount(3, $class->constants());
                $this->assertInstanceOf(ReflectionConstant::class, $class->constants()->get('FOOBAR'));
                $this->assertInstanceOf(ReflectionConstant::class, $class->constants()->get('EEEBAR'));
            },
        ];

        yield 'It can provide the name of its last member' => [
            <<<'EOT'
<?php

class Class2
{
    private $foo;
    private $bar;
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals('bar', $class->properties()->last()->name());
            },
        ];

        yield 'It can provide the name of its first member' => [
            <<<'EOT'
<?php

class Class2
{
    private $foo;
    private $bar;
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals('foo', $class->properties()->first()->name());
            },
        ];

        yield 'It can provide its position' => [
            <<<'EOT'
<?php

class Class2
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(7, $class->position()->start());
            },
        ];

        yield 'It can provide the position of its member declarations' => [
            <<<'EOT'
<?php

class Class2
{
    private $foobar;
    private $barfoo;

    public function zed()
    {
    }
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(20, $class->memberListPosition()->start());
            },
        ];

        yield 'It provides list of its interfaces' => [
            <<<'EOT'
<?php

interface InterfaceOne
{
}

class Class2 implements InterfaceOne
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(1, $class->interfaces()->count());
                $this->assertEquals('InterfaceOne', $class->interfaces()->first()->name());
            },
        ];

        yield 'It list of interfaces includes interfaces from parent classes' => [
            <<<'EOT'
<?php

interface InterfaceOne
{
}

class Class1 implements InterfaceOne
{
}

class Class2 extends Class1
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(1, $class->interfaces()->count());
                $this->assertEquals('InterfaceOne', $class->interfaces()->first()->name());
            },
        ];

        yield 'It provides list of its traits' => [
            <<<'EOT'
<?php

trait TraitNUMBERone
{
    }

trait TraitNUMBERtwo
{
}

class Class2
{
    use TraitNUMBERone;
    use TraitNUMBERtwo;
}

EOT
        ,
            'Class2',
            function (ReflectionClass $class) {
                $this->assertEquals(2, $class->traits()->count());
                $this->assertEquals('TraitNUMBERone', $class->traits()->get('TraitNUMBERone')->name());
                $this->assertEquals('TraitNUMBERtwo', $class->traits()->get('TraitNUMBERtwo')->name());
            },
        ];

        yield 'Traits are inherited from parent classes (?)' => [
            <<<'EOT'
<?php

trait TraitNUMBERone
{
}

class Class2
{
    use TraitNUMBERone;
}

class Class1 extends Class2
{
}

EOT
        ,
            'Class1',
            function ($class) {
                $this->assertEquals(1, $class->traits()->count());
                $this->assertEquals('TraitNUMBERone', $class->traits()->first()->name());
            },
        ];

        yield 'Get methods includes trait methods' => [
            <<<'EOT'
<?php

trait TraitNUMBERone
{
    public function traitMethod1()
    {
    }
    }

trait TraitNUMBERtwo
{
    public function traitMethod2()
    {
    }
}

class Class2
{
    use TraitNUMBERone, TraitNUMBERtwo;

    public function notATrait()
    {
    }
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(3, $class->methods()->count());
                $this->assertTrue($class->methods()->has('traitMethod1'));
                $this->assertTrue($class->methods()->has('traitMethod2'));
            },
        ];

        yield 'Get methods includes aliased trait methods' => [
            <<<'EOT'
<?php

trait TraitOne
{
    public function one() {}
    public function three() {}
    public function four() {}
}

class Class2
{
    use TraitOne {
        one as private two;
        three as protected three;
    }

    public function one()
    {
    }
}

EOT
        ,
            'Class2',
            function (ReflectionClass $class) {
                $this->assertEquals(4, $class->methods()->count());
                $this->assertTrue($class->methods()->has('one'));
                $this->assertTrue($class->methods()->has('two'));
                $this->assertTrue($class->methods()->has('three'));
                $this->assertTrue($class->methods()->has('four'));
                $this->assertEquals(Visibility::private(), $class->methods()->get('two')->visibility());
                $this->assertEquals(Visibility::protected(), $class->methods()->get('three')->visibility());
                $this->assertFalse($class->methods()->belongingTo(ClassName::fromString(Class2::class))->has('two'));
                $this->assertEquals('TraitOne', $class->methods()->get('two')->declaringClass()->name()->short());
            },
        ];

        yield 'Get methods includes namespaced aliased trait methods' => [
            <<<'EOT'
<?php

namespace Bar;

trait TraitOne
{
    public function one() {}
    public function three() {}
}

class Class2
{
    use \Bar\TraitOne {
        one as private two;
        three as protected three;
    }

    public function one()
    {
    }
}

EOT
        ,
            'Bar\Class2',
            function (ReflectionClass $class) {
                $this->assertEquals(3, $class->methods()->count());
                $this->assertTrue($class->methods()->has('one'));
                $this->assertTrue($class->methods()->has('three'));
            },
        ];

        yield 'Get properties includes trait properties' => [
            <<<'EOT'
<?php

trait TraitNUMBERone
{
    private $prop1;
}

class Class2
{
    use TraitNUMBERone;
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(1, $class->properties()->count());
                $this->assertEquals('prop1', $class->properties()->first()->name());
            },
        ];

        yield 'Get methods at offset' => [
            <<<'EOT'
<?php

class Class2
{
    public function notATrait()
    {
    }
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(1, $class->methods()->atOffset(27)->count());
            },
        ];

        yield 'Get properties includes trait methods' => [
            <<<'EOT'
<?php

trait TraitNUMBERone
{
    public $foobar;
}

class Class2
{
    use TraitNUMBERone;

    private $notAFoobar;
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(2, $class->properties()->count());
                $this->assertEquals('foobar', $class->properties()->first()->name());
            },
        ];

        yield 'Get properties for belonging to' => [
            <<<'EOT'
<?php

class Class1
{
    public $foobar;
}

class Class2 extends Class1
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertCount(1, $class->properties()->belongingTo(ClassName::fromString('Class1')));
                $this->assertCount(0, $class->properties()->belongingTo(ClassName::fromString('Class2')));
            },
        ];


        yield 'If it extends an interface, then ignore' => [
            <<<'EOT'
<?php

interface SomeInterface
{
}

class Class2 extends SomeInterface
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(0, $class->methods()->count());
            },
        ];


        yield 'isInstanceOf returns false when it is not an instance of' => [
            <<<'EOT'
<?php

class Class2
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertFalse($class->isInstanceOf(ClassName::fromString('Foobar')));
            },
        ];

        yield 'isInstanceOf returns true for itself' => [
            <<<'EOT'
<?php

class Class2
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertTrue($class->isInstanceOf(ClassName::fromString('Class2')));
            },
        ];

        yield 'isInstanceOf returns true when it is not an instance of an interface' => [
            <<<'EOT'
<?php

interface SomeInterface
{
}

class Class2 implements SomeInterface
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertTrue($class->isInstanceOf(ClassName::fromString('SomeInterface')));
            },
        ];

        yield 'isInstanceOf returns true for a parent class' => [
            <<<'EOT'
<?php

class SomeParent
{
}

class Class2 extends SomeParent
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertTrue($class->isInstanceOf(ClassName::fromString('SomeParent')));
            },
        ];

        yield 'Returns source code' => [
            <<<'EOT'
<?php

class Class2
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertContains('class Class2', (string) $class->sourceCode());
            },
        ];

        yield 'Returns imported classes' => [
            <<<'EOT'
<?php

use Foobar\Barfoo;
use Barfoo\Foobaz as Carzatz;

class Class2
{
}

EOT
        ,
            'Class2',
            function ($class) {
                $this->assertEquals(NameImports::fromNames([
                    'Barfoo' => Name::fromString('Foobar\\Barfoo'),
                    'Carzatz' => Name::fromString('Barfoo\\Foobaz'),
                ]), $class->scope()->nameImports());
            },
        ];

        yield 'Inherits constants from interface' => [
            <<<'EOT'
<?php

use Foobar\Barfoo;
use Barfoo\Foobaz as Carzatz;

interface SomeInterface
{
    const SOME_CONSTANT = 'foo';
}

class Class2 implements SomeInterface
{
}

EOT
        ,
            'Class2',
            function (ReflectionClass $class) {
                $this->assertCount(1, $class->constants());
                $this->assertEquals('SOME_CONSTANT', $class->constants()->get('SOME_CONSTANT')->name());
            },
        ];

        yield 'Returns all members' => [
            <<<'EOT'
<?php

class Class1
{
    private const FOOBAR = 'foobar';
    private $foo;
    private function foobar() {}
}

EOT
        ,
            'Class1',
            function (ReflectionClass $class) {
                $this->assertCount(3, $class->members());
                $this->assertTrue($class->members()->has('FOOBAR'));
                $this->assertTrue($class->members()->has('foobar'));
                $this->assertTrue($class->members()->has('foo'));
            },
        ];

        yield 'Incomplete extends' => [
            <<<'EOT'
<?php

class Class1 extends
{
}

EOT
        ,
            'Class1',
            function (ReflectionClass $class) {
                $this->assertNull($class->parent());
                $this->assertEquals('Class1', $class->name()->short());
            },
        ];
    }

    /**
     * @dataProvider provideVirtualMethods
     */
    public function testVirtualMethods(string $source, string $class, \Closure $assertion)
    {
        $class = $this->createReflector($source)->reflectClassLike(ClassName::fromString($class));
        $assertion($class);
    }


    public function provideVirtualMethods()
    {
        yield 'virtual methods' => [
            <<<'EOT'
<?php

/**
 * @method \Foobar foobar()
 * @method \Foobar barfoo()
 */
class Class1
{
}

EOT
        ,
            'Class1',
            function ($class) {
                $this->assertEquals(2, $class->methods()->count());
                $this->assertEquals('foobar', $class->methods()->first()->name());
            }
        ];

        yield 'virtual methods merge onto existing ones' => [
            <<<'EOT'
<?php

/**
 * @method \Foobar foobar()
 */
class Class1
{
    public function foobar(): \Barfoo
    {
    }
}

EOT
        ,
            'Class1',
            function (ReflectionClass $class) {
                $this->assertCount(1, $class->methods());
                $this->assertEquals(
                    'Barfoo',
                    $class->methods()->first()->type()->__toString(),
                    'original type should be preserved'
                );
                $this->assertEquals('Foobar', $class->methods()->first()->inferredTypes()->best()->__toString());
            },
        ];

        yield 'virtual methods are inherited' => [
            <<<'EOT'
<?php


/** @method \Foobar foobar() */
class Class1 {}

class Class2 extends Class1 {
    public function foo()  {}
}

EOT
        ,
            'Class2',
            function (ReflectionClass $class) {
                $this->assertCount(2, $class->methods());
                $this->assertEquals(
                    'Foobar',
                    $class->methods()->get('foobar')->inferredTypes()->best()->__toString()
                );
            },
        ];

        yield 'virtual method types can be relative' => [
            '<?php namespace Bosh { /** @method Foobar foobar() */ class Class1 {}',
            'Bosh\Class1',
            function (ReflectionClass $class) {
                $this->assertEquals(
                    'Bosh\Foobar',
                    $class->methods()->get('foobar')->inferredTypes()->best()->__toString()
                );
            },
        ];

        yield 'virtual method types can be absolute' => [
            '<?php namespace Bosh { /** @method \Foobar foobar() */ class Class1 {}',
            'Bosh\Class1',
            function (ReflectionClass $class) {
                $this->assertEquals(
                    'Foobar',
                    $class->methods()->get('foobar')->inferredTypes()->best()->__toString()
                );
            },
        ];

        yield 'virtual methods of child classes override those of parents' => [
            <<<'EOT'
<?php


/** @method \Foobar foobar() */
class Class1 {}

/** @method \Barfoo foobar() */
class Class2 extends Class1 {
    public function foo()  {}
}

EOT
        ,
            'Class2',
            function (ReflectionClass $class) {
                $this->assertCount(2, $class->methods());
                $this->assertEquals(
                    'Barfoo',
                    $class->methods()->get('foobar')->inferredTypes()->best()->__toString()
                );
            },
        ];
    }

    /**
     * @dataProvider provideVirtualProperties
     */
    public function testVirtualProperties(string $source, string $class, \Closure $assertion)
    {
        $class = $this->createReflector($source)->reflectClassLike(ClassName::fromString($class));
        $assertion($class);
    }


    public function provideVirtualProperties()
    {
        yield 'virtual properties' => [
            <<<'EOT'
<?php

/**
 * @property \Foobar $foobar
 * @property \Foobar $barfoo
 */
class Class1
{
}

EOT
        ,
            'Class1',
            function ($class) {
                $this->assertEquals(2, $class->properties()->count());
                $this->assertEquals('foobar', $class->properties()->first()->name());
            }
        ];

        yield 'invalid properties' => [
            <<<'EOT'
<?php

/**
 * @property $foobar
 * @property
 */
class Class1
{
}

EOT
        ,
            'Class1',
            function ($class) {
                $this->assertEquals(0, $class->properties()->count());
            }
        ];

        yield 'multiple types' => [
            <<<'EOT'
<?php

/**
 * @property string|int $foobar
 */
class Class1
{
}

EOT
        ,
            'Class1',
            function (ReflectionClass $class) {
                $this->assertEquals(1, $class->properties()->count());
                $this->assertCount(2, $class->properties()->first()->inferredTypes());
            }
        ];
    }
}
