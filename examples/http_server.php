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
use Swoole\Coroutine\Http\Server;

use function Swoole\Coroutine\run;

require_once __DIR__ . '/../vendor/autoload.php';
Coroutine::set([
    'hook_flags' => SWOOLE_HOOK_ALL,
]);

$callback = function () {
    $server = new Server('0.0.0.0', 9501);
    $server->handle('/', function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        $response->setHeader('Server', 'Hyperf');
        switch ($request->server['request_uri']) {
            case '/':
                $response->end('Hello World.');
                break;
            case '/header':
                $response->header('X-ID', [uniqid(), $id = uniqid()]);
                $response->end($id);
                break;
            case '/cookies':
                $response->setCookie('X-Server-Id', $id = uniqid());
                $response->setCookie('X-Server-Name', 'Hyperf');
                $response->end($id);
                break;
            case '/timeout':
                $time = $request->get['time'] ?? 1;
                sleep((int) $time);
                $response->end();
                break;
            default:
                $response->setStatusCode(404);
                $response->end();
                break;
        }
    });
    $server->start();
};
if (function_exists('Swoole\Coroutine\run')) {
    run($callback);
} else {
    Coroutine::create($callback);
}
