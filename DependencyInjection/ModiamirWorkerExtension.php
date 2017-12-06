<?php

namespace Modiamir\WorkerBundle\DependencyInjection;

use Modiamir\WorkerBundle\WorkerInterface;
use Modiamir\WorkerBundle\WorkerService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ModiamirWorkerExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container
            ->registerForAutoconfiguration(WorkerInterface::class)
            ->addTag('modiamir_worker.worker');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('modiamir_worker.queue_mode', $config['queue_mode']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');


    }

    /**
     * Allow an extension to prepend the extension configurations.
     */
    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $modiamirWorkerConfig = $this->processConfiguration(new Configuration(), $configs);

        // get all bundles
        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['OldSoundRabbitMqBundle'])) {

            $queueName = $modiamirWorkerConfig['queue_name'];

            if ($modiamirWorkerConfig['support_delay']) {
                $exchangeOptions = [
                    "name" => $queueName,
                    "type" => "x-delayed-message",
                    "arguments" => [ "x-delayed-type" => ["S", "direct"]]
                ];
            } else {
                $exchangeOptions = [
                    "name" => $queueName,
                    "type" => "direct"
                ];
            }

            $connectionName = $modiamirWorkerConfig['connection_name'];

            $config = [
                'producers' => [
                    'modiamir_worker_service' => [
                        'connection' => $connectionName,
                        'exchange_options' => $exchangeOptions,
                        "service_alias" => "modiamir_worker_service",
                    ]
                ],
                'consumers' => [
                    'modiamir_worker_service' => [
                        'connection' => $connectionName,
                        'exchange_options' => $exchangeOptions,
                        "queue_options" => ["name" => $queueName],
                        "callback" => 'modiamir_worker.worker_service',
                    ]
                ]
            ];

            $container->prependExtensionConfig('old_sound_rabbit_mq', $config);
        }
    }
}
