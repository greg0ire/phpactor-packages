<?php

namespace Phpactor\WorseReflection\Core\Reflection\Collection;

/**
 * @method \Phpactor\WorseReflection\Core\Reflection\ReflectionMethod first()
 * @method \Phpactor\WorseReflection\Core\Reflection\ReflectionMethod last()
 * @method \Phpactor\WorseReflection\Core\Reflection\ReflectionMethod get(string $name)
 */
interface ReflectionMethodCollection extends ReflectionMemberCollection
{
    public function abstract();
}
