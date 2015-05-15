<?php
namespace ResourcePool;

/**
 * @author Josh Di Fabio <joshdifabio@gmail.com>
 *
 * @api
 */
class Allocation
{
    private $releaseFn;
    private $size;
    
    public function __construct($releaseFn, $size)
    {
        $this->releaseFn = $releaseFn;
        $this->size = $size;
    }

    /**
     * Releases one of the resources which are still part of this allocation
     */
    public function releaseOne()
    {
        $this->release(1);
    }

    /**
     * Releases any resources which are still part of this allocation
     */
    public function releaseAll()
    {
        if ($this->size) {
            $this->release($this->size);
        }
    }

    /**
     * Releases a specified number of the resources which are still part of this allocation
     *
     * @param int $count the number of resources to release
     */
    public function release($count)
    {
        $count = min($count, $this->size);

        if (!$count) {
            return;
        }

        $this->size -= $count;
        call_user_func($this->releaseFn, $count);
    }
}
