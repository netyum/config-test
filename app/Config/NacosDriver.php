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
use Hyperf\ConfigNacos\Client;
use Hyperf\ConfigNacos\ClientInterface;
use Hyperf\ConfigNacos\Constants;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;

class NacosDriver extends AbstractDriver
{
    /**
     * @var Client
     */
    protected $client;

    protected $driverName = 'nacos';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->client = $container->get(ClientInterface::class);
    }

    protected function updateConfig(array $config)
    {
        $root = $this->config->get('config_center.drivers.nacos.default_key');
        foreach ($config ?? [] as $key => $conf) {
            if (is_int($key)) {
                $key = $root;
            }

            if (is_array($conf) && $this->config->get('config_center.drivers.nacos.merge_mode') === Constants::CONFIG_MERGE_APPEND) {
                $conf = Arr::merge($this->config->get($key, []), $conf);
            }

            $this->config->set($key, $conf);
            // 更新配置变化
            $this->updateConfigChange($key, $conf);
        }
    }

    /**
     * 更新配置变化.
     */
    private function updateConfigChange(string $key, array $config): void
    {
        if ($key == 'databases') {
            $this->updateDatabaseConfigChange($config);
        } elseif ($key == 'redis') {
            $this->updateRedisConfigChange($config);
        }
    }

    /**
     * 更新db连接池.
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function updateDatabaseConfigChange(array $config)
    {
        static $databasesPrevConfig = [];
        if (empty($databasesPrevConfig)) {
            $databasesPrevConfig = $config;
        } else {
            if ($databasesPrevConfig != $config) {
                $pool = ApplicationContext::getContainer()->get(\Hyperf\DbConnection\Pool\PoolFactory::class);
                foreach ($config as $poolName => $v) {
                    $pool->clearPool($poolName);
                }
                $databasesPrevConfig = $config;
            }
        }
    }

    /**
     * 更新redis连接池.
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function updateRedisConfigChange(array $config)
    {
        static $redisPrevConfig = [];
        if (empty($redisPrevConfig)) {
            $redisPrevConfig = $config;
        } else {
            if ($redisPrevConfig != $config) {
                $pool = ApplicationContext::getContainer()->get(\Hyperf\Redis\Pool\PoolFactory::class);
                foreach ($config as $poolName => $v) {
                    $pool->clearPool($poolName);
                }
                $redisPrevConfig = $config;
            }
        }
    }
}
