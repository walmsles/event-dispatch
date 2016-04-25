# Event Dispatch

Simple publish/subscribe event Dispatcher.
This project is an exploration of TDD - I should be doing this more and thought why not try it out making something midly useful that I need in other projects :-)

## What does this package provide
This package provides a clean, de-coupled Publish/Subscribe interface for Dispatching custom defined Events.
Events are completely customisable including data passed into the Events.  Events are dispatched via PHP `call_user_func_array()` so to pass multiple parameters pass an array payload as an array(). 

## Usage

```
use EventDispatch\Dispatcher;

$dispatcher = new Dispatcher();

$dispatcher->subscribe('myEvent', function($myData1, $myData2) {
    // so somehting with $myData
    
    return $myData1 . ' ' . $myData2;
});

$dispatch->dispatch('myEvent', array('event data', 'event data 2');
```
## installation
```
composer require walmsles/event-dispatch
```

If you don't have composer - get it!  http://getcomposer.org

## License - MIT
```
The MIT License (MIT)

Copyright (c) 2016 Michael Walmsley

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
