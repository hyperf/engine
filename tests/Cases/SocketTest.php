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

use Hyperf\Engine\Socket;

/**
 * @internal
 * @coversNothing
 */
class SocketTest extends AbstractTestCase
{
    /**
     * @group Server
     */
    // public function testTcpServer()
    // {
    //     $socket = new Socket();
    //     $socket->connect('127.0.0.1', 9502);
    //     $socket->write([(new Buffer())->write('ping')->rewind()]);
    //     $socket->recv($buffer = new Buffer());
    //     $this->assertSame('pong', $buffer->rewind()->getContents());
    //     usleep(1000);
    //     $socket->write([(new Buffer())->write('Hello World.')->rewind()]);
    //     $socket->recv($buffer = new Buffer());
    //     $this->assertSame('recv: Hello World.', $buffer->rewind()->getContents());
    //     $this->assertSame('recv: Hello World.', (string) $buffer);
    // }

    /**
     * @group Server
     */
    public function testSocketConnectTcpServer()
    {
        $this->runInCoroutine(function () {
            $socket = new Socket(AF_INET, SOCK_STREAM, 0);
            $socket->setProtocol([
                'open_length_check' => true,
                'package_max_length' => 1024 * 1024 * 2,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
            ]);
            $socket->connect('127.0.0.1', 9502);
            $socket->sendAll(pack('N', 4) . 'ping');
            $this->assertSame('pong', substr($socket->recvPacket(), 4));
        });
    }
}
