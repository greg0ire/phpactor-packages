<?php

namespace Phpactor\Docblock\Tests\Tag;

use PHPUnit\Framework\TestCase;
use Phpactor\Docblock\Tag\MethodTag;
use Phpactor\Docblock\DocblockException;
use Phpactor\Docblock\Tag\DocblockTypes;
use Phpactor\Docblock\Tag\PropertyTag;

class PropertyTagTest extends TestCase
{
    public function testGetSet()
    {
        $tag = new PropertyTag(DocblockTypes::fromStringTypes([ 'Foobar']), 'foobar');
        $this->assertEquals('foobar', $tag->propertyName());
        $this->assertEquals(DocblockTypes::fromStringTypes([ 'Foobar' ]), $tag->types());
    }
}
