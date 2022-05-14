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
namespace App\Config;

use Hyperf\ConfigCenter\AbstractDriver;
use Hyperf\ConfigZookeeper\ClientInterface;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

class ZookeeperDriver extends AbstractDriver
{
    protected $driverName = 'zookeeper';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->client = $container->get(ClientInterface::class);
    }

    protected function updateConfig(array $config)
    {
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $this->config->set($key, $value);
                $this->logger->debug(sprintf('Config [%s] is updated', $key));
            }
        }
        $this->updateConfigChange($config);
    }

    /**
     * @param $config
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function updateConfigChange($config)
    {
        $this->updateDatabaseConfigChange($config);
        $this->updateRedisConfigChange($config);
    }

    /**
     * 更新数据库配置变化，清空连接池.
     * @param $config
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function updateDatabaseConfigChange($config)
    {
        static $prevConfig = [];
        if (! empty($prevConfig) && $prevConfig != $config) {
            $clearPool = [];
            $keys = array_keys($config);
            foreach ($keys as $key) {
                if ($key === 'databases') {
                    $subKeys = array_keys($config[$key]);
                    foreach ($subKeys as $poolName) {
                        if (isset($prevConfig[$key][$poolName]) && $prevConfig[$key][$poolName] != $config[$key][$poolName]) {
                            $clearPool[$poolName] = $poolName;
                        }
                    }
                }
            }

            if (count($clearPool) > 0) {
                foreach ($clearPool as $poolName) {
                    $pool = ApplicationContext::getContainer()->get(\Hyperf\DbConnection\Pool\PoolFactory::class);
                    $pool->clearPool($poolName);
                }
            }
        }
        $prevConfig = $config;
    }

    /**
     * 更新Redis配置变化，清空连接池.
     * @param $config
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function updateRedisConfigChange($config)
    {
        static $prevConfig = [];
        if (! empty($prevConfig) && $prevConfig != $config) {
            $clearPool = [];
            $keys = array_keys($config);
            foreach ($keys as $key) {
                if ($key === 'redis') {
                    $subKeys = array_keys($config[$key]);
                    foreach ($subKeys as $poolName) {
                        if (isset($prevConfig[$key][$poolName]) && $prevConfig[$key][$poolName] != $config[$key][$poolName]) {
                            $clearPool[$poolName] = $poolName;
                        }
                    }
                }
            }

            if (count($clearPool) > 0) {
                foreach ($clearPool as $poolName) {
                    $pool = ApplicationContext::getContainer()->get(\Hyperf\Redis\Pool\PoolFactory::class);
                    $pool->clearPool($poolName);
                }
            }
        }
        $prevConfig = $config;
    }
}
