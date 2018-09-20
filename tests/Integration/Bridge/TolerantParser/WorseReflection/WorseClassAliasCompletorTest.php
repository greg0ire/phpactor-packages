<?php

namespace Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\WorseReflection;

use Generator;
use Phpactor\Completion\Bridge\TolerantParser\TolerantCompletor;
use Phpactor\Completion\Bridge\TolerantParser\WorseReflection\WorseClassAliasCompletor;
use Phpactor\Completion\Core\Suggestion;
use Phpactor\Completion\Tests\Integration\Bridge\TolerantParser\TolerantCompletorTestCase;
use Phpactor\WorseReflection\ReflectorBuilder;

class WorseClassAliasCompletorTest extends TolerantCompletorTestCase
{
    protected function createTolerantCompletor(string $source): TolerantCompletor
    {
        $reflector = ReflectorBuilder::create()->addSource($source)->build();
        return new WorseClassAliasCompletor($reflector);
    }

    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        $this->assertComplete($source, $expected);
    }

    public function provideComplete(): Generator
    {
        yield 'no imports' => [
            <<<'EOT'
<?php

$class = new B<>
EOT
        , 
            []
        ];

        yield 'import with no aliases' => [
            <<<'EOT'
<?php

use Barfoo;

$class = new B<>
EOT
        , 
            []
        ];

        yield 'import with aliased class' => [
            <<<'EOT'
<?php namespace {
    use Barfoo as BarfooThis;

    $class = new B<>
}
EOT
        , 
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'BarfooThis',
                    'short_description' => 'Alias for: Barfoo',
                ]
            ]
        ];

        yield 'import with aliased class and concrete class' => [
            <<<'EOT'
<?php namespace {
    use Barfoo as BarfooThis;
    use Barbar;

    $class = new B<>
}
EOT
        , 
            [
                [
                    'type' => Suggestion::TYPE_CLASS,
                    'name' => 'BarfooThis',
                    'short_description' => 'Alias for: Barfoo',
                ]
            ]
        ];
    }
}
