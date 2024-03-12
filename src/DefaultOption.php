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

use Hyperf\Engine\Contract\DefaultOptionInterface;

class DefaultOption implements DefaultOptionInterface
{
    public static function hookFlags(): int
    {
        return defined('SWOOLE_HOOK_ALL') ? SWOOLE_HOOK_ALL : 0;
    }
}
