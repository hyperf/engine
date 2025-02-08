<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace HyperfTest\Cases;

use ArrayObject;
use Hyperf\Engine\Channel;
use Hyperf\Engine\Contract\CoroutineInterface;
use Hyperf\Engine\Coroutine;
use Hyperf\Engine\Exception\CoroutineDestroyedException;
use Hyperf\Engine\Exception\RunningInNonCoroutineException;
use Throwable;

/**
 * @internal
 * @coversNothing
 */
class CoroutineTest extends AbstractTestCase
{
    public function testCoroutineCreate()
    {
        $this->runInCoroutine(function () {
            $coroutine = new Coroutine(function () {
                $this->assertTrue(true);
            });

            $coroutine->execute();

            $this->assertInstanceOf(CoroutineInterface::class, $coroutine);
            $this->assertIsInt($coroutine->getId());
        });

        $this->runInCoroutine(function () {
            $coroutine = Coroutine::create(function () {
                $this->assertTrue(true);
            });

            $this->assertInstanceOf(CoroutineInterface::class, $coroutine);
            $this->assertIsInt($coroutine->getId());
        });
    }

    public function testCoroutineContext()
    {
        $this->runInCoroutine(function () {
            $id = uniqid();
            $coroutine = Coroutine::create(function () use ($id) {
                $this->assertInstanceOf(ArrayObject::class, Coroutine::getContextFor());
                $this->assertFalse(isset(Coroutine::getContextFor()['name']));
                $this->assertSame(null, Coroutine::getContextFor()['name'] ?? null);
                Coroutine::getContextFor()['name'] = $id;
                $this->assertSame($id, Coroutine::getContextFor()['name']);
                usleep(1000);
            });

            $this->assertSame($id, Coroutine::getContextFor($coroutine->getId())['name']);

            usleep(1000);
            $this->assertNull(Coroutine::getContextFor($coroutine->getId()));
        });
    }

    public function testCoroutineId()
    {
        $this->assertSame(-1, Coroutine::id());
        $this->runInCoroutine(function () {
            $this->assertIsInt($id = Coroutine::id());
            $this->assertGreaterThan(0, $id);
        });
    }

    public function testCoroutinePid()
    {
        $this->runInCoroutine(function () {
            $pid = Coroutine::id();
            Coroutine::create(function () use ($pid) {
                $this->assertSame($pid, Coroutine::pid());
                $pid = Coroutine::id();
                $co = Coroutine::create(function () use ($pid) {
                    $this->assertSame($pid, Coroutine::pid(Coroutine::id()));
                    usleep(1000);
                });
                Coroutine::create(function () use ($pid) {
                    $this->assertSame($pid, Coroutine::pid());
                });
                $this->assertSame($pid, Coroutine::pid($co->getId()));
            });
        });
    }

    public function testCoroutinePidInNonCoroutineEnvironment()
    {
        $this->expectExceptionMessage('Non-Coroutine environment don\'t has parent coroutine id.');
        $this->expectException(RunningInNonCoroutineException::class);

        Coroutine::pid();
    }

    public function testCoroutinePidHasBeenDestroyed()
    {
        $this->runInCoroutine(function () {
            $co = Coroutine::create(function () {
            });

            try {
                Coroutine::pid($co->getId());
                $this->assertTrue(false);
            } catch (Throwable $exception) {
                $this->assertInstanceOf(CoroutineDestroyedException::class, $exception);
            }
        });
    }

    public function testCoroutineInTopCoroutine()
    {
        $this->runInCoroutine(function () {
            $this->assertSame(0, Coroutine::pid());
        });
    }

    public function testCoroutineDefer()
    {
        $this->runInCoroutine(function () {
            $channel = new Channel(2);
            Coroutine::create(function () use ($channel) {
                Coroutine::defer(function () use ($channel) {
                    $channel->push(2);
                });

                $channel->push(1);
            });

            $this->assertSame(1, $channel->pop());
            $this->assertSame(2, $channel->pop());
        });
    }

    public function testTheOrderForCoroutineDefer()
    {
        $this->runInCoroutine(function () {
            $channel = new Channel(3);
            Coroutine::create(function () use ($channel) {
                Coroutine::defer(function () use ($channel) {
                    $channel->push(2);
                });
                Coroutine::defer(function () use ($channel) {
                    $channel->push(3);
                });

                $channel->push(1);
            });

            $this->assertSame(1, $channel->pop());
            $this->assertSame(3, $channel->pop());
            $this->assertSame(2, $channel->pop());
        });
    }

    public function testCoroutineResumeById()
    {
        $this->runInCoroutine(function () {
            $channel = new Channel(10);
            Coroutine::create(function () use ($channel) {
                $channel->push(1);
                $co = Coroutine::create(function () use ($channel) {
                    $channel->push(2);
                    Coroutine::yield();
                    $channel->push(3);
                });
                $channel->push(4);
                $res = Coroutine::resumeById($co->getId());
                $channel->push(5);
            });

            $this->assertSame(1, $channel->pop());
            $this->assertSame(2, $channel->pop());
            $this->assertSame(4, $channel->pop());
            $this->assertSame(3, $channel->pop());
            $this->assertSame(5, $channel->pop());
        });
    }

    public function testCoroutineList()
    {
        $this->runInCoroutine(function () {
            $list = Coroutine::list();
            $this->assertIsIterable($list);
            $this->assertContains(Coroutine::id(), $list);
        });

        $this->runInCoroutine(function () {
            Coroutine::create(function () {
                sleep(1);
            });
            Coroutine::create(function () {
                sleep(1);
            });
            Coroutine::create(function () {
                sleep(1);
            });
            $this->assertEquals(4, iterator_count(Coroutine::list()));
        });
    }
}
