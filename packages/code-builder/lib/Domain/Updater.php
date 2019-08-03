<?php

namespace Phpactor\CodeBuilder\Domain;

use Phpactor\CodeBuilder\Domain\Prototype\Prototype;

interface Updater
{
    public function apply(Prototype $prototype, Code $code): Code;
}
