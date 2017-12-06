<?php

namespace Modiamir\WorkerBundle;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WorkerService implements ConsumerInterface
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var string
     */
    private $queueMode;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array|WorkerInterface[]
     */
    private $workers;

    public function __construct(ProducerInterface $producer, LoggerInterface $logger, $queueMode)
    {
        /** @var Producer $producer */
        $this->producer = $producer;
        $this->queueMode = $queueMode;
        $this->logger = $logger;
    }

    public function addWorker($id, WorkerInterface $worker)
    {
        $this->workers[$id] = $worker;
    }

    public function sync($id, array $data) {
        try {
            $this->workers[$id]->execute($data);
        } catch (\Throwable $exception) {
            $this->logger->error('Worker failed', ['id' => $id, 'data' => $data]);
            $this->logger->error($exception->getMessage(), [$exception->getTrace()]);
        }
    }

    public function async($id, array $data, int $delay = 0) {
        $message = serialize(['id' => $id, 'data' => $data]);

        $this->producer->publish($message, '', [], ['x-delay' => $delay]);
    }

    public function publish($id, array $data, int $delay = 0) {
        if ($this->queueMode == 'async') {
            $this->async($id, $data, $delay);
        } else {
            $this->sync($id, $data);
        }
    }

    /**
     * @param AMQPMessage $msg The message
     * @return mixed false to reject and requeue, any other value to acknowledge
     * @throws \Exception
     */
    public function execute(AMQPMessage $msg)
    {
        $message = unserialize($msg->getBody());

        try {
            $this->sync($message['id'], $message['data']);
        } catch (\Throwable $exception) {
            $this->logger->error('Queue message process failed', [$message]);
            $this->logger->error($exception->getMessage(), [$exception->getTrace()]);
        }

        return true;
    }
}