# Resource Pool

[![Build Status](https://img.shields.io/travis/joshdifabio/resource-pool.svg?style=flat-square)](https://travis-ci.org/joshdifabio/resource-pool)
[![Coverage](https://img.shields.io/codecov/c/github/joshdifabio/resource-pool.svg?style=flat-square)](http://codecov.io/github/joshdifabio/resource-pool)
[![Code Quality](https://img.shields.io/scrutinizer/g/joshdifabio/resource-pool.svg?style=flat-square)](https://scrutinizer-ci.com/g/joshdifabio/resource-pool/)

## Introduction

Resource pools allow you to regulate the concurrency level of your asynchronous PHP components and spare your CPU, Internet connection and other resources from excessive load.

## Usage

Consider an application which executes commands concurrently using child processes.

```php
$resultPromises = [];

foreach (getLotsOfCommands() as $command) {
    // start a new process asynchronously
    $resultPromises[] = runProcessAsync($command);
}

\React\Promise\all($resultPromises)->then(function () {
    echo "We executed all the commands at once and now they've finished!";
});
```

This is a simple example of a common scenario in async PHP applications: unregulated usage of a limited resource; in this case, CPU cores.

Creating 100s or even 1000s of concurrent child processes or remote connections is a potential problem. This is where resource pools come in handy.

```php
$pool = new \ResourcePool\Pool(10);
$resultPromises = [];

foreach (getLotsOfCommands() as $command) {
    $resultPromises[] = $pool->allocate(1)->then(
        function ($allocation) use ($command) {
            $resultPromise = runProcessAsync($command);
            $resultPromise->then([$allocation, 'releaseAll']);
            return $resultPromise;
        }
    );
}

\React\Promise\all($resultPromises)->then(function () {
    echo "We executed all the commands with a max concurrency of 10 and now they've finished!";
});
```

## Installation

Install Resource Pool using [composer](https://getcomposer.org/).

```
composer require joshdifabio/resource-pool
```

## License

Resource Pool is released under the [MIT](https://github.com/joshdifabio/resource-pool/blob/master/LICENSE) license.
