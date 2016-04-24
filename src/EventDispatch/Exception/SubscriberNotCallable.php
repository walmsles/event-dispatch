<?php

namespace EventDispatch\Exception;

/**
 * Class SubscriberNotCallable
 * @package EventDispatch\Exception
 */
class SubscriberNotCallable extends \Exception
{
    public function __construct()
    {
        parent::__construct('Subscriber Method must be callable');
    }
}