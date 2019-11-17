<?php

namespace Phpactor\WorseReflection\Bridge\TolerantParser\Reflector;

use Microsoft\PhpParser\Node\SourceFileNode;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionClassCollection;
use Phpactor\WorseReflection\Core\Reflection\ReflectionOffset;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethodCall;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionClassCollection as TolerantReflectionClassCollection;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\Offset;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionOffset as TolerantReflectionOffset;
use Phpactor\WorseReflection\Core\Inference\NodeReflector;
use Phpactor\WorseReflection\Core\ServiceLocator;
use Microsoft\PhpParser\Parser;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionFunctionCollection as CoreReflectionFunctionCollection;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection\ReflectionFunctionCollection as TolerantReflectionFunctionCollection;

class TolerantSourceCodeReflector implements SourceCodeReflector
{
    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    /**
     * @var Parser
     */
    private $parser;

    public function __construct(ServiceLocator $serviceLocator, Parser $parser)
    {
        $this->serviceLocator = $serviceLocator;
        $this->parser = $parser;
    }

    /**
     * {@inheritDoc}
     */
    public function reflectClassesIn($sourceCode): ReflectionClassCollection
    {
        $sourceCode = SourceCode::fromUnknown($sourceCode);
        $node = $this->parseSourceCode($sourceCode);
        return TolerantReflectionClassCollection::fromNode($this->serviceLocator, $sourceCode, $node);
    }

    /**
     * {@inheritDoc}
     */
    public function reflectOffset($sourceCode, $offset): ReflectionOffset
    {
        $sourceCode = SourceCode::fromUnknown($sourceCode);
        $offset = Offset::fromUnknown($offset);

        $rootNode = $this->parseSourceCode($sourceCode);
        $node = $rootNode->getDescendantNodeAtPosition($offset->toInt());

        $resolver = $this->serviceLocator->symbolContextResolver();
        $frame = $this->serviceLocator->frameBuilder()->build($node);

        return TolerantReflectionOffset::fromFrameAndSymbolContext($frame, $resolver->resolveNode($frame, $node));
    }

    public function reflectMethodCall($sourceCode, $offset): ReflectionMethodCall
    {
        $reflection = $this->reflectNode($sourceCode, $offset);

        if (false === $reflection instanceof ReflectionMethodCall) {
            throw new \RuntimeException(sprintf(
                'Expected method call, got "%s"',
                get_class($reflection)
            ));
        }

        return $reflection;
    }

    private function reflectNode($sourceCode, $offset)
    {
        $sourceCode = SourceCode::fromUnknown($sourceCode);
        $offset = Offset::fromUnknown($offset);

        $rootNode = $this->parseSourceCode($sourceCode);
        $node = $rootNode->getDescendantNodeAtPosition($offset->toInt());

        $frame = $this->serviceLocator->frameBuilder()->build($node);
        $nodeReflector = new NodeReflector($this->serviceLocator);

        return $nodeReflector->reflectNode($frame, $node);
    }

    /**
     * {@inheritDoc}
     */
    public function reflectFunctionsIn($sourceCode): CoreReflectionFunctionCollection
    {
        $sourceCode = SourceCode::fromUnknown($sourceCode);
        $node = $this->parseSourceCode($sourceCode);
        return TolerantReflectionFunctionCollection::fromNode($this->serviceLocator, $sourceCode, $node);
    }

    private function parseSourceCode(SourceCode $sourceCode): SourceFileNode
    {
        $rootNode = $this->parser->parseSourceFile((string) $sourceCode, $sourceCode->path());
        return $rootNode;
    }
}
