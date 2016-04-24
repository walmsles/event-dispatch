<?php

use EventDispatch\Dispatcher;
use EventDispatch\Exception\SubscriberNotCallable;

require __DIR__."/../testSetup.php";


/**
 * Function which is callable - to test Dispatcher->subscribe()
 * @param $data
 *
 * @return mixed
 */
function callableSubscriberFunction($data)
{
    return $data;
}

/**
 * Class DispatcherTest
 */
class DispatcherTest extends PHPUnit_Framework_TestCase
{

    /**
     *
     * @return \EventDispatch\Dispatcher
     */
    public function testCanCreateDispatcher()
    {
        $dispatcher = new Dispatcher();

        return $dispatcher;
    }

    /**
     *
     * Test that I can subscriber to a simple event (i.e. add an event)
     * Events should accept a name and a closure or other callable parameter
     *
     * @depends testCanCreateDispatcher
     *
     */
    public function testCanSubscribeClosure(Dispatcher $dispatcher)
    {
        $dispatcher->subscribe('closureEvent', function($data) {
            // simple event - returns passed in $data
            return $data;
        });

        $dispatcher->subscribe('functionEvent', 'callableSubscriberFunction');
    }

    /**
     * Test Can retrieve subscribers - expect an array of subscribers
     * @param \EventDispatch\Dispatcher $dispatcher
     * @depends testCanCreateDispatcher
     *
     */
    public function testCanGetListOfSubscribers(Dispatcher $dispatcher)
    {
        $subscribers = $dispatcher->getSubscribers('closureEvent');
        $this->assertCount(1, $subscribers);
    }

    /**
     * @param \EventDispatch\Dispatcher $dispatcher
     * @depends testCanCreateDispatcher
     * @expectedException \EventDispatch\Exception\SubscriberNotCallable
     */
    public function testCanSubscribefunction(Dispatcher $dispatcher)
    {
        $dispatcher->subscribe('invalidEvent', 'Non Callable');
    }

    protected $classMethod;

    /**
     * @depends testCanCreateDispatcher
     * @param \EventDispatch\Dispatcher $dispatcher
     */
    public function testCanSubscribeClassMethod(Dispatcher $dispatcher)
    {
        $this->classMethod = array($this, 'callableClassMethod');
        $dispatcher->subscribe('ClassMethod', $this->classMethod);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function callableClassMethod($data)
    {
        return $data;
    }

    /**
     * @depends testCanCreateDispatcher
     * @param \EventDispatch\Dispatcher $dispatcher
     */
    public function testCanFireClassMethodEvent(Dispatcher $dispatcher)
    {
        $expectedData = 'Class Method Event';
        $result = $dispatcher->dispatch('ClassMethod', $expectedData, true);
        $this->assertEquals($expectedData, $result);

        // if fire ALL events expect array response
        $result = $dispatcher->dispatch('ClassMethod', $expectedData);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
    }

    /**
     * Dispatch Event and check get correct resposne from Function Event
     * Function event should pass data passed into the event
     * @param \EventDispatch\Dispatcher $dispatcher
     * @depends testCanCreateDispatcher
     */
    public function testCanFireFunctionEvent(Dispatcher $dispatcher)
    {
        $expectedData = 'Function data';
        $result = $dispatcher->dispatch('functionEvent', $expectedData, true);
        $this->assertEquals($expectedData, $result);

        // if fire ALL events expect array response
        $result = $dispatcher->dispatch('functionEvent', $expectedData);
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($expectedData, $result[0]);
    }

    protected $queueData = 'Queue Data';

    /**
     * test placing an event on a Q for deferred dispatching
     *
     * @param \EventDispatch\Dispatcher $dispatcher
     * @depends testCanCreateDispatcher
     */
    public function testEventEnqueue(Dispatcher $dispatcher)
    {
        $dispatcher->queue('closureEvent', $this->queueData);

        $subscribers = $dispatcher->getSubscribers('closureEvent'.Dispatcher::QUEUE_NAME);
        $this->assertCount(1, $subscribers);
    }

    /**
     * Fire Event and check get correct response from Closure event
     * Closure EVent returns data passed into the event fire
     * @depends testCanCreateDispatcher
     */
    public function testCanFireClosureEvent(Dispatcher $dispatcher)
    {
        $expectedData = 'Closure Data';

        // If fire one event expect string result
        $result = $dispatcher->dispatch('closureEvent', $expectedData, true);
        $this->assertEquals($expectedData, $result);

        // if fire ALL events expect array response
        $result = $dispatcher->dispatch('closureEvent', $expectedData);

        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        $this->assertEquals($expectedData, $result[0]);
    }

    /**
     * @depends testCanCreateDispatcher
     * @param \EventDispatch\Dispatcher $dispatcher
     */
    public function testQueueDispatch(Dispatcher $dispatcher)
    {
        $result = $dispatcher->flush('closureEvent');
        $this->assertTrue(is_array($result));
        $this->assertEquals(1, count($result));
        // check get right result form Queued event
        $this->assertEquals($this->queueData, $result[0]);
    }

    /**
     *
     * @param \EventDispatch\Dispatcher $dispatcher
     * @depends testCanCreateDispatcher
     */
    public function testRemoveEvent(Dispatcher $dispatcher)
    {
        $dispatcher->removeEvent('ClassMethod');
        $subscribers = $dispatcher->getSubscribers('ClassMethod');
        $this->assertEquals(0, count($subscribers));

        $result = $dispatcher->dispatch('ClassMethod', 'data');
        $this->assertEquals(0, count($result));
    }

    /**
     * @param \EventDispatch\Dispatcher $dispatch
     * @depends testCanCreateDispatcher
     */
    public function testDispatchWithHalt(Dispatcher $dispatcher)
    {
        $dispatcher->subscribe('multi', function($data) {
           return $data . " 1";
        });
        $dispatcher->subscribe('multi', function($data) {
            return $data . " 2";
        });

        // Check get string value back from 1 event firing
        // Multiple event fire will return an array of results.
        $result = $dispatcher->dispatch('multi', "value", true);
        $this->assertEquals("value 1", $result);

        // Check both event listeners fire
        $result = $dispatcher->dispatch('multi', "data");
        $this->assertEquals(2, count($result));
        $this->assertEquals('data 1', $result[0]);
        $this->assertEquals('data 2', $result[1]);
    }

    /**
     * @param \EventDispatch\Dispatcher $dispatcher
     * @depends testCanCreateDispatcher
     */
    public function testFailDispatch(Dispatcher $dispatcher) 
    {
        $dispatcher->subscribe('failTest', function($data) {
            return false;
        });
        $dispatcher->subscribe('failTest', function($data) {
            return $data . " 2";
        });

        // Check no result set returned since firt closure returns false
        $result = $dispatcher->dispatch('failTest', "data");
        $this->assertEquals(0, count($result));

        $dispatcher->removeEvent('failTest');

        // Setup new test - second closure returns fail so should get 1 result
        $dispatcher->subscribe('failTest', function($data) {
            return "$data 1";
        });
        $dispatcher->subscribe('failTest', function($data) {
            return false;
        });

        // Check no result set returned since firt closure returns false
        $result = $dispatcher->dispatch('failTest', "data");

        $this->assertEquals(1, count($result));
        $this->assertEquals('data 1', $result[0]);
    }

}