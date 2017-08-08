<?php

namespace Phpactor\WorseReflection\Tests\Benchmarks;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\ClassName;
use Phpactor\WorseReflection\Reflection\ReflectionMethod;

/**
 * @Iterations(4)
 * @Revs(10)
 * @OutputTimeUnit("milliseconds", precision=2)
 */
class PhpUnitReflectClassBench extends BaseBenchCase
{

    /**
     * @Subject()
     */
    public function test_case()
    {
        $class = $this->getReflector()->reflectClass(ClassName::fromString(TestCase::class));
    }

    /**
     * @Subject()
     */
    public function test_case_methods_and_properties()
    {
        $class = $this->getReflector()->reflectClass(ClassName::fromString(TestCase::class));

        /** @var $method ReflectionMethod */
        foreach ($class->methods() as $method) {
            foreach ($method->parameters() as $parameter) {
            }
        }

        foreach ($class->properties() as $property) {
            $property->type();
        }
    }
}
