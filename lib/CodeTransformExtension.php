<?php

namespace Phpactor\Extension\CodeTransform;

use Phpactor\CodeTransform\CodeTransform;
use Phpactor\CodeTransform\Domain\Generators;
use Phpactor\CodeTransform\Domain\Transformers;
use Phpactor\Container\Container;
use Phpactor\Container\ContainerBuilder;
use Phpactor\Container\Extension;
use Phpactor\Extension\ClassToFile\ClassToFileExtension;
use Phpactor\Extension\Rpc\RpcExtension;
use Phpactor\MapResolver\Resolver;
use Phpactor\Extension\CodeTransform\Rpc\ClassInflectHandler;
use Phpactor\Extension\CodeTransform\Rpc\ClassNewHandler;
use Phpactor\Extension\CodeTransform\Rpc\TransformHandler;
use RuntimeException;

class CodeTransformExtension implements Extension
{
    const TAG_FROM_EXISTING_GENERATOR = 'code_transform.from_existing_generator';
    const TAG_TRANSFORMER = 'code_transform.transformer';
    const TAG_NEW_CLASS_GENERATOR = 'code_transform.new_class_generator';

    /**
     * {@inheritDoc}
     */
    public function configure(Resolver $schema)
    {
        $schema->setDefaults([
        ]);
    }

    public function load(ContainerBuilder $container)
    {
        $this->registerTransformers($container);
        $this->registerGenerators($container);
        $this->registerRpc($container);
    }

    private function registerTransformers(ContainerBuilder $container)
    {
        $container->register('code_transform.transformers', function (Container $container) {
            $transformers = [];
            foreach ($container->getServiceIdsForTag(self::TAG_TRANSFORMER) as $serviceId => $attrs) {
                $transformers[$attrs['name']] = $container->get($serviceId);
            }

            return Transformers::fromArray($transformers);
        });

        $container->register('code_transform.transform', function (Container $container) {
            return CodeTransform::fromTransformers($container->get('code_transform.transformers'));
        });
    }

    private function registerGenerators(ContainerBuilder $container)
    {
        $container->register('code_transform.new_class_generators', function (Container $container) {
            $generators = [];
            foreach ($container->getServiceIdsForTag(self::TAG_NEW_CLASS_GENERATOR) as $serviceId => $attrs) {
                $this->assertNameAttribute($attrs, $serviceId);
                $generators[$attrs['name']] = $container->get($serviceId);
            }

            return Generators::fromArray($generators);
        });

        $container->register('code_transform.from_existing_generators', function (Container $container) {
            $generators = [];
            foreach ($container->getServiceIdsForTag(self::TAG_FROM_EXISTING_GENERATOR) as $serviceId => $attrs) {
                $this->assertNameAttribute($attrs, $serviceId);
                $generators[$attrs['name']] = $container->get($serviceId);
            }

            return Generators::fromArray($generators);
        });
    }

    private function registerRpc(ContainerBuilder $container)
    {
        $container->register('code_transform.rpc.handler.class_inflect', function (Container $container) {
            return new ClassInflectHandler(
                $container->get('code_transform.from_existing_generators'),
                $container->get(ClassToFileExtension::SERVICE_CONVERTER)
            );
        }, [ RpcExtension::TAG_RPC_HANDLER => ['name' => ClassInflectHandler::NAME] ]);

        $container->register('code_transform.rpc.handler.class_new', function (Container $container) {
            return new ClassNewHandler(
                $container->get('code_transform.from_existing_generators'),
                $container->get(ClassToFileExtension::SERVICE_CONVERTER)
            );
        }, [ RpcExtension::TAG_RPC_HANDLER => ['name' => ClassNewHandler::NAME] ]);


        $container->register('code_transform.rpc.handler.transform', function (Container $container) {
            return new TransformHandler(
                $container->get('code_transform.transform')
            );
        }, [ RpcExtension::TAG_RPC_HANDLER => ['name' => TransformHandler::NAME] ]);
    }

    private function assertNameAttribute($attrs, $serviceId)
    {
        if (!isset($attrs['name'])) {
            throw new RuntimeException(sprintf(
                'Generator "%s" must be registered with the "name" tag',
                $serviceId
            ));
        }
    }
}
