<?php

namespace Raekke\ServiceResolver;

use Pimple;
use Raekke\Message\MessageInterface;

/**
 * @package Raekke
 */
class PimpleAwareServiceResolver implements ServiceResolverInterface
{
    protected $services = array();
    protected $container;

    /**
     * @param Pimple $container
     */
    public function __construct(Pimple $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function register($name, $service)
    {
        $this->services[$name] = $service;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(MessageInterface $message)
    {
        if (!isset($this->services[$message->getName()])) {
            throw new \InvalidArgumentException('No service registered for message "' . $message->getName() . '".');
        }

        return $this->container[$this->services[$message->getName()]];
    }
}