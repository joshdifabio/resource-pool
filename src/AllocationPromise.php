<?php
namespace ResourcePool;

use React\Promise\PromiseInterface;

/**
 * @author Josh Di Fabio <joshdifabio@gmail.com>
 *
 * @api
 */
class AllocationPromise implements PromiseInterface
{
    private $promise;
    private $resolveFn;
    private $allocation;

    public function __construct(PromiseInterface $promise, $resolveFn = null)
    {
        $this->promise = $promise;
        $this->resolveFn = $resolveFn;
    }

    /**
     * {@inheritdoc}
     */
    public function then($fulfilledHandler = null, $errorHandler = null, $progressHandler = null)
    {
        return $this->promise->then($fulfilledHandler, $errorHandler, $progressHandler);
    }

    /**
     * @return Allocation
     */
    public function now()
    {
        if (null === $this->allocation) {
            $allocation = &$this->allocation;
            $this->promise->then(function ($_allocation) use (&$allocation) {
                $allocation = $_allocation;
            });
            
            if (null !== $this->resolveFn) {
                call_user_func($this->resolveFn);
            }
        }

        return $this->allocation;
    }
}
