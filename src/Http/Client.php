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
namespace Hyperf\Engine\Http;

use Hyperf\Engine\Contract\Http\ClientInterface;
use Swoole\Coroutine\Http\Client as HttpClient;

class Client implements ClientInterface
{
    /**
     * @var HttpClient
     */
    protected $client;

    public function __construct(string $name, int $port, bool $ssl = false)
    {
        $this->client = new HttpClient($name, $port, $ssl);
    }

    public function set(array $settings)
    {
        $this->client->set($settings);
        return $this;
    }

    /**
     * @param string[][] $headers
     */
    public function request(string $method = 'GET', string $path = '/', array $headers = [], string $conotents = '', string $version = '1.1'): RawResponse
    {
        $client = $this->client;
        $client->setMethod($method);
        $client->setData($conotents);
        $client->setHeaders($this->encodeHeaders($headers));
        $client->execute($path);
        return new RawResponse(
            $client->statusCode,
            $this->decodeHeaders($client, $client->headers),
            $client->body,
            $version
        );
    }

    /**
     * @param string[] $headers
     * @return string[][]
     */
    private function decodeHeaders(HttpClient $client, array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $header) {
            $result[$name][] = $header;
        }
        if ($client->set_cookie_headers) {
            $result['Set-Cookies'] = $client->set_cookie_headers;
        }
        return $result;
    }

    /**
     * Swoole engine not support two dimensional array.
     * @param string[][] $headers
     * @return string[]
     */
    private function encodeHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $value) {
            $result[$name] = implode(',', $value);
        }

        return $result;
    }
}
