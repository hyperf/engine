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

use Hyperf\Engine\Exception\SocketClosedException;
use Hyperf\Engine\Exception\SocketConnectException;
use Hyperf\Engine\SafeSocket;
use Hyperf\Engine\Socket;
use Swoole\Coroutine\Server;
use Throwable;

/**
 * @internal
 * @coversNothing
 */
class SocketTest extends AbstractTestCase
{
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
                $this->assertStringContainsString('timed out', $exception->getMessage());
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

    public function testSafeSocketSendAndRecvPacket()
    {
        $this->runInCoroutine(function () {
            $server = new Server('0.0.0.0', 9506);
            $p = function (string $data): string {
                return pack('N', strlen($data)) . $data;
            };
            go(function () use ($server, $p) {
                $server->set([
                    'open_length_check' => true,
                    'package_max_length' => 1024 * 1024 * 2,
                    'package_length_type' => 'N',
                    'package_length_offset' => 0,
                    'package_body_offset' => 4,
                ]);
                $server->handle(function (Server\Connection $connection) use ($p) {
                    $socket = new SafeSocket($connection->exportSocket(), 65535);
                    // $socket = $connection->exportSocket();
                    while (true) {
                        try {
                            $body = $socket->recvPacket();
                            if (empty($body)) {
                                break;
                            }
                            go(function () use ($socket, $body, $p) {
                                $body = substr($body, 4);
                                if ($body === 'ping') {
                                    $socket->sendAll($p('pong'));
                                } else {
                                    $socket->sendAll($p($body));
                                }
                            });
                        } catch (Throwable $exception) {
                            $this->assertInstanceOf(SocketClosedException::class, $exception);
                            break;
                        }
                    }
                });
                $server->start();
            });

            sleep(1);

            $socket = (new Socket\SocketFactory())->make(new Socket\SocketOption('127.0.0.1', 9506, protocol: [
                'open_length_check' => true,
                'package_max_length' => 1024 * 1024 * 2,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
            ]));

            for ($i = 0; $i < 200; ++$i) {
                $res = $socket->sendAll($p(str_repeat('s', 10240)), 1);
            }

            for ($i = 0; $i < 200; ++$i) {
                $socket->recvPacket(1);
            }

            $server->shutdown();
        });
    }

    public function testSafeSocketBroken()
    {
        $this->runInCoroutine(function () {
            $server = new Server('0.0.0.0', 9506);
            $p = function (string $data): string {
                return pack('N', strlen($data)) . $data;
            };
            go(function () use ($server, $p) {
                $server->set([
                    'open_length_check' => true,
                    'package_max_length' => 1024 * 1024 * 2,
                    'package_length_type' => 'N',
                    'package_length_offset' => 0,
                    'package_body_offset' => 4,
                ]);
                $server->handle(function (Server\Connection $connection) use ($p) {
                    $socket = new SafeSocket($connection->exportSocket(), 65535);
                    while (true) {
                        try {
                            $body = $socket->recvPacket();
                            if (empty($body)) {
                                break;
                            }
                            go(function () use ($socket, $body, $p) {
                                $body = substr($body, 4);
                                if ($body === 'ping') {
                                    $socket->sendAll($p('pong'));
                                } else {
                                    $socket->sendAll($p($body));
                                }
                            });
                        } catch (Throwable $exception) {
                            $this->assertInstanceOf(SocketClosedException::class, $exception);
                            break;
                        }
                    }
                });
                $server->start();
            });

            sleep(1);

            $socket = (new Socket\SocketFactory())->make(new Socket\SocketOption('127.0.0.1', 9506, protocol: [
                'open_length_check' => true,
                'package_max_length' => 1024 * 1024 * 2,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
            ]));

            $socket->sendAll($p(str_repeat('s', 10240)), 1);
            $socket->recvPacket(1);
            $socket->sendAll($p(str_repeat('s', 10240)), 1);
            $socket->recvPacket(1);

            $socket->close();

            sleep(1);

            $server->shutdown();
        });
    }
}
