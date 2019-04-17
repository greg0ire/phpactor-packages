<?php

namespace Phpactor\CodeBuilder\Domain\Prototype;

use Phpactor\CodeBuilder\Domain\Type\Exception\TypeCannotBeNullableException;

final class Type extends Prototype
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $none = false;

    /**
     * @var bool
     */
    private $nullable = false;

    private function __construct(string $type = null, bool $nullable = false)
    {
        $this->type = $type;
        $this->nullable = $nullable;
    }

    public static function fromString(string $string, bool $nullableAllowed = false): Type
    {
        $nullable = 0 === strpos($string, '?');
        $type = $nullable ? substr($string, 1) : $string;

        $type = new self($type, $nullable);

        if ($nullable && !$nullableAllowed) {
            throw new TypeCannotBeNullableException($type);
        }

        return $type;
    }

    public static function none(): Type
    {
        $new = new self();
        $new->none = true;

        return $new;
    }

    public function __toString()
    {
        return $this->nullable ? sprintf('?%s', $this->type) : $this->type;
    }

    public function namespace(): ?string
    {
        if (null === $this->type) {
            return null;
        }

        if (false === strpos($this->type, '\\')) {
            return null;
        }

        return substr($this->type, 0, strpos($this->type, '\\'));
    }

    public function notNone(): bool
    {
        return false === $this->none;
    }

    public function nullable(): bool
    {
        return $this->nullable;
    }
}
