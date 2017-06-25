<?php

namespace DTL\Filesystem\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class IntegrationTestCase extends TestCase
{
    protected function initWorkspace()
    {
        $filesystem = new Filesystem();
        if ($filesystem->exists($this->workspacePath())) {
            $filesystem->remove($this->workspacePath());
        }

        $filesystem->mkdir($this->workspacePath());
    }

    protected function workspacePath()
    {
        return __DIR__.'/workspace';
    }

    protected function loadProject()
    {
        $projectPath = __DIR__.'/project';
        $filesystem = new Filesystem();
        $filesystem->mirror($projectPath, $this->workspacePath());
        chdir($this->workspacePath());
        exec('composer dumpautoload 2> /dev/null');
    }

    protected function getProjectAutoloader()
    {
        return require __DIR__.'/project/vendor/autoload.php';
    }
}
