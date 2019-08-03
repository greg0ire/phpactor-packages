<?php

namespace Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\Collection;

use Phpactor\WorseReflection\Core\ServiceLocator;
use Microsoft\PhpParser\Node\Statement\InterfaceDeclaration;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionInterface;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionClass;
use Microsoft\PhpParser\Node\Statement\TraitDeclaration;
use Phpactor\WorseReflection\Bridge\TolerantParser\Reflection\ReflectionTrait;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\Reflection\Collection\ReflectionClassCollection as CoreReflectionClassCollection;
use Microsoft\PhpParser\ClassLike;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\NamespacedNameInterface;
use Phpactor\WorseReflection\Core\Virtual\VirtualReflectionClassDecorator;

/**
 * @method \Phpactor\WorseReflection\Core\Reflection\ReflectionClass get()
 * @method \Phpactor\WorseReflection\Core\Reflection\ReflectionClass first()
 * @method \Phpactor\WorseReflection\Core\Reflection\ReflectionClass last()
 */
class ReflectionClassCollection extends AbstractReflectionCollection implements CoreReflectionClassCollection
{
    public static function fromNode(ServiceLocator $serviceLocator, SourceCode $source, Node $node)
    {
        $items = [];

        $nodeCollection = $node->getDescendantNodes(function (Node $node) {
            return false === $node instanceof ClassLike;
        });

        foreach ($nodeCollection as $child) {
            if (false === $child instanceof ClassLike) {
                continue;
            }

            if (false === $child instanceof NamespacedNameInterface) {
                continue;
            }

            if ($child instanceof TraitDeclaration) {
                $items[(string) $child->getNamespacedName()] =  new ReflectionTrait($serviceLocator, $source, $child);
                continue;
            }

            if ($child instanceof InterfaceDeclaration) {
                $items[(string) $child->getNamespacedName()] =  new ReflectionInterface($serviceLocator, $source, $child);
                continue;
            }

            $items[(string) $child->getNamespacedName()] = new VirtualReflectionClassDecorator(
                $serviceLocator,
                new ReflectionClass($serviceLocator, $source, $child),
                $serviceLocator->methodProviders()
            );
        }

        return new static($serviceLocator, $items);
    }

    public function concrete()
    {
        return new self($this->serviceLocator, array_filter($this->items, function ($item) {
            return $item->isConcrete();
        }));
    }

    protected function collectionType(): string
    {
        return CoreReflectionClassCollection::class;
    }
}
