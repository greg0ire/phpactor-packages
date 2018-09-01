<?php

namespace Phpactor\CodeBuilder\Domain\Builder;

use Phpactor\CodeBuilder\Domain\Builder\Exception\InvalidBuilderException;

abstract class ClassLikeBuilder implements Builder
{
    const CHILDREN = [
        'methods',
    ];

    /**
     * @var SourceCodeBuilder
     */
    private $parent;

    /**
     * @var MethodBuilder[]
     */
    protected $methods = [];

    /**
     * @var string
     */
    protected $name;

    public static function childNames(): array
    {
        return [
            'methods',
        ];
    }

    public function __construct(SourceCodeBuilder $parent, string $name)
    {
        $this->parent = $parent;
        $this->name = $name;
    }

    public function add(Builder $builder)
    {
        if ($builder instanceof MethodBuilder) {
            $this->methods[$builder->builderName()] = $builder;
            return;
        }

        throw new InvalidBuilderException($this, $builder);
    }

    public function method(string $name): MethodBuilder
    {
        if (isset($this->methods[$name])) {
            return $this->methods[$name];
        }

        $this->methods[$name] = $builder = new MethodBuilder($this, $name);

        return $builder;
    }

    public function end(): SourceCodeBuilder
    {
        return $this->parent;
    }
}
