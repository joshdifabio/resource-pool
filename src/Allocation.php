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
     * Releases a single resource from this allocation
     */
    public function releaseOne()
    {
        $this->release(1);
    }

    /**
     * Releases all resources from this allocation
     */
    public function releaseAll()
    {
        if ($this->size) {
            $this->release($this->size);
        }
    }

    /**
     * Releases the specified number of resources from this allocation
     *
     * @param int $count
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
