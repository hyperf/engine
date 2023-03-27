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

namespace Hyperf\Engine\Http;

use Hyperf\Engine\Contract\Http\Writable;
use Swoole\Http\Response;

class EventStream
{
    protected bool $isTransfer = false;

    protected Writable $connection;

    public function __construce(Writable $connection)
    {
        $this->connection = $connection;
    }

    public function createStream(): self
    {
        if (! $this->isTransfer) {
            /** @var Response $socket */
            $socket = $this->connection->getSocket();
            $socket->header('Content-Type', 'text/event-stream; charset=utf-8');
            $socket->header('Transfer-Encoding', 'chunked');
        }
        $this->isTransfer = true;
        return $this;
    }

    public function write(string $data): self
    {
        $this->connection->write($data);
        return $this;
    }

    public function end(): void
    {
        $this->connection->end();
    }
}