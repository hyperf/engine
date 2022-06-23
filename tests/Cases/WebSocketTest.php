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

use Hyperf\Engine\WebSocket\Opcode;
use Swoole\Coroutine\Http\Client;
use Swoole\WebSocket\Frame;

/**
 * @internal
 * @coversNothing
 */
class WebSocketTest extends AbstractTestCase
{
    /**
     * @group Server
     */
    public function testWebSocket()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9503, false);
            $client->upgrade('/');

            $client->push('Hello World!', Opcode::TEXT);
            $ret = $client->recv(1);
            $this->assertInstanceOf(Frame::class, $ret);
            $this->assertSame('received: Hello World!', $ret->data);
            $this->assertSame(Opcode::TEXT, $ret->opcode);

            $client->push('', Opcode::PING);
            $ret = $client->recv(1);
            $this->assertInstanceOf(Frame::class, $ret);
            $this->assertSame(Opcode::PONG, $ret->opcode);
        });
    }
}
