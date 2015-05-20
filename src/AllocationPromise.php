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
    protected $promise;
    protected $resolver;
    protected $result;

    public function __construct(PromiseInterface $promise, $resolver = null)
    {
        $this->promise = $promise;
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function then($fulfilledHandler = null, $errorHandler = null, $progressHandler = null)
    {
        return $this->promise->then($fulfilledHandler, $errorHandler, $progressHandler);
    }

    /**
     * Calls the specified handler when this promise is fulfilled.
     * 
     * If the handler returns a promise, 
     *
     * @param callable $handler
     * @return PromiseInterface
     */
    public function to($handler /*, $args… */)
    {
        $args = array_slice(func_get_args(), 1);

        return $this->then(
            function (Allocation $allocation) use ($handler, $args) {
                try {
                    $result = Util::promiseFor(call_user_func_array($handler, $args));
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
     * Tries to return the allocation now, synchronously
     * 
     * If the pool does not have sufficient resources available then an exception is thrown and this
     * promise is rejected
     *
     * @return Allocation
     * @throws \RuntimeException thrown if the allocation fails or has previously failed
     */
    public function orFail()
    {
        return $this->getResult(false);
    }
    
    protected function getResult($burst)
    {
        if (null === $this->result) {
            $this->result = $this->resolve($burst);
        }

        if ($this->result instanceof \Exception) {
            throw $this->result;
        }

        return $this->result;
    }
    
    private function resolve($burst)
    {
        $result = null;
        
        $this->promise->then(
            function ($allocation) use (&$result) {
                $result = $allocation;
            },
            function ($error) use (&$result) {
                $result = $error;
            }
        );

        if (null === $result) {
            call_user_func($this->resolver, $burst);
        }
        
        return $result ?: new \LogicException('The resolver did not resolve the promise');
    }
}
