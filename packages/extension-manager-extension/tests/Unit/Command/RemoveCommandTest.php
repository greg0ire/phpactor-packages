<?php

namespace Phpactor\Extension\ExtensionManager\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Phpactor\Extension\ExtensionManager\Command\RemoveCommand;
use Phpactor\Extension\ExtensionManager\Model\Extension;
use Phpactor\Extension\ExtensionManager\Model\ExtensionState;
use Phpactor\Extension\ExtensionManager\Model\Extensions;
use Phpactor\Extension\ExtensionManager\Service\RemoverService;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class RemoveCommandTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $remover;

    /**
     * @var CommandTester
     */
    private $tester;


    public function setUp()
    {
        $this->remover = $this->prophesize(RemoverService::class);
        $this->tester = new CommandTester(new RemoveCommand($this->remover->reveal()));
    }

    public function testRemovesAnExtension()
    {
        $this->remover->findDependentExtensions(['foo'])->willReturn(new Extensions([]));
        $this->remover->removeExtension('foo')->shouldBeCalled();


        $this->tester->execute([
            'extension' => ['foo'],
        ]);

        $this->assertEquals(0, $this->tester->getStatusCode());
    }

    public function testRemovesAnExtensionAndDependentExtensions()
    {
        $this->remover->findDependentExtensions(['foo'])->willReturn(new Extensions([
            new Extension('bar', 'foo', 'desc', 'class'),
            new Extension('baz', 'foo', 'desc', 'class'),
        ]));

        $this->remover->removeExtension('foo')->shouldBeCalled();
        $this->remover->removeExtension('bar')->shouldBeCalled();
        $this->remover->removeExtension('baz')->shouldBeCalled();

        $this->tester->execute([
            'extension' => ['foo'],
        ], [
            'interactive' => false,
        ]);

        $this->assertEquals(0, $this->tester->getStatusCode());
    }

    public function testFailsIfAnyOfTheDependentPackagesArePrimary()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('primary exten');
        $this->remover->findDependentExtensions(['foo'])->willReturn(new Extensions([
            new Extension('bar', 'foo', 'desc', 'class'),
            new Extension('baz', 'foo', 'desc', 'class', [], ExtensionState::STATE_PRIMARY),
        ]));

        $this->tester->execute([
            'extension' => ['foo'],
        ], [
            'interactive' => false,
        ]);
    }
}
