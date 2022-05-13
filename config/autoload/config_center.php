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
use Hyperf\ConfigApollo\PullMode;
use Hyperf\ConfigCenter\Mode;

$namespaces = explode(',', env('APOLLO_NAMESPACES', 'application'));

return [
    'enable' => (bool) env('CONFIG_CENTER_ENABLE', true),
    'driver' => env('CONFIG_CENTER_DRIVER', 'apollo'),
    'mode' => env('CONFIG_CENTER_MODE', Mode::PROCESS),
    'drivers' => [
        'apollo' => [
            'driver' => Hyperf\ConfigApollo\ApolloDriver::class,
            'pull_mode' => PullMode::INTERVAL,
            'server' => env('APOLLO_CONFIG_URL', 'http://127.0.0.1:9080'),
            'appid' => env('APOLLO_APPID', 'test'),
            'cluster' => env('APOLLO_CLUSTER', 'default'),
            'namespaces' => [
                'application',
            ],
            'interval' => 5,
            'strict_mode' => true,
            'client_ip' => current(swoole_get_local_ip()),
            'pullTimeout' => 10,
            'interval_timeout' => 1,
        ],
        'nacos' => [
            'driver' => Hyperf\ConfigNacos\NacosDriver::class,
            // 配置合并方式，支持覆盖和合并
            'merge_mode' => Hyperf\ConfigNacos\Constants::CONFIG_MERGE_APPEND,
            'interval' => 3,
            // 如果对应的映射 key 没有设置，则使用默认的 key
            'default_key' => env('NACOS_DEFAULT_KEY', 'nacos_config'),
            'listener_config' => [
                // dataId, group, tenant, type, content
                // 映射后的配置 KEY => Nacos 中实际的配置
                'databases' => [
                    'tenant' => 'public', // corresponding with service.namespaceId
                    'data_id' => 'databases',
                    'group' => 'DEFAULT_GROUP',
                    'type' => 'json',
                ],
                'redis' => [
                    'tenant' => 'public', // corresponding with service.namespaceId
                    'data_id' => 'redis',
                    'group' => 'DEFAULT_GROUP',
                    'type' => 'json',
                ],
            ],
            'client' => [
                // nacos server url like https://nacos.hyperf.io, Priority is higher than host:port
                'uri' => env('NACOS_CONFIG_URL', 'http://127.0.0.1:8848'),
                // 'host' => '120.53.67.144',
                // 'port' => 8848,
                'username' => env('NACOS_USERNAME', null),
                'password' => env('NACOS_PASSWORD', null),
                'guzzle' => [
                    'config' => null,
                ],
            ],
        ],
    ],
];
