<?php

namespace Phpactor\WorseReflection\Tests\Unit\Core\Reflector\ClassReflector;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Core\Name;
use Phpactor\WorseReflection\Core\Reflector\ClassReflector;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Reflector\ClassReflector\MemonizedReflector;
use Phpactor\WorseReflection\Core\Reflector\FunctionReflector;

class MemonizedClassReflectorTest extends TestCase
{
    /**
     * @var ClassReflector|ObjectProphecy
     */
    private $innerClassReflector;

    /**
     * @var MemonizedClassReflector
     */
    private $reflector;

    /**
     * @var ObjectProphecy|FunctionReflector
     */
    private $innerFunctionReflector;

    /**
     * @var ClassName
     */
    private $className;

    public function setUp()
    {
        $this->innerClassReflector = $this->prophesize(ClassReflector::class);
        $this->innerFunctionReflector = $this->prophesize(FunctionReflector::class);

        $this->reflector = new MemonizedReflector(
            $this->innerClassReflector->reveal(),
            $this->innerFunctionReflector->reveal()
        );
        $this->className = ClassName::fromString('Hello');
    }

    public function testReflectClass()
    {
        $this->innerClassReflector->reflectClass($this->className)->shouldBeCalledTimes(1);
        $this->reflector->reflectClass($this->className);
        $this->reflector->reflectClass($this->className);
        $this->reflector->reflectClass($this->className);
    }

    public function testReflectInterface()
    {
        $this->innerClassReflector->reflectInterface($this->className)->shouldBeCalledTimes(1);
        $this->reflector->reflectInterface($this->className);
        $this->reflector->reflectInterface($this->className);
        $this->reflector->reflectInterface($this->className);
    }

    public function testReflectTrait()
    {
        $this->innerClassReflector->reflectTrait($this->className)->shouldBeCalledTimes(1);
        $this->reflector->reflectTrait($this->className);
        $this->reflector->reflectTrait($this->className);
        $this->reflector->reflectTrait($this->className);
    }

    public function testReflectClassLike()
    {
        $this->innerClassReflector->reflectClassLike($this->className)->shouldBeCalledTimes(1);
        $this->reflector->reflectClassLike($this->className);
        $this->reflector->reflectClassLike($this->className);
        $this->reflector->reflectClassLike($this->className);
    }

    public function testReflectFunction()
    {
        $name = Name::fromString('Foo');
        $this->innerFunctionReflector->reflectFunction($name)->shouldBeCalledTimes(1);
        $this->reflector->reflectFunction($name);
        $this->reflector->reflectFunction($name);
        $this->reflector->reflectFunction($name);
    }
}
