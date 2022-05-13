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

use Hyperf\ConfigApollo\ClientInterface;
use Hyperf\ConfigApollo\PullMode;
use Hyperf\ConfigCenter\AbstractDriver;
use Hyperf\ConfigCenter\Contract\PipeMessageInterface;
use Hyperf\Engine\Channel;
use Hyperf\Process\ProcessCollector;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class ApolloDriver extends AbstractDriver
{
    /**
     * @var ClientInterface
     */
    protected $client;

    protected $driverName = 'apollo';

    protected $notifications = [];

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->client = $container->get(ClientInterface::class);
    }

    public function createMessageFetcherLoop(): void
    {
        $pullMode = $this->config->get('config_center.drivers.apollo.pull_mode', PullMode::INTERVAL);
        if ($pullMode === PullMode::LONG_PULLING) {
            $this->handleLongPullingLoop();
        } elseif ($pullMode === PullMode::INTERVAL) {
            $this->handleIntervalLoop();
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function onPipeMessage(PipeMessageInterface $pipeMessage): void
    {
        $data = $pipeMessage->getData();
        // 这里改造了一下  消息增加了前一次配置，和当前配置
        $config = $data['config'];
        $prevConfig = $data['prevConfig'];
        $this->updateConfig($config);
        $this->updateConfigChange($config, $prevConfig);
    }

    protected function handleIntervalLoop(): void
    {
        $prevConfig = [];
        $this->loop(function () use (&$prevConfig) {
            $config = $this->pull();
            if ($config !== $prevConfig) {
                // 这里改造一下
                $this->syncConfig([
                    'prevConfig' => $prevConfig,
                    'config' => $config,
                ]);
                $prevConfig = $config;
            }
        });
    }

    protected function handleLongPullingLoop(): void
    {
        $prevConfig = [];
        $channel = new Channel(1);
        $this->longPulling($channel);
        Coroutine::create(function () use (&$prevConfig, $channel) {
            while (true) {
                try {
                    $namespaces = $channel->pop();
                    if (! $namespaces && $channel->isClosing()) {
                        break;
                    }
                    $config = $this->client->parallelPull($namespaces);
                    if ($config !== $prevConfig) {
                        $this->syncConfig([
                            'prevConfig' => $prevConfig,
                            'config' => $config,
                        ]);
                        $prevConfig = $config;
                    }
                } catch (\Throwable $exception) {
                    $this->logger->error((string) $exception);
                }
            }
        });
    }

    protected function loop(callable $callable, ?Channel $channel = null): int
    {
        return Coroutine::create(function () use ($callable, $channel) {
            $interval = $this->getInterval();
            retry(INF, function () use ($callable, $channel, $interval) {
                while (true) {
                    try {
                        $coordinator = CoordinatorManager::until(Constants::WORKER_EXIT);
                        $untilEvent = $coordinator->yield($interval);
                        if ($untilEvent) {
                            $channel && $channel->close();
                            break;
                        }
                        $callable();
                    } catch (\Throwable $exception) {
                        $this->logger->error((string) $exception);
                        throw $exception;
                    }
                }
            }, $interval * 1000);
        });
    }

    protected function longPulling(Channel $channel): void
    {
        $namespaces = $this->config->get('config_center.drivers.apollo.namespaces', []);
        foreach ($namespaces as $namespace) {
            $this->notifications[$namespace] = [
                'namespaceName' => $namespace,
                'notificationId' => -1,
            ];
        }
        $this->loop(function () use ($channel) {
            $response = $this->client->longPulling($this->notifications);
            if ($response instanceof ResponseInterface && $response->getStatusCode() === 200) {
                $body = json_decode((string) $response->getBody(), true);
                foreach ($body as $item) {
                    if (isset($item['namespaceName'], $item['notificationId']) && $item['notificationId'] > $this->notifications[$item['namespaceName']]['notificationId']) {
                        $prevId = $this->notifications[$item['namespaceName']]['notificationId'];
                        $this->notifications[$item['namespaceName']]['notificationId'] = $afterId = $item['notificationId'];
                        $this->logger->debug(sprintf('Updated apollo namespace [%s] notification id from %s to %s', $item['namespaceName'], $prevId, $afterId));
                        if ($prevId > -1) {
                            $channel->push([$item['namespaceName']]);
                        }
                    }
                }
            }
        }, $channel);
    }

    protected function pull(): array
    {
        return $this->client->pull();
    }

    protected function formatValue($value)
    {
        if (! $this->config->get('config_center.drivers.apollo.strict_mode', false)) {
            return $value;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        if (is_numeric($value)) {
            $value = (strpos($value, '.') === false) ? (int) $value : (float) $value;
        }

        return $value;
    }

    protected function updateConfig(array $config)
    {
        $mergedConfigs = [];
        foreach ($config as $c) {
            foreach ($c as $key => $value) {
                $mergedConfigs[$key] = $value;
            }
        }
        unset($config);
        foreach ($mergedConfigs ?? [] as $key => $value) {
            $this->config->set($key, $this->formatValue($value));
            $this->logger->debug(sprintf('Config [%s] is updated', $key));
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function syncConfig(array $config)
    {
        if (class_exists(ProcessCollector::class) && ! ProcessCollector::isEmpty()) {
            $this->shareConfigToProcesses($config);
        } else {
            // 结构变化了 这里处理一下
            $this->updateConfig($config['config']);
            $this->updateConfigChange($config['config'], $config['prevConfig']);
        }
    }

    /**
     * 更新配置变化.
     * @param $config
     * @param $prevConfig
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function updateConfigChange($config, $prevConfig)
    {
        $this->updateDatabaseConfigChange($config, $prevConfig);
        $this->updateRedisConfigChange($config, $prevConfig);
    }

    /**
     * 更新数据库配置变化，清空连接池.
     * @param $config
     * @param $prevConfig
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function updateDatabaseConfigChange($config, $prevConfig)
    {
        if (count($prevConfig) == 0) {
            return;
        }
        $clearPool = [];
        $keys = array_keys($config);
        foreach ($keys as $key) {
            $subKeys = array_keys($config[$key]);
            foreach ($subKeys as $subKey) {
                $patten = '#databases\.(.+?)\.#is';
                if (preg_match($patten, $subKey, $matches)) {
                    $poolName = $matches[1];
                    if (isset($prevConfig[$key][$subKey]) && $prevConfig[$key][$subKey] != $config[$key][$subKey]) {
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

    /**
     * 更新Redis配置变化，清空连接池.
     * @param $config
     * @param $prevConfig
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function updateRedisConfigChange($config, $prevConfig)
    {
        if (count($prevConfig) == 0) {
            return;
        }
        $clearPool = [];
        $keys = array_keys($config);
        foreach ($keys as $key) {
            $subKeys = array_keys($config[$key]);
            foreach ($subKeys as $subKey) {
                $patten = '#redis\.(.+?)\.#is';
                if (preg_match($patten, $subKey, $matches)) {
                    $poolName = $matches[1];
                    if (isset($prevConfig[$key][$subKey]) && $prevConfig[$key][$subKey] != $config[$key][$subKey]) {
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
}
