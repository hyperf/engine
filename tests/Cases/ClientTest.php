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

use GuzzleHttp;
use Hyperf\Engine\Exception\HttpClientException;
use Hyperf\Engine\Http\Client;
use Hyperf\Guzzle\CoroutineHandler;
use Throwable;

/**
 * @internal
 * @coversNothing
 */
class ClientTest extends AbstractTestCase
{
    /**
     * @group Server
     */
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

    /**
     * @group Server
     */
    public function testClientSocketConnectionRefused()
    {
        $this->runInCoroutine(function () {
            try {
                $client = new Client('127.0.0.1', 9502);
                $client->request('GET', '/timeout?time=1');
                $this->assertTrue(false);
            } catch (Throwable $exception) {
                $this->assertInstanceOf(HttpClientException::class, $exception);
                $this->assertSame(SOCKET_ECONNREFUSED, $exception->getCode());
                $this->assertSame('Connection refused', $exception->getMessage());
            }
        });
    }

    /**
     * @group Server
     */
    public function testClientJsonRequest()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9501);
            $response = $client->request(
                'POST',
                '/',
                ['Content-Type' => 'application/json charset=UTF-8'],
                json_encode(['name' => 'Hyperf'], JSON_UNESCAPED_UNICODE)
            );
            $this->assertSame(200, $response->statusCode);
            $this->assertSame(['Hyperf'], $response->headers['server']);
            $this->assertSame('Hello World.', $response->body);
        });
    }

    /**
     * @group Server
     */
    public function testClientSocketConnectionTimeout()
    {
        $this->runInCoroutine(function () {
            try {
                $client = new Client('127.0.0.1', 9501);
                $client->set(['timeout' => 0.1]);
                $client->request('GET', '/timeout?time=1');
                $this->assertTrue(false);
            } catch (Throwable $exception) {
                $this->assertInstanceOf(HttpClientException::class, $exception);
                $this->assertSame(SOCKET_ETIMEDOUT, $exception->getCode());
                $this->assertStringContainsString('timed out', $exception->getMessage());
            }
        });
    }

    /**
     * @group Server
     */
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
            ], $response->headers['set-cookie']);
        });
    }

    /**
     * @group Server
     */
    public function testGuzzleClientWithCookies()
    {
        $this->runInCoroutine(function () {
            $client = new GuzzleHttp\Client([
                'base_uri' => 'http://127.0.0.1:9501/',
                'handler' => GuzzleHttp\HandlerStack::create(new CoroutineHandler()),
                'cookies' => true,
            ]);

            $response = $client->get('cookies');

            $cookies = $client->getConfig('cookies');

            $this->assertSame((string) $response->getBody(), $cookies->toArray()[0]['Value']);
            $this->assertSame('Hyperf', $cookies->toArray()[1]['Value']);
        });
    }

    /**
     * @group Server
     */
    public function testServerHeaders()
    {
        $this->runInCoroutine(function () {
            // Co Client Won't support to get multi response headers.
            $client = new Client('127.0.0.1', 9501);
            $response = $client->request('GET', '/header');
            $this->assertSame($response->body, implode(',', $response->headers['x-id']));

            $client = new GuzzleHttp\Client([
                'base_uri' => 'http://127.0.0.1:9501/',
                'handler' => GuzzleHttp\HandlerStack::create(new CoroutineHandler()),
            ]);

            $response = $client->get('/header');
            $this->assertSame((string) $response->getBody(), $response->getHeaderLine('x-id'));

            // When Swoole version > 4.5, The native curl support to get multi response headers.
            if (SWOOLE_VERSION_ID >= 40600) {
                $client = new GuzzleHttp\Client([
                    'base_uri' => 'http://127.0.0.1:9501/',
                ]);
                $response = $client->get('/header');
                $this->assertSame(2, count($response->getHeader('x-id')));
                $this->assertSame((string) $response->getBody(), $response->getHeader('x-id')[1]);
            }
        });
    }

    /**
     * @group Server
     */
    public function testClientNotFound()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9501);
            $response = $client->request('GET', '/not_found');
            $this->assertSame(404, $response->statusCode);
        });
    }
}
