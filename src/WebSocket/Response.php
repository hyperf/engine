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
namespace Hyperf\Engine\WebSocket;

use Hyperf\Engine\Contract\WebSocket\FrameInterface;
use Hyperf\Engine\Contract\WebSocket\ResponseInterface;
use Hyperf\Engine\Exception\InvalidArgumentException;
use Swoole\Http\Response as SwooleResponse;
use Swoole\WebSocket\Server;

use function Hyperf\Engine\swoole_get_flags_from_frame;

class Response implements ResponseInterface
{
    public function __construct(protected mixed $connection)
    {
    }

    public function push(FrameInterface $frame, int $fd = 0): bool
    {
        $data = (string) $frame->getPayloadData();
        $flags = swoole_get_flags_from_frame($frame);

        if ($this->connection instanceof SwooleResponse) {
            $this->connection->push($data, $frame->getOpcode(), $flags);
        }

        if ($this->connection instanceof Server) {
            $this->connection->push($fd, $data, $frame->getOpcode(), $flags);
        }

        throw new InvalidArgumentException('The websocket connection is invalid.');
    }
}
