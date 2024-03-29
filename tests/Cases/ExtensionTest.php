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

use Hyperf\Engine\Extension;

/**
 * @internal
 * @coversNothing
 */
class ExtensionTest extends AbstractTestCase
{
    public function testExtensionLoaded()
    {
        $this->assertTrue(Extension::isLoaded());
    }
}
