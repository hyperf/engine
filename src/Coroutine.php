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

use Hyperf\Engine\Contract\CoroutineInterface;
use Hyperf\Engine\Exception\RuntimeException;
use Swoole\Coroutine as SwooleCo;

class Coroutine implements CoroutineInterface
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var int
     */
    private $id;

    public function __construct(callable $callable, int $stackPageSize = 0, int $stackSize = 0)
    {
        $this->callable = $callable;
    }

    public static function create(callable $callable, ...$data)
    {
        $coroutine = new static($callable);
        $coroutine->execute(...$data);
        return $coroutine;
    }

    public function execute(...$data)
    {
        $this->id = SwooleCo::create($this->callable, ...$data);
    }

    public function getId()
    {
        if (is_null($this->id)) {
            throw new RuntimeException('Coroutine was not be executed.');
        }
        return $this->id;
    }

    public static function id()
    {
        return SwooleCo::getCid();
    }

    public static function set(array $config)
    {
        SwooleCo::set($config);
    }

    public static function getContextFor(?int $id = null)
    {
        if ($id === null) {
            return SwooleCo::getContext();
        }

        return SwooleCo::getContext($id);
    }
}
