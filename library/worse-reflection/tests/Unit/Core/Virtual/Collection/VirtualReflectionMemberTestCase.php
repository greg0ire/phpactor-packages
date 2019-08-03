<?php

namespace Phpactor\WorseReflection\Tests\Unit\Core\Virtual\Collection;

use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Position;
use Phpactor\WorseReflection\Core\Visibility;

/**
 * @method \Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionMemberCollection collection()
 */
abstract class VirtualReflectionMemberTestCase extends AbstractReflectionCollectionTestCase
{
    /**
     * @var ObjectProphecy
     */
    protected $declaringClass;

    /**
     * @var ObjectProphecy
     */
    protected $class;

    protected $position;

    public function setUp()
    {
        $this->declaringClass = $this->prophesize(ReflectionClass::class);
        $this->class = $this->prophesize(ReflectionClass::class);
        $this->position = Position::fromStartAndEnd(0, 10);
    }

    public function testByName()
    {
        $collection = $this->collection(['one', 'two'])->byName('one');
        $this->assertCount(1, $collection);
    }

    public function testByVisiblities()
    {
        $collection = $this->collection(['one', 'two'])->byVisibilities([
            Visibility::public()
        ]);
        $this->assertCount(2, $collection);
    }

    public function testBelongingTo()
    {
        $belongingTo = ClassName::fromString('Hello');
        $this->declaringClass->name()->willReturn($belongingTo);
        $collection = $this->collection(['one', 'two'])->belongingTo($belongingTo);
        $this->assertCount(2, $collection);
    }

    public function testAtOffset()
    {
        $collection = $this->collection(['one', 'two'])->atoffset(0);
        $this->assertcount(2, $collection);
    }

    public function testVirtual()
    {
        $collection = $this->collection(['one', 'two']);
        $this->assertCount(2, $collection->virtual());
    }

    public function testReal()
    {
        $collection = $this->collection(['one', 'two']);
        $this->assertCount(0, $collection->real());
    }
}
