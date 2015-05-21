<?php
namespace ResourcePool;

/**
 * @author Josh Di Fabio <joshdifabio@gmail.com>
 *
 * @api
 */
class PartialAllocationPromise extends AllocationPromise
{
    /**
     * Returns the allocation now, synchronously
     * 
     * If the pool does not have sufficient resources available then the number of resources will
     * burst beyond the pool size in order to facilitate this allocation
     *
     * @return Allocation
     * @throws \RuntimeException thrown if the allocation has previously failed
     */
    public function force()
    {
        return $this->getResult(true);
    }
}
