# Resource Pool

[![Build Status](https://img.shields.io/travis/joshdifabio/resource-pool.svg?style=flat-square)](https://travis-ci.org/joshdifabio/resource-pool)
[![Coverage](https://img.shields.io/codecov/c/github/joshdifabio/resource-pool.svg?style=flat-square)](http://codecov.io/github/joshdifabio/resource-pool)
[![Code Quality](https://img.shields.io/scrutinizer/g/joshdifabio/resource-pool.svg?style=flat-square)](https://scrutinizer-ci.com/g/joshdifabio/resource-pool/)

Don't pwn your resources, pool them!

## Introduction

Resource pools allow you to regulate the concurrency level of your asynchronous PHP components and spare your CPU, Internet connection and other resources from excessive load.

## Basic usage

Consider an application which sends HTTP requests to a remote endpoint.

### How you shouldn't do it

```php
foreach (getLotsOfRequests() as $request) {
    sendRequest($request)->then(function ($response) {
        // the response came back!
    });
}
```

An implementation like this could easily send 100s or even 1000s of requests within a single second, causing huge load on the remote server as it tries to serve your requests. This is essentially a DOS attack, and will make sysadmins cry, who will then make you cry.

### How you should do it

Create a resource pool representing a fixed number of resources, for example five.

```php
$pool = new \ResourcePool\Pool(5);
```

Before sending a request, allocate a resource from the pool. `Pool::allocateOne()` returns a promise which resolves as soon as a resource becomes available.

```php
foreach (getLotsOfRequests() as $request) {
    $pool->allocateOne()->to('sendRequest', $request)->then(function ($response) {
        // the response came back!
    });
}
```

That's it! You did it! The above implementation will only spawn a maximum of five requests concurrently.

## Advanced usage

Advanced, eh? You should read this.

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
// returns an allocation of two resources, even if the pool is fully allocated
$allocation = $pool->allocate(2)->orBurst();
```

```php
// throws a \RuntimeException if the pool cannot allocate two resources
$allocation = $pool->allocate(2)->orFail();
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
