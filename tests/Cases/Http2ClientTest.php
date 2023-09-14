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

use Hyperf\Engine\Http\V2\Client;
use Hyperf\Engine\Http\V2\Request;

/**
 * @internal
 * @coversNothing
 */
class Http2ClientTest extends AbstractTestCase
{
    /**
     * @group Server
     */
    public function testHttp2ServerReceived()
    {
        $this->runInCoroutine(function () {
            $client = new Client('127.0.0.1', 9501);
            $client->send(new Request('/'));
            $response = $client->recv(1);
            $this->assertSame('Hello World.', $response->getBody());

            $client->send(new Request('/header'));
            $response = $client->recv(1);
            $id = $response->getHeaders()['x-id'];
            $this->assertSame($id, $response->getBody());

            $client->send(new Request('/not-found'));
            $response = $client->recv(1);
            $this->assertSame(404, $response->getStatusCode());

            $this->assertTrue($client->isConnected());

            $client->close();

            $this->assertFalse($client->isConnected());
        });
    }
}
