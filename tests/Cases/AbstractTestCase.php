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

use PHPUnit\Framework\TestCase;
use function Swoole\Coroutine\run;

/**
 * Class AbstractTestCase.
 */
abstract class AbstractTestCase extends TestCase
{
    public function runInCoroutine(callable $callable)
    {
        return run($callable);
    }
}
