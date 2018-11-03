<?php

namespace Phpactor\Extension\SourceCodeFilesystem;

use Phpactor\Filesystem\Adapter\Composer\ComposerFileListProvider;
use Phpactor\Filesystem\Adapter\Git\GitFilesystem;
use Phpactor\Filesystem\Adapter\Simple\SimpleFilesystem;
use Phpactor\Filesystem\Domain\ChainFileListProvider;
use Phpactor\Filesystem\Domain\FilePath;
use Phpactor\Filesystem\Domain\MappedFilesystemRegistry;
use Phpactor\Filesystem\Domain\Exception\NotSupported;
use Phpactor\Filesystem\Domain\FallbackFilesystemRegistry;
use Phpactor\Container\Extension;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Container;
use Phpactor\MapResolver\Resolver;

class SourceCodeFilesystemExtension implements Extension
{
    const FILESYSTEM_GIT = 'git';
    const FILESYSTEM_COMPOSER = 'composer';
    const FILESYSTEM_SIMPLE = 'simple';
    const SERVICE_REGISTRY = 'source_code_filesystem.registry';
    const SERVICE_FILESYSTEM_GIT = 'source_code_filesystem.git';
    const SERVICE_FILESYSTEM_SIMPLE = 'source_code_filesystem.simple';
    const SERVICE_FILESYSTEM_COMPOSER = 'source_code_filesystem.composer';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
    }

    public function load(ContainerBuilder $container)
    {
        $this->registerFilesystems($container);
    }

    private function registerFilesystems(ContainerBuilder $container)
    {
        $container->register(self::SERVICE_REGISTRY, function (Container $container) {
            $filesystems = [];
            foreach ($container->getServiceIdsForTag('source_code_filesystem.filesystem') as $serviceId => $attributes) {
                try {
                    $filesystems[$attributes['name']] = $container->get($serviceId);
                } catch (NotSupported $exception) {
                    $container->get('monolog.logger')->warning(sprintf(
                        'Filesystem "%s" not supported: "%s"',
                        $attributes['name'],
                        $exception->getMessage()
                    ));
                }
            }
        
            return new FallbackFilesystemRegistry(
                new MappedFilesystemRegistry($filesystems),
                'simple'
            );
        });
        $container->register(self::SERVICE_FILESYSTEM_GIT, function (Container $container) {
            return new GitFilesystem(FilePath::fromString($container->getParameter('cwd')));
        }, [ 'source_code_filesystem.filesystem' => [ 'name' => self::FILESYSTEM_GIT ]]);
        
        $container->register(self::SERVICE_FILESYSTEM_SIMPLE, function (Container $container) {
            return new SimpleFilesystem(FilePath::fromString($container->getParameter('cwd')));
        }, [ 'source_code_filesystem.filesystem' => ['name' => self::FILESYSTEM_SIMPLE]]);
        
        $container->register(self::SERVICE_FILESYSTEM_COMPOSER, function (Container $container) {
            $providers = [];
            $cwd = FilePath::fromString($container->getParameter('cwd'));
            $classLoaders = $container->get('composer.class_loaders');
        
            if (!$classLoaders) {
                throw new NotSupported('No composer class loaders found/configured');
            }
        
            foreach ($classLoaders as $classLoader) {
                $providers[] = new ComposerFileListProvider($cwd, $classLoader);
            }
        
            return new SimpleFilesystem($cwd, new ChainFileListProvider($providers));
        }, [ 'source_code_filesystem.filesystem' => [ 'name' => self::FILESYSTEM_COMPOSER ]]);
    }
}
