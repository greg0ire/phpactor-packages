<?php

namespace Phpactor\Extension\ExtensionManager\Model;

class ExtensionFileGenerator
{
    const EXTENSION_CLASS_PROPERTY = 'phpactor.extension_class';

    /**
     * @var string
     */
    private $extensionListFile;

    public function __construct(string $extensionListFile)
    {
        $this->extensionListFile = $extensionListFile;
    }

    /**
     * @param Extensions<Extension> $extensions
     */
    public function writeExtensionList(Extensions $extensions)
    {
        $code = [
            '<?php',
            '// ' . date('c'),
            '// this file is autogenerated by phpactor do not edit it',
            '',
            'return ['
        ];

        foreach ($extensions as $extension) {
            $code[] = sprintf('  "\\%s",', $extension->className());
        }

        $code[] = '];';

        if (!file_exists(dirname($this->extensionListFile))) {
            mkdir(dirname($this->extensionListFile), 0777, true);
        }

        file_put_contents($this->extensionListFile, implode(PHP_EOL, $code));
    }
}
