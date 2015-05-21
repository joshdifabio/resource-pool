<?php
namespace ResourcePool;

use React\Promise\PromiseInterface;

/**
 * @author Josh Di Fabio <joshdifabio@gmail.com>
 *
 * @api
 */
class Pool
{
    private $internal;

    public function __construct($size = null)
    {
        $this->internal = new PoolInternal($size);
    }

    /**
     * Allocates a single resource when one becomes available
     *
     * @return PartialAllocationPromise
     */
    public function allocateOne()
    {
        return $this->internal->allocate(1);
    }

    /**
     * Allocates the specified number of resources when they become available
     *
     * @param int $count
     * @return PartialAllocationPromise
     */
    public function allocate($count)
    {
        return $this->internal->allocate($count);
    }

    /**
     * Allocates all of the pool's resources when they become available
     *
     * @return AllocationPromise
     */
    public function allocateAll()
    {
        return $this->internal->allocateAll();
    }

    /**
     * Sets the number of resources in the pool
     *
     * @param int $size
     */
    public function setSize($size)
    {
        $this->internal->setSize($size);
    }

    /**
     * Returns the number of resources which are not currently allocated
     *
     * @return int
     */
    public function getAvailability()
    {
        return $this->internal->getAvailability();
    }

    /**
     * Returns the number of resources which are currently allocated
     *
     * @return int
     */
    public function getUsage()
    {
        return $this->internal->getUsage();
    }

    /**
     * @param callable|null $fulfilledHandler
     * @return PromiseInterface
     */
    public function whenNextIdle($fulfilledHandler = null)
    {
        return $this->internal->whenNextIdle($fulfilledHandler);
    }
}
