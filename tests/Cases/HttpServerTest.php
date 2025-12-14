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

use Hyperf\Codec\Json;
use Hyperf\Engine\Http\Client;

/**
 * @internal
 * @coversNothing
 */
class HttpServerTest extends AbstractTestCase
{
    /**
     * @group Server
     */
    public function testHttpServerHelloWorld()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9505);
            $response = $client->request('GET', '/');
            $this->assertSame(200, $response->statusCode);
            $this->assertSame('Hello World.', $response->body);
        });
    }

    /**
     * @group Server
     */
    public function testHttpServerReceived()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9505);
            $response = $client->request('POST', '/', contents: 'Hyperf');
            $this->assertSame(200, $response->statusCode);
            $this->assertSame('Received: Hyperf', $response->body);
        });
    }

    /**
     * @group Server
     */
    public function testHttpServerCookies()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9505);

            $client->setCookies(['key' => 'value']);

            $response = $client->request('POST', '/set-cookies', ['user_id' => uniqid()], Json::encode(['id' => $id = uniqid()]));
            $this->assertSame(200, $response->statusCode);
            $this->assertSame(1, count($response->getHeaders()['set-cookie']));
            $this->assertStringStartsWith('id=' . $id, $response->getHeaders()['set-cookie'][0]);
            $json = Json::decode((string) $response->getBody());
            $this->assertSame(['key' => 'value'], $json);

            $response = $client->request('POST', '/set-cookies', [], Json::encode(['id2' => $id2 = uniqid()]));
            $this->assertSame(200, $response->statusCode);
            $this->assertSame(1, count($response->getHeaders()['set-cookie']));
            $this->assertStringStartsWith('id2=' . $id2, $response->getHeaders()['set-cookie'][0]);
            $json = Json::decode((string) $response->getBody());
            $this->assertSame(['key' => 'value', 'id' => $id], $json);

            $client->setCookies([]);
            $response = $client->request('POST', '/set-cookies', [], Json::encode(['id2' => $id2 = uniqid()]));
            $this->assertSame(200, $response->statusCode);
            $this->assertSame(1, count($response->getHeaders()['set-cookie']));
            $this->assertStringStartsWith('id2=' . $id2, $response->getHeaders()['set-cookie'][0]);
            $json = Json::decode((string) $response->getBody());
            $this->assertSame([], $json);
        });
    }
}
