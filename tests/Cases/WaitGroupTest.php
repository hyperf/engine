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

use Hyperf\Engine\Coroutine;
use Hyperf\Engine\WaitGroup;

/**
 * @internal
 * @coversNothing
 */
class WaitGroupTest extends AbstractTestCase
{
    public function testWaitAgain()
    {
        $this->runInCoroutine(function () {
            $wg = new WaitGroup();
            $wg->add(2);
            $result = [];
            $i = 2;
            while ($i--) {
                Coroutine::create(function () use ($wg, &$result) {
                    usleep(1000);
                    $result[] = true;
                    $wg->done();
                });
            }
            $wg->wait(1);
            $this->assertTrue(count($result) === 2);

            $wg->add();
            $wg->add();
            $result = [];
            $i = 2;
            while ($i--) {
                Coroutine::create(function () use ($wg, &$result) {
                    usleep(1000);
                    $result[] = true;
                    $wg->done();
                });
            }
            $wg->wait(1);
            $this->assertTrue(count($result) === 2);
        });
    }
}
