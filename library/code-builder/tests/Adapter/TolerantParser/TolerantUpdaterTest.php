<?php

namespace Phpactor\CodeBuilder\Tests\Adapter\TolerantParser;

use Phpactor\CodeBuilder\Tests\Adapter\UpdaterTestCase;
use Phpactor\CodeBuilder\Adapter\TolerantParser\TolerantUpdater;
use Phpactor\CodeBuilder\Domain\Updater;
use Phpactor\CodeBuilder\Adapter\Twig\TwigRenderer;

class TolerantUpdaterTest extends UpdaterTestCase
{
    protected function updater(): Updater
    {
        return new TolerantUpdater(new TwigRenderer());
    }
}
