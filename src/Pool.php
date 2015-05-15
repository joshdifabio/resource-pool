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
     * Allocates the specified number of resources when they become available
     *
     * @param int $count
     * @return AllocationPromise
     */
    public function allocate($count = 1)
    {
        return $this->internal->whenAllocated($count);
    }

    /**
     * Allocates all of the pool's resources when they become available
     *
     * @param int $resources
     * @return PromiseInterface
     */
    public function allocateAll()
    {
        return $this->internal->whenAllAllocated();
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
     * @return int|null null is returned if the pool has unlimited size
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
}
