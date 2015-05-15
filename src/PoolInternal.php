<?php
namespace ResourcePool;

use React\Promise\FulfilledPromise;
use React\Promise\Deferred;

/**
 * This class exists to keep the public interface of Pool clean. Once PHP 5.3 support is dropped,
 * this functionality will probably be moved to Pool in the form of private methods.
 *
 * @author Josh Di Fabio <joshdifabio@gmail.com>
 *
 * @internal
 */
class PoolInternal
{
    private $size;
    private $usage = 0;
    private $queue;

    public function __construct($size = null)
    {
        $this->size = $size;
        $this->queue = new \SplQueue;
    }

    public function whenAllocated($count)
    {
        if ($this->canAllocate($count)) {
            $allocation = $this->createAllocation($count);
            $promise = new FulfilledPromise($allocation);
            $resolver = null;
        } else {
            $deferred = new Deferred;
            $promise = $deferred->promise();
            $that = $this;
            $isResolved = false;
            $resolver = function () use (&$isResolved, $that, $count, $deferred) {
                $isResolved = true;
                $allocation = $that->createAllocation($count);
                $deferred->resolve($allocation);
            };
            $this->queue->enqueue(array($count, $deferred, &$isResolved));
        }

        return new AllocationPromise($promise, $resolver);
    }

    public function whenAllAllocated()
    {
        $count = null;

        if ($this->canAllocate($count)) {
            $allocation = $this->createAllocation($count);
            return new FulfilledPromise($allocation);
        }

        $deferred = new Deferred;
        $this->queue->enqueue(array($count, $deferred, false));

        return $deferred->promise();
    }

    public function setSize($size)
    {
        $this->size = $size;
        $this->processQueue();
    }

    public function getAvailability()
    {
        return max(0, $this->size - $this->usage);
    }

    public function getUsage()
    {
        return $this->usage;
    }

    private function processQueue()
    {
        foreach ($this->queue as $allocationInfo) {
            if (true === $allocationInfo[2]) {
                $this->queue->dequeue();
                continue;
            }
            
            if (!$this->canAllocate($allocationInfo[0])) {
                break;
            }

            $this->queue->dequeue();
            $allocation = $this->createAllocation($allocationInfo[0]);
            $allocationInfo[1]->resolve($allocation);
        }
    }

    public function decrementUsage($amount)
    {
        $this->usage -= $amount;
        $this->processQueue();
    }

    private function canAllocate($count = null)
    {
        if (null === $count) {
            return 0 === $this->usage && $this->size > 0;
        }

        return $count <= $this->getAvailability();
    }

    public function createAllocation($size)
    {
        if (null === $size) {
            $size = $this->size;
        }

        $this->usage += $size;

        return new Allocation(array($this, 'decrementUsage'), $size);
    }
}
