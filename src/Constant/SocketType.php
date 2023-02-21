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
namespace Hyperf\Engine\Constant;

class SocketType
{
    public const SOCK_TCP = SWOOLE_SOCK_TCP;

    public const SOCK_TCP6 = SWOOLE_SOCK_TCP6;

    public const SOCK_UDP = SWOOLE_SOCK_UDP;

    public const SOCK_UDP6 = SWOOLE_SOCK_UDP6;

    public const SOCK_UNIX_STREAM = SWOOLE_SOCK_UNIX_STREAM;

    public const SOCK_UNIX_DGRAM = SWOOLE_SOCK_UNIX_DGRAM;
}
