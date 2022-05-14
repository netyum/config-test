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
return [
    Hyperf\ConfigApollo\ApolloDriver::class => App\Config\ApolloDriver::class,
    Hyperf\ConfigNacos\NacosDriver::class => App\Config\NacosDriver::class,
    Hyperf\ConfigEtcd\EtcdDriver::class => App\Config\EtcdDriver::class,
    Hyperf\DbConnection\Pool\PoolFactory::class => App\Pool\DbPoolFactory::class,
    Hyperf\Redis\Pool\PoolFactory::class => App\Pool\RedisPoolFactory::class,
];
