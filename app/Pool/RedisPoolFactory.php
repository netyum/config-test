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
namespace App\Pool;

use Hyperf\Di\Container;
use Hyperf\Redis\Pool\RedisPool;
use Psr\Container\ContainerInterface;

class RedisPoolFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RedisPool[]
     */
    protected $pools = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getPool(string $name): RedisPool
    {
        if (isset($this->pools[$name])) {
            return $this->pools[$name];
        }

        if ($this->container instanceof Container) {
            $pool = $this->container->make(RedisPool::class, ['name' => $name]);
        } else {
            $pool = new RedisPool($this->container, $name);
        }
        return $this->pools[$name] = $pool;
    }

    /**
     * 清掉连接池.
     */
    public function clearPool(string $name)
    {
        unset($this->pools[$name]);
    }
}
