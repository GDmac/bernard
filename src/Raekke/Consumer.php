<?php

namespace Raekke;

use Raekke\Consumer\Job;
use Raekke\Queue\QueueInterface;
use Raekke\ServiceResolver\ServiceResolverInterface;

declare(ticks=1);

/**
 * @package Consumer
 */
class Consumer implements ConsumerInterface
{
    protected $failed;
    protected $services;
    protected $shutdown = false;
    protected $defaults = array(
        'max_retries' => 5,
        'max_runtime' => PHP_INT_MAX,
    );

    /**
     * @param ServiceResolverInterface $services
     * @param Queue                    $failed   Failed messages will be enqueued on this.
     */
    public function __construct(
        ServiceResolverInterface $services,
        QueueInterface $failed = null
    ) {
        $this->failed = $failed;
        $this->services = $services;
    }

    /**
     * {@inheritDoc}
     */
    public function consume(QueueInterface $queue, array $options = array())
    {
        $options = array_merge($this->defaults, array_filter($options));
        $runtime = microtime(true) + $options['max_runtime'];

        // register with monitoring object class thing

        pcntl_signal(SIGTERM, array($this, 'shutdown'), true);
        pcntl_signal(SIGINT, array($this, 'shutdown'), true);

        while (microtime(true) < $runtime) {
            if ($this->shutdown) {
                break;
            }

            if (null === $envelope = $queue->dequeue()) {
                continue;
            }

            try {
                $message = $envelope->getMessage();
                $service = $this->services->resolve($message);

                $job = new Job($service, $message);
                $job();
            } catch (\Exception $e) {
                if ($envelope->getRetries() < $options['max_retries']) {
                    $envelope->incrementRetries();
                    $queue->enqueue($envelope);

                    continue;
                }

                if (!$this->failed) {
                    continue;
                }

                $this->failed->enqueue($envelope);
            }
        }

        // Unregister with consumer monitoring thing
    }

    public function shutdown()
    {
        $this->shutdown = true;
    }
}
