<?php

namespace Phpactor\WorseReflection\Core;

class Visibility
{
    private $visibility;

    public static function public()
    {
        return self::create('public');
    }

    public static function private()
    {
        return self::create('private');
    }

    public static function protected()
    {
        return self::create('protected');
    }

    public function isPublic()
    {
        return $this->visibility === 'public';
    }

    public function isProtected()
    {
        return $this->visibility === 'protected';
    }

    public function isPrivate()
    {
        return $this->visibility === 'private';
    }

    public function __toString()
    {
        return $this->visibility;
    }

    private function __construct()
    {
    }

    private static function create($visibility)
    {
        $instance = new self();
        $instance->visibility = $visibility;

        return $instance;
    }
}
