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
use Hyperf\Engine\Coroutine;
use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;

use function Swoole\Coroutine\run;

require_once __DIR__ . '/../vendor/autoload.php';
Coroutine::set([
    'hook_flags' => SWOOLE_HOOK_ALL,
]);

function p(string $data): string
{
    return pack('N', strlen($data)) . $data;
}

run(function () {
    $server = new Server('0.0.0.0', 9502);
    $server->set([
        'open_length_check' => true,
        'package_max_length' => 1024 * 1024 * 2,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
    ]);
    $server->handle(function (Connection $connection) {
        $socket = $connection->exportSocket();
        while (true) {
            $body = $socket->recvPacket();
            if (empty($body)) {
                break;
            }
            $body = substr($body, 4);
            if ($body === 'ping') {
                $socket->sendAll(p('pong'));
            } else {
                $socket->sendAll(p('recv:' . $body));
            }
        }
    });
    $server->start();
});
