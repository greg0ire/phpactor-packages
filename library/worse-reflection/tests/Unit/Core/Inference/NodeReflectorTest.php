<?php

namespace Phpactor\WorseReflection\Tests\Unit\Core\Inference;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Core\ServiceLocator;
use Phpactor\WorseReflection\Core\Inference\NodeReflector;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Microsoft\PhpParser\Node\SourceFileNode;

class NodeReflectorTest extends TestCase
{
    public function testUnkown()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Did not know how');
        $frame = new Frame('test');
        $locator = $this->prophesize(ServiceLocator::class);
        $nodeReflector = new NodeReflector($locator->reveal());

        $nodeReflector->reflectNode($frame, new SourceFileNode());
    }
}
