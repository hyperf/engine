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

use Hyperf\Engine\Channel;
use Hyperf\Engine\Contract\ChannelInterface;

/**
 * @internal
 * @coversNothing
 */
class ChannelTest extends AbstractTestCase
{
    public function testChannelPushAndPop()
    {
        $this->runInCoroutine(function () {
            $result = [
                uniqid(),
                uniqid(),
                uniqid(),
            ];
            /** @var ChannelInterface $channel */
            $channel = new Channel(3);
            foreach ($result as $value) {
                $channel->push($value);
            }

            $actual[] = $channel->pop();
            $actual[] = $channel->pop();
            $actual[] = $channel->pop();

            $this->assertSame($result, $actual);
        });
    }
}
