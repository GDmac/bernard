<?php

namespace Bernard\Router;

use Bernard\Envelope;
use Bernard\Exception\ReceiverNotFoundException;

/**
 * Routes a Envelope to a Receiver by using the name of the Envelope.
 *
 * @package Bernard
 */
abstract class AbstractRouter implements \Bernard\Router
{
    protected $receivers = array();

    /**
     * @param array $receivers
     */
    public function __construct(array $receivers = array())
    {
        foreach ($receivers as $name => $receiver) {
            $this->add($name, $receiver);
        }
    }

    /**
     * @param string $name
     * @param mixed  $receiver
     */
    public function add($name, $receiver)
    {
        if (!$this->accepts($receiver)) {
            throw new \InvalidArgumentException('Given "$receiver" is not supported.');
        }

        $this->receivers[$name] = $receiver;
    }

    /**
     * {@inheritDoc}
     */
    public function map(Envelope $envelope)
    {
        if (!isset($this->receivers[$name = $envelope->getName()])) {
            throw new ReceiverNotFoundException();
        }

        if (!$receiver = $this->get($name)) {
            throw new ReceiverNotFoundException();
        }

        if (is_callable($receiver = $this->get($name))) {
            return $receiver;
        }

        return array($receiver, lcfirst($envelope->getName()));
    }

    /**
     * @param  mixed   $receiver
     * @return boolean
     */
    abstract protected function accepts($receiver);

    /**
     * @param  string $name
     * @return mixed
     */
    abstract protected function get($name);
}