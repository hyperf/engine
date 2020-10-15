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

use Hyperf\Engine\Contract\CoroutineInterface;
use Hyperf\Engine\Coroutine;
use Hyperf\Engine\Exception\CoroutineDestroyedException;
use Hyperf\Engine\Exception\RunningInNonCoroutineException;

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
                $this->assertInstanceOf(\ArrayObject::class, Coroutine::getContextFor());
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
            } catch (\Throwable $exception) {
                $this->assertInstanceOf(CoroutineDestroyedException::class, $exception);
            }
        });
    }
}
