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

use Hyperf\Engine\Signal;

/**
 * @internal
 * @coversNothing
 */
class SignalTest extends AbstractTestCase
{
    public function testSignal()
    {
        $this->runInCoroutine(function () {
            $res = Signal::wait(SIGUSR1, 1);
            $this->assertFalse($res);

            go(static function () {
                sleep(1);
                posix_kill(getmypid(), SIGUSR1);
            });

            $res = Signal::wait(SIGUSR1, 2);
            $this->assertTrue($res);
        });
    }
}
