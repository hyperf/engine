<?php

namespace Hyperf\Engine\Http;

use Hyperf\Engine\Contract\Http\ConnectionInterface;
use Hyperf\Engine\Contract\Http\Chunkable;

use Swoole\Http\Response;

class Connection implements ConnectionInterface, Chunkable
{
    public function __construct(protected Response $response)
    {
    }

    public function write(string $data): bool
    {
        return $this->response->write($data);
    }

    public function getSocket(): mixed
    {
        return $this->response;
    }

    public function end(): void
    {
        $this->response->end();
    }
}
