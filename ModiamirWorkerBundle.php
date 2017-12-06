<?php

namespace Modiamir\WorkerBundle;

use Modiamir\WorkerBundle\DependencyInjection\WorkerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ModiamirWorkerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new WorkerCompilerPass());
    }
}
