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

use Hyperf\Engine\Exception\SocketConnectException;
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

    public function testSocketConnectFailed()
    {
        $this->runInCoroutine(function () {
            try {
                (new Socket\SocketFactory())->make(new Socket\SocketOption('127.0.0.1', 33333));
            } catch (SocketConnectException $exception) {
                $this->assertSame(SOCKET_ECONNREFUSED, $exception->getCode());
                $this->assertSame('Connection refused', $exception->getMessage());
            }

            try {
                (new Socket\SocketFactory())->make(new Socket\SocketOption('192.0.0.1', 9501, 1));
            } catch (SocketConnectException $exception) {
                $this->assertSame(SOCKET_ETIMEDOUT, $exception->getCode());
                $this->assertSame('Operation timed out', $exception->getMessage());
            }
        });
    }

    /**
     * @group Server
     */
    public function testSocketRecvPacketFromTcpServer()
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

            $id = uniqid();
            $socket->sendAll(pack('N', strlen($id)) . $id);
            $this->assertSame('recv:' . $id, substr($socket->recvPacket(), 4));
        });

        $this->runInCoroutine(function () {
            $socket = (new Socket\SocketFactory())->make(new Socket\SocketOption('127.0.0.1', 9502, protocol: [
                'open_length_check' => true,
                'package_max_length' => 1024 * 1024 * 2,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
            ]));
            $socket->sendAll(pack('N', 4) . 'ping');
            $this->assertSame('pong', substr($socket->recvPacket(), 4));

            $id = uniqid();
            $socket->sendAll(pack('N', strlen($id)) . $id);
            $this->assertSame('recv:' . $id, substr($socket->recvPacket(), 4));
        });
    }

    /**
     * @group Server
     */
    public function testSocketRecvAllFromTcpServer()
    {
        $this->runInCoroutine(function () {
            $socket = new Socket(AF_INET, SOCK_STREAM, 0);
            $socket->connect('127.0.0.1', 9502);
            $socket->sendAll(pack('N', 4) . 'ping');
            $res = $socket->recvAll(4);
            $this->assertSame(4, unpack('Nlen', $res)['len']);
            $res = $socket->recvAll(4);
            $this->assertSame('pong', $res);

            $id = str_repeat(uniqid(), rand(1, 10));
            $socket->sendAll(pack('N', $len = strlen($id)) . $id);
            $res = $socket->recvAll(4);
            $len += 5;
            $this->assertSame($len, unpack('Nlen', $res)['len']);
            $res = $socket->recvAll($len);
            $this->assertSame('recv:' . $id, $res);
        });
    }
}
