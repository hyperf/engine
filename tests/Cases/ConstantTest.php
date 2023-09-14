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

use Hyperf\Engine\Constant;
use Swoole\Coroutine\Http\Server as HttpServer;
use Swoole\Coroutine\Server;

/**
 * @internal
 * @coversNothing
 */
class ConstantTest extends AbstractTestCase
{
    public function testEngine()
    {
        $this->assertSame('Swoole', Constant::ENGINE);
    }

    public function testIsCoroutineServer()
    {
        $this->runInCoroutine(function () {
            $this->assertTrue(Constant::isCoroutineServer(new HttpServer('127.0.0.1')));
            $this->assertTrue(Constant::isCoroutineServer(new Server('127.0.0.1')));
        });
    }
}
