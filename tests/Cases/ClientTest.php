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
namespace HyperfTest\Cases;

use Hyperf\Engine\Http\Client;

/**
 * @internal
 * @coversNothing
 */
class ClientTest extends AbstractTestCase
{
    public function testClientRequest()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9501);
            $response = $client->request('GET', '/');
            $this->assertSame(200, $response->statusCode);
            $this->assertSame(['Hyperf'], $response->headers['server']);
            $this->assertSame('Hello World.', $response->body);
        });
    }

    public function testClientCookies()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9501);
            $response = $client->request('GET', '/cookies');
            $this->assertSame(200, $response->statusCode);
            $this->assertSame(['Hyperf'], $response->headers['server']);
            $this->assertSame([
                'X-Server-Id=' . $response->body,
                'X-Server-Name=Hyperf',
            ], $response->headers['Set-Cookies']);
        });
    }
}
