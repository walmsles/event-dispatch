<?php

namespace walmsles\EventDispatch;

use walmsles\EventDispatch\Exception\SubscriberNotCallable;

/**
 * Class Dispatcher
 *
 * Manages subscribers to Events and calls all listeners when Event is dispatched
 * Listener is any callable php structure as accepted by "call_user_func_array" method
 *
 */
class Dispatcher
{

    const QUEUE_NAME = '__queue';

    /**
     * List of Subscribers to Events.
     * Is multi-dimensional array: $eventList[$priority][] = subscribers
     * @var
     */
    private $eventList = [];

    /**
     * Cached Sorted array - Subscribers sorted by priority
     * @var array
     */
    private $sorted = [];


    /**
     * Subscribe to an Event with either a closure or callable structure
     *
     * @param     $eventName
     * @param     $callable
     * @param int $priority - highest number highest priority
     *
     * @return $this
     * @throws \EventDispatch\Exception\SubscriberNotCallable
     */
    public function subscribe($eventName, $callable, $priority = 0)
    {
        if (!is_callable($callable)) {
            throw new SubscriberNotCallable();
        }

        $this->eventList[$eventName][$priority][] = $callable;

        /*
         * Clear cached sorted array
         */
        unset($this->sorted[$eventName]);

        return $this;
    }


    /**
     * Remove Event from Event List
     * @param $eventName
     *
     * @return $this
     */
    public function removeEvent($eventName)
    {
        unset($this->eventList[$eventName]);
        unset($this->sorted[$eventName]);

        return $this;
    }
    
    /**
     * Sort Subscribers in reverse priority order - higher priority first
     *
     * @param $eventName
     *
     * @return mixed
     */
    public function sortSubscribers($eventName)
    {
        $this->sorted[$eventName] = array();
        if (isset($this->eventList[$eventName])) {
            krsort($this->eventList[$eventName]);
            $this->sorted[$eventName] = call_user_func_array('array_merge', $this->eventList[$eventName]);
        }

        return $this->sorted[$eventName];
    }

    /**
     * Return list of callabel subscribers in priority order (highest priority first)
     *
     * @param $eventName
     *
     * @return mixed
     */
    public function getSubscribers($eventName)
    {
        $subscribers = [];

        if (empty($this->sorted[$eventName])) {
            $this->sortSubscribers($eventName);
        }

        return $this->sorted[$eventName];
    }

    /**
     *
     * Dispatch an event.
     *
     * @param       $eventName
     * @param array $payload
     * @param bool  $halt - halt on first subscriber returning a result
     *
     * @return array|null
     */
    public function dispatch($eventName, $payload = array(), $halt = false)
    {

        $responses = array();

        // Make sure payload is an array for call_user_func_arrray below
        if (!is_array($payload)) {
            $payload = array($payload);
        }

        foreach ($this->getSubscribers($eventName) as $listener) {
            $response = call_user_func_array($listener, $payload);
            /*
             * return is list of responses.  If halt is true then first response only
             * will be returned
             */
            if (!is_null($response) and $halt) {
                return $response;
            }

            // if false response returned then stop processing list
            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        /*
         *  Only return response if not halting on first call - if gpt here when halt == true
         *  then no events processed so return null.
         */

        return $halt ? null : $responses;
    }

    /**
     * Defer dispatching of an event or events until flush is called.
     *
     * @param       $eventName
     * @param array $payload
     *
     * @throws \EventDispatch\Exception\SubscriberNotCallable
     */
    public function queue($eventName, $payload = array())
    {
        $me = $this;

        $this->subscribe($eventName . self::QUEUE_NAME, function () use ($me, $eventName, $payload) {
            return $me->dispatch($eventName, $payload);
        });
    }

    /**
     * Flush Event Queue
     *
     * @param $eventName
     */
    public function flush($eventName)
    {
        $result = $this->dispatch($eventName . self::QUEUE_NAME);
        // If result is an array then need to merge or will end up with array of arrays.
        if (is_array($result)) {
            $result = call_user_func_array('array_merge', $result);
        }

        return $result;
    }

}
