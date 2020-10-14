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
    }

    public function testCoroutineRun()
    {
        $this->runInCoroutine(function () {
            $coroutine = Coroutine::run(function () {
                $this->assertTrue(true);
            });

            $this->assertInstanceOf(CoroutineInterface::class, $coroutine);
            $this->assertIsInt($coroutine->getId());
        });
    }
}
