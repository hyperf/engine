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
use Hyperf\Codec\Json;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Engine\Coroutine;
use Hyperf\Engine\Http\ServerFactory;
use Hyperf\Engine\Http\Stream;
use Hyperf\Engine\ResponseEmitter;
use Hyperf\HttpMessage\Cookie\Cookie;
use Hyperf\HttpMessage\Server\Request;
use Hyperf\HttpMessage\Server\Response;
use Psr\Http\Message\RequestInterface;
use Swoole\Http\Response as SwooleResponse;

use function Swoole\Coroutine\run;

require_once __DIR__ . '/../vendor/autoload.php';
Coroutine::set([
    'hook_flags' => SWOOLE_HOOK_ALL,
]);

$callback = function () {
    $logger = Mockery::mock(StdoutLoggerInterface::class);
    $logger->shouldReceive('error', 'critical')->withAnyArgs()->andReturnUsing(static function ($args) {
        echo $args . PHP_EOL;
    });

    $emitter = new ResponseEmitter($logger);
    $server = (new ServerFactory($logger))->make('0.0.0.0', 9505);

    $server->handle(static function (RequestInterface $request, SwooleResponse $response) use ($emitter) {
        $path = $request->getUri()->getPath();

        $callback = match ($path) {
            '/set-cookies' => function (Request $request) {
                $cookies = $request->getCookieParams();
                $json = Json::decode((string) $request->getBody());

                $response = new Response();
                if (! empty($json['id'])) {
                    $response->setCookie(new Cookie('id', $json['id']));
                }
                if (! empty($json['id2'])) {
                    $response->setCookie(new Cookie('id2', $json['id2']));
                }

                return $response->withBody(new Stream(Json::encode($cookies)));
            },
            default => function (RequestInterface $request) {
                $body = (string) $request->getBody();
                $ret = 'Hello World.';
                if ($body) {
                    $ret = 'Received: ' . $body;
                }

                return (new Response())->withBody(new Stream($ret));
            }
        };
        $emitter->emit($callback($request), $response);
    });

    $server->start();
};

run($callback);
