<?php

namespace Phpactor\ClassMover\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Phpactor\ClassMover\Domain\Model\ClassMemberQuery;

class ClassMemberQueryTest extends TestCase
{
    public function testOnlyConstants()
    {
        $query = ClassMemberQuery::create()->onlyConstants();
        $this->assertEquals(ClassMemberQuery::TYPE_CONSTANT, $query->type());
    }

    public function testOnlyMethods()
    {
        $query = ClassMemberQuery::create()->onlyMethods();
        $this->assertEquals(ClassMemberQuery::TYPE_METHOD, $query->type());
    }

    public function testOnlyProperties()
    {
        $query = ClassMemberQuery::create()->onlyProperties();
        $this->assertEquals(ClassMemberQuery::TYPE_PROPERTY, $query->type());
    }

    public function testHasType()
    {
        $query = ClassMemberQuery::create();
        $this->assertFalse($query->hasType());

        $query = $query->onlyConstants();
        $this->assertTrue($query->hasType());
    }
}
