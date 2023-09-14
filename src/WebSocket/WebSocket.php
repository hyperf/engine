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

use Hyperf\Engine\Contract\WebSocket\WebSocketInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\CloseFrame;
use Swoole\WebSocket\Frame as SwFrame;

class WebSocket implements WebSocketInterface
{
    protected ?Response $connection;

    /**
     * @var array<string, callable>
     */
    protected array $events = [];

    public function __construct(Response $connection, Request $request)
    {
        $this->connection = $connection;
        $this->connection->upgrade();
    }

    public function on(string $event, callable $callback): void
    {
        $this->events[$event] = $callback;
    }

    public function start(): void
    {
        while (true) {
            /** @var false|string|SwFrame $frame */
            $frame = $this->connection->recv();
            if ($frame === false || $frame instanceof CloseFrame || $frame === '') {
                if ($callback = $this->events[static::ON_CLOSE] ?? null) {
                    $callback($this->connection, $this->connection->fd);
                }
                break;
            }

            switch ($frame->opcode) {
                case Opcode::PING:
                    $this->connection->push('', Opcode::PONG);
                    break;
                case Opcode::PONG:
                    break;
                default:
                    if ($callback = $this->events[static::ON_MESSAGE] ?? null) {
                        $callback($this->connection, $frame);
                    }
            }
        }

        $this->connection = null;
        $this->events = [];
    }
}
