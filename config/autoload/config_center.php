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
    ],
];
