<?php

namespace DTL\Filesystem\Tests\Adapter;

use DTL\Filesystem\Tests\Adapter\IntegrationTestCase;
use DTL\Filesystem\Adapter\Simple\SimpleFilesystem;
use DTL\Filesystem\Domain\FilePath;
use DTL\Filesystem\Domain\AbsoluteExistingPath;
use DTL\Filesystem\Domain\Filesystem;

abstract class AdapterTestCase extends IntegrationTestCase
{
    private $filesystem;

    public function setUp()
    {
        $this->initWorkspace();
        $this->loadProject();
    }

    abstract protected function filesystem(): Filesystem;

    protected function basePath()
    {
        return AbsoluteExistingPath::fromString($this->workspacePath());
    }

    public function testFind()
    {
        $fileList = $this->filesystem()->fileList();

        $location = FilePath::fromString('src/Hello/Goodbye.php');
        $foo = $fileList->contains($location);
        $this->assertTrue($fileList->contains(FilePath::fromString('src/Foobar.php')));
        $this->assertTrue($foo);
    }

    public function testRemove()
    {
        $file = FilePath::fromString('src/Hello/Goodbye.php');
        $absolutePath = $this->basePath()->concatExistingLocation($file);
        $this->assertTrue(file_exists($absolutePath));
        $this->filesystem()->remove($file);
        $this->assertFalse(file_exists($absolutePath));
    }

    public function testMove()
    {
        $srcLocation = FilePath::fromString('src/Hello/Goodbye.php');
        $destLocation = FilePath::fromString('src/Hello/Hello.php');

        $this->filesystem()->move($srcLocation, $destLocation);
        $this->assertTrue(file_exists($this->basePath()->concatExistingLocation($destLocation)));
        $this->assertFalse(file_exists($this->basePath()->concatNonExistingLocation($srcLocation)));
    }

    public function testCopy()
    {
        $srcLocation = FilePath::fromString('src/Hello/Goodbye.php');
        $destLocation = FilePath::fromString('src/Hello/Hello.php');

        $this->filesystem()->copy($srcLocation, $destLocation);
        $this->assertTrue(file_exists($this->basePath()->concatExistingLocation($destLocation)));
        $this->assertTrue(file_exists($this->basePath()->concatExistingLocation($srcLocation)));
    }

    public function testAbsolutePath()
    {
        $location = FilePath::fromString('src/Hello/Goodbye.php');
        $absolute = $this->filesystem()->absolutePath($location);
        $this->assertTrue(file_exists($absolute));
    }
}
