<?php

namespace Modiamir\WorkerBundle\DependencyInjection;

use Modiamir\WorkerBundle\WorkerService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class WorkerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has('modiamir_worker.worker_service')) {
            return;
        }

        $definition = $container->findDefinition('modiamir_worker.worker_service');

        // find all service IDs with the app.mail_transport tag
        $taggedServices = $container->findTaggedServiceIds('modiamir_worker.worker');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addWorker', array(
                $id, new Reference($id)
            ));
        }
    }
}