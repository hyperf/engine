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
namespace Hyperf\Engine;

use Hyperf\Engine\Contract\SocketInterface;
use Hyperf\Engine\Exception\SocketClosedException;
use Hyperf\Engine\Exception\SocketTimeoutException;
use Swoole\Coroutine\Socket;

class SafeSocket implements SocketInterface
{
    protected Channel $channel;

    protected bool $loop = false;

    public function __construct(protected Socket $socket, int $capacity = 65535)
    {
        $this->channel = new Channel($capacity);
    }

    /**
     * @throws SocketTimeoutException when send data timeout
     * @throws SocketClosedException when the client is closed
     */
    public function sendAll(string $data, float $timeout = 0): int|false
    {
        $this->loop();

        $res = $this->channel->push([$data, $timeout], $timeout ?: -1);
        if ($res === false) {
            if ($this->channel->isClosing()) {
                throw new SocketClosedException('The channel is closed.');
            }
            if ($this->channel->isTimeout()) {
                throw new SocketTimeoutException('The channel is full.');
            }
        }
        return strlen($data);
    }

    /**
     * @throws SocketTimeoutException when send data timeout
     * @throws SocketClosedException when the client is closed
     */
    public function recvAll(int $length = 65536, float $timeout = 0): string|false
    {
        $res = $this->socket->recvAll($length, $timeout);
        if (! $res) {
            if ($this->socket->errCode === SOCKET_ETIMEDOUT) {
                throw new SocketTimeoutException('Recv timeout');
            }

            $this->close();
            throw new SocketClosedException('The socket is closed.');
        }

        return $res;
    }

    /**
     * @throws SocketTimeoutException when send data timeout
     * @throws SocketClosedException when the client is closed
     */
    public function recvPacket(float $timeout = 0): string|false
    {
        $res = $this->socket->recvPacket($timeout);
        if (! $res) {
            if ($this->socket->errCode === SOCKET_ETIMEDOUT) {
                throw new SocketTimeoutException('Recv timeout');
            }

            $this->close();
            throw new SocketClosedException('The socket is closed.');
        }

        return $res;
    }

    public function close(): bool
    {
        $this->channel->close();

        return $this->socket->close();
    }

    protected function loop(): void
    {
        if ($this->loop) {
            return;
        }

        $this->loop = true;

        go(function () {
            while (true) {
                $data = $this->channel->pop(-1);
                if ($this->channel->isClosing()) {
                    return;
                }

                [$data, $timeout] = $data;

                $this->socket->sendAll($data, $timeout);
            }
        });
    }
}
