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
namespace Hyperf\Engine;

use Hyperf\Engine\Contract\ChannelInterface;

class Channel extends \Swoole\Coroutine\Channel implements ChannelInterface
{
    public function getCapacity()
    {
        return $this->capacity;
    }

    public function getLength()
    {
        return $this->length();
    }

    public function isAvailable()
    {
        // TODO: Implement isAvailable() method.
    }

    public function hasProducers()
    {
        // TODO: Implement hasProducers() method.
    }

    public function hasConsumers()
    {
        // TODO: Implement hasConsumers() method.
    }

    public function isReadable()
    {
        // TODO: Implement isReadable() method.
    }

    public function isWritable()
    {

    }

    public function isClosing()
    {
        return $this->errCode === SWOOLE_CHANNEL_CLOSED;
    }
}
