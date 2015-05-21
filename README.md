# Resource Pool

[![Build Status](https://img.shields.io/travis/joshdifabio/resource-pool.svg?style=flat-square)](https://travis-ci.org/joshdifabio/resource-pool)
[![Coverage](https://img.shields.io/codecov/c/github/joshdifabio/resource-pool.svg?style=flat-square)](http://codecov.io/github/joshdifabio/resource-pool)
[![Code Quality](https://img.shields.io/scrutinizer/g/joshdifabio/resource-pool.svg?style=flat-square)](https://scrutinizer-ci.com/g/joshdifabio/resource-pool/)

Don't pwn your resources, pool them!

## Introduction

Resource pools allow you to regulate the concurrency level of your asynchronous PHP components and spare your servers from excessive load. You'll find them particularly useful if your application sends HTTP requests or spawns child processes using something like [ReactPHP](https://github.com/reactphp/react).

## Basic usage

If you aren't familiar with [Promises](https://github.com/reactphp/promise), this section isn't going to make a lot of sense.

Consider an application which sends HTTP requests to a remote endpoint asynchronously.

```php
function sendRequest() {
    // this would probably be something like Guzzle or React/HttpClient
}
```

### How you shouldn't do it

```php
foreach (getThousandsOfRequests() as $request) {
    sendRequest($request)->then(function ($response) {
        // the response came back!
    });
}

// thousands of requests have been initiated concurrently
```

An implementation like this could easily send 100s or even 1000s of requests within a single second, causing huge load on the remote server as it tries to serve your requests. This is essentially a DOS attack, and will make sysadmins cry, who will then make you cry.

### How you should do it

Create a resource pool representing a fixed number of resources, for example five.

```php
$pool = new \ResourcePool\Pool(5);
```

Before sending a request, allocate a resource from the pool. `Pool::allocateOne()` returns an `AllocationPromise` which resolves as soon as a resource becomes available.

```php
foreach (getThousandsOfRequests() as $request) {
    // to() will invoke a function and then release the allocated resources once it's done
    $pool->allocateOne()->to('sendRequest', $request)->then(function ($response) {
        // the response came back!
    });
}

// five requests are running; the rest are queued and will be sent as others complete
```

That's it! You did it! This implementation will spawn a maximum of five concurrent requests.

## Advanced usage

Advanced user? Read on.

### Allocate multiple resources

```php
$pool->allocate(5)->to(function () {
    // this task requires five resources to run!
});
```

### Allocate all the resources

```php
$pool->allocateAll()->to(function () {
    // this requires all the resources!
});
```

### Release allocations manually

```php
// call then() instead of to() to work with the allocation directly
$pool->allocate(2)->then(function ($allocation) {
    // two things which need to run at the same time
    firstThing()->done([$allocation, 'releaseOne']);
    secondThing()->done([$allocation, 'releaseOne']);
});
```

### Force an allocation to resolve immediately

```php
try {
    $allocation = $pool->allocate(2)->now();
} catch (\RuntimeException $e) {
    // throws a \RuntimeException if the pool cannot allocate two resources
}
```

You can also choose to burst beyond the size of the pool for a specific allocation.

```php
$pool = new \ResourcePool\Pool(1);
$allocation = $pool->allocate(2)->force();
$pool->getUsage(); // 2
$pool->getAvailability(); // 0
$allocation->releaseAll();
$pool->getAvailability(); // 1
```

### Find out when a pool is idle

```php
$pool->whenNextIdle(function () {
    // the pool is idle!
});
```

### Change the size of a pool

```php
$pool->setSize(100);
```

### Find out how many resources are allocated

```php
$pool->getUsage();
```

### Find out how many resources are available

```php
$pool->getAvailability();
```

## Installation

Install Resource Pool using [composer](https://getcomposer.org/).

```
composer require joshdifabio/resource-pool
```

## License

Resource Pool is released under the [MIT](https://github.com/joshdifabio/resource-pool/blob/master/LICENSE) license.
