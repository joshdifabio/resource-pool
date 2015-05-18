<?php
namespace ResourcePool;

use React\Promise\PromiseInterface;
use React\Promise\Util;
use React\Promise\RejectedPromise;

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
     * @param callable $handler
     * @return PromiseInterface
     */
    public function to($handler)
    {
        return $this->then(
            function (Allocation $allocation) use ($handler) {
                try {
                    $result = Util::promiseFor(call_user_func($handler));
                    $result->then(array($allocation, 'releaseAll'), array($allocation, 'releaseAll'));
                } catch (\Exception $e) {
                    $result = new RejectedPromise($e);
                    $allocation->releaseAll();
                }

                return $result;
            }
        );
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
