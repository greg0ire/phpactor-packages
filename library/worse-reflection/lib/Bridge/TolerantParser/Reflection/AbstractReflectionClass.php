<?php

namespace Phpactor\WorseReflection\Bridge\TolerantParser\Reflection;

use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;

abstract class AbstractReflectionClass extends AbstractReflectedNode implements ReflectionClassLike
{
    /**
     * @deprecated Use instanceof instead
     */
    public function isInterface(): bool
    {
        return $this instanceof ReflectionInterface;
    }

    /**
     * @deprecated Use instanceof instead
     */
    public function isTrait(): bool
    {
        return $this instanceof ReflectionTrait;
    }

    /**
     * @deprecated Use instanceof instead
     */
    public function isClass(): bool
    {
        return $this instanceof ReflectionClass;
    }

    public function isConcrete(): bool
    {
        return false;
    }
}
