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

use Hyperf\DbConnection\Pool\DbPool;
use Hyperf\Di\Container;
use Psr\Container\ContainerInterface;

class DbPoolFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var DbPool[]
     */
    protected $pools = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getPool(string $name): DbPool
    {
        if (isset($this->pools[$name])) {
            return $this->pools[$name];
        }

        if ($this->container instanceof Container) {
            $pool = $this->container->make(DbPool::class, ['name' => $name]);
        } else {
            $pool = new DbPool($this->container, $name);
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
