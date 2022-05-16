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
use Hyperf\ConfigEtcd\ClientInterface;
use Hyperf\ConfigEtcd\KV;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Packer\JsonPacker;
use Psr\Container\ContainerInterface;

class EtcdDriver extends AbstractDriver
{
    /**
     * @var JsonPacker
     */
    protected $packer;

    /**
     * @var array
     */
    protected $mapping;

    protected $driverName = 'etcd';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->client = $container->get(ClientInterface::class);
        $this->mapping = $this->config->get('config_center.drivers.etcd.mapping', []);
        $this->packer = $container->get($this->config->get('config_center.drivers.etcd.packer', JsonPacker::class));
    }

    protected function updateConfig(array $config)
    {
        $configurations = $this->format($config);
        foreach ($configurations as $kv) {
            $key = $this->mapping[$kv->key] ?? null;
            if (is_string($key)) {
                $config = $this->packer->unpack($kv->value);
                $this->config->set($key, $this->packer->unpack($kv->value));
                $this->logger->debug(sprintf('Config [%s] is updated', $key));
                $this->updateConfigChange($key, $config);
            }
        }
    }

    /**
     * Format kv configurations.
     */
    protected function format(array $config): array
    {
        $result = [];
        foreach ($config as $value) {
            $result[] = new KV($value);
        }

        return $result;
    }

    /**
     * 更新配置变化.
     */
    private function updateConfigChange(string $key, ?array $config): void
    {
        if (is_null($config)) {
            return;
        }
        if ($key == 'databases') {
            $this->updateDatabaseConfigChange($config);
        } elseif ($key == 'redis') {
            $this->updateRedisConfigChange($config);
        } else {
            $patten = '#databases\.([^.]+)#is';
            if (preg_match($patten, $key, $matches)) {
                $poolName = $matches[1];
                $this->updateDatabaseConfigChange($config, $poolName);
            }

            $patten = '#redis\.([^.]+)#is';
            if (preg_match($patten, $key, $matches)) {
                $poolName = $matches[1];
                $this->updateRedisConfigChange($config, $poolName);
            }
        }
    }

    /**
     * 更新db连接池.
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function updateDatabaseConfigChange(array $config, ?string $poolName = null)
    {
        $pool = ApplicationContext::getContainer()->get(\Hyperf\DbConnection\Pool\PoolFactory::class);
        static $databasesPrevConfig = [];
        static $databasesPrevConfigWithPoolName = [];
        if (is_null($poolName)) {
            if (empty($databasesPrevConfig)) {
                $databasesPrevConfig = $config;
            } else {
                if ($databasesPrevConfig != $config) {
                    foreach ($config as $poolName => $v) {
                        $pool->clearPool($poolName);
                    }
                    $databasesPrevConfig = $config;
                }
            }
        } else {
            if (isset($databasesPrevConfigWithPoolName[$poolName])) {
                if ($databasesPrevConfigWithPoolName[$poolName] != $config) {
                    $pool->clearPool($poolName);
                }
            }
            $databasesPrevConfigWithPoolName[$poolName] = $config;
        }
    }

    /**
     * 更新redis连接池.
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function updateRedisConfigChange(array $config, ?string $poolName = null)
    {
        $pool = ApplicationContext::getContainer()->get(\Hyperf\Redis\Pool\PoolFactory::class);
        static $redisPrevConfig = [];
        static $redisPrevConfigWithPoolName = [];
        if (is_null($poolName)) {
            if (empty($redisPrevConfig)) {
                $redisPrevConfig = $config;
            } else {
                if ($redisPrevConfig != $config) {
                    foreach ($config as $poolName => $v) {
                        $pool->clearPool($poolName);
                    }
                    $redisPrevConfig = $config;
                }
            }
        } else {
            if (isset($redisPrevConfigWithPoolName[$poolName])) {
                if ($redisPrevConfigWithPoolName[$poolName] != $config) {
                    $pool->clearPool($poolName);
                }
            }
            $redisPrevConfigWithPoolName[$poolName] = $config;
        }
    }
}
