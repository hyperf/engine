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

use Hyperf\Engine\Contract\Http\Chunkable;
use Hyperf\Engine\Contract\Http\ConnectionInterface;
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

    /**
     * @return Response
     */
    public function getSocket(): mixed
    {
        return $this->response;
    }

    public function end(): void
    {
        $this->response->end();
    }
}
