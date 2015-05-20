<?php
namespace ResourcePool;

use React\Promise\Deferred;

/**
 * @author Josh Di Fabio <joshdifabio@gmail.com>
 */
class PoolTest extends \PHPUnit_Framework_TestCase
{
    public function testAllocate()
    {
        $pool = new Pool(1);

        $this->assertEquals(0, $pool->getUsage());

        $firstAllocation = null;

        $pool->allocateOne()->then(function ($allocation) use (&$firstAllocation) {
            $firstAllocation = $allocation;
        });
        $this->assertNotNull($firstAllocation);
        $this->assertEquals(1, $pool->getUsage());

        $secondAllocation = null;

        $pool->allocateOne()->then(function ($allocation) use (&$secondAllocation) {
            $secondAllocation = $allocation;
        });
        $this->assertNull($secondAllocation);
        $this->assertEquals(1, $pool->getUsage());

        $thirdAllocation = $pool->allocateOne()->orBurst();
        $this->assertNull($secondAllocation);
        $this->assertEquals(2, $pool->getUsage());

        for ($i = 0; $i < 2; $i++) {
            $thirdAllocation->releaseAll();
            $this->assertNull($secondAllocation);
            $this->assertEquals(1, $pool->getUsage());
        }

        $firstAllocation->releaseAll();
        $this->assertNotNull($secondAllocation);
        $this->assertEquals(1, $pool->getUsage());

        $secondAllocation->releaseAll();
        $this->assertEquals(0, $pool->getUsage());
    }

    public function testSizeIncrease()
    {
        $pool = new Pool(1);

        $this->assertEquals(0, $pool->getUsage());

        $firstAllocation = null;

        $pool->allocateOne()->then(function ($allocation) use (&$firstAllocation) {
            $firstAllocation = $allocation;
        });
        $this->assertNotNull($firstAllocation);
        $this->assertEquals(1, $pool->getUsage());

        $secondAllocation = null;

        $pool->allocateOne()->then(function ($allocation) use (&$secondAllocation) {
            $secondAllocation = $allocation;
        });
        $this->assertNull($secondAllocation);
        $this->assertEquals(1, $pool->getUsage());

        $pool->setSize(1);
        $this->assertNull($secondAllocation);
        $this->assertEquals(1, $pool->getUsage());

        $pool->setSize(0);
        $this->assertNull($secondAllocation);
        $this->assertEquals(1, $pool->getUsage());

        $pool->setSize(2);
        $this->assertNotNull($secondAllocation);
        $this->assertEquals(2, $pool->getUsage());
    }

    public function testAllocateAll()
    {
        $pool = new Pool(2);

        $this->assertEquals(0, $pool->getUsage());

        $firstAllocation = null;

        $pool->allocateOne()->then(function ($allocation) use (&$firstAllocation) {
            $firstAllocation = $allocation;
        });
        $this->assertNotNull($firstAllocation);
        $this->assertEquals(1, $pool->getUsage());

        $secondAllocation = null;

        $pool->allocateOne()->then(function ($allocation) use (&$secondAllocation) {
            $secondAllocation = $allocation;
        });
        $this->assertNotNull($secondAllocation);
        $this->assertEquals(2, $pool->getUsage());

        $fullAllocation = null;

        $pool->allocateAll()->then(function ($allocation) use (&$fullAllocation) {
            $fullAllocation = $allocation;
        });
        $this->assertNull($fullAllocation);
        $this->assertEquals(2, $pool->getUsage());

        $pool->setSize(3);

        $firstAllocation->releaseAll();
        $this->assertNull($fullAllocation);
        $this->assertEquals(1, $pool->getUsage());

        $secondAllocation->releaseAll();
        $this->assertNotNull($fullAllocation);
        $this->assertEquals(3, $pool->getUsage());
    }

    public function testAllocateTo()
    {
        $pool = new Pool(2);
        $deferred = new Deferred;
        $resultPromise = $pool->allocate(2)->to(array($deferred, 'promise'));
        $this->assertEquals(2, $pool->getUsage());
        $deferred->resolve('Hello!');
        $this->assertEquals(0, $pool->getUsage());
        $result = null;
        $resultPromise->then(function ($_result) use (&$result) {
            $result = $_result;
        });
        $this->assertSame('Hello!', $result);
    }

    public function testAllocateToSyncCallback()
    {
        $pool = new Pool(2);
        $result = null;
        $resultPromise = $pool->allocate(2)->to(function () {
            return 'Hello!';
        });
        $resultPromise->then(function ($_result) use (&$result) {
            $result = $_result;
        });
        $this->assertEquals(0, $pool->getUsage());
        $this->assertSame('Hello!', $result);
    }

    public function testAllocateToWithParam()
    {
        $pool = new Pool(1);
        $deferred = new Deferred;
        $resultPromise = $pool->allocate(1)->to(array($deferred, 'resolve'), 'Hello, world!');
        $result = null;
        $resultPromise->then(function ($_result) use (&$result) {
            $result = $_result;
        });
        $this->assertSame('Hello, world!', $result);
    }

    public function testAllocateToWithParams()
    {
        $pool = new Pool(1);
        $resultPromise = $pool->allocate(1)->to('implode', ',', array('Hello', ' world!'));
        $result = null;
        $resultPromise->then(function ($_result) use (&$result) {
            $result = $_result;
        });
        $this->assertSame('Hello, world!', $result);
    }

    public function testWhenNextIdle()
    {
        $pool = new Pool(2);

        $isIdle1 = false;
        $pool->whenNextIdle(function () use (&$isIdle1) {
            $isIdle1 = true;
        });
        $this->assertTrue($isIdle1);

        $isIdle2 = false;
        $pool->whenNextIdle()->then(function () use (&$isIdle2) {
            $isIdle2 = true;
        });
        $this->assertTrue($isIdle2);

        $deferred = new Deferred;
        $isIdle3 = false;
        $pool->allocate(1)->to(array($deferred, 'promise'));
        $pool->whenNextIdle(function () use (&$isIdle3) {
            $isIdle3 = true;
        });
        $this->assertFalse($isIdle3);
        $deferred->resolve();
        $this->assertTrue($isIdle3);
    }

    public function testAllocationFailureCleanup()
    {
        $pool = new Pool(0);

        $allocationPromise = $pool->allocateOne();

        $isIdle1 = false;
        $pool->whenNextIdle(function () use (&$isIdle1) {
            $isIdle1 = true;
        });
        $this->assertFalse($isIdle1);

        $e1 = null;
        try {
            $allocationPromise->orFail();
        } catch (\Exception $e1) {
            
        }
        $this->assertNotNull($e1);

        $e2 = null;
        try {
            $allocationPromise->orBurst();
        } catch (\Exception $e2) {
            
        }
        $this->assertNotNull($e2);

        $isIdle2 = false;
        $pool->whenNextIdle(function () use (&$isIdle2) {
            $isIdle2 = true;
        });
        $this->assertTrue($isIdle2);
    }
}
