# hyperf 配置中心 更新底层配置

### apollo配置
```
databases.default.driver = mysql
databases.default.host = 127.0.0.1
databases.default.port = 3306
databases.default.database = test
databases.default.username = root
databases.default.password = root
databases.default.charset = utf8mb4
databases.default.collation = utf8mb4_unicode_ci
databases.default.prefix = 
databases.default.pool.min_connections = 1
databases.default.pool.max_connections = 10
databases.default.pool.connect_timeout = 10.0
databases.default.pool.wait_timeout = 3.0
databases.default.pool.heartbeat = -1
databases.default.pool.max_idle_time = 60
redis.default.host = 127.0.0.1
redis.default.auth = 
redis.default.port = 6379
redis.default.db = 0
redis.default.pool.min_connections = 1
```

### nancos配置
database data_id
```json
{
    "default": {
        "driver": "mysql",
        "host": "127.0.0.1",
        "port": 3306,
        "database": "test",
        "username": "roo3t",
        "password": "root",
        "charset": "utf8mb4",
        "collation": "utf8mb4_unicode_ci",
        "prefix": "",
        "pool": {
            "min_connections": 1,
            "max_connections": 10,
            "connect_timeout": 10.0,
            "wait_timeout": 3.0,
            "heartbeat": -1,
            "max_idle_time": 60
        }
    }
}
```
redis data_id
```json
{
    "default": {
        "host": "127.0.0.1",
        "auth": "",
        "port": 6379,
        "db": 0,
        "pool": {
            "min_connections": 1,
            "max_connections": 10,
            "connect_timeout": 10.0,
            "wait_timeout": 3.0,
            "heartbeat": -1,
            "max_idle_time": 60
        }
    }
}
```

### 修改

1. `App\Config\ApolloDriver`, `App\Config\NacosDriver` `App\Config\EtcdDriver` `App\Config\ZookeeperDriver`

增加`updateConfigChange` 更新db和redis变化

2. `App\Pool\DbPoolFactory` `App\Pool\RedisPoolFactory`

增加 `clearPool方法`  清除连接池

3. 使用依赖大法， 修改系统依赖 `config/dependencies.php` 

```
Hyperf\ConfigApollo\ApolloDriver::class => App\Config\ApolloDriver::class,
Hyperf\ConfigNacos\NacosDriver::class => App\Config\NacosDriver::class,
Hyperf\ConfigEtcd\EtcdDriver::class => App\Config\EtcdDriver::class,
Hyperf\ConfigZookeeper\ZookeeperDriver::class => App\Config\ZookeeperDriver::class,
Hyperf\DbConnection\Pool\PoolFactory::class => App\Pool\DbPoolFactory::class,
```

#### 实际测试

不能通过依赖注入大法，替换Redis的 PoolFactory类，因为上层，限定必须是  Hyperf\Redis\Pool\PoolFactory的实例，

所以Redis的PoolFactory采用class_map 替换大法,修改 config/autoload/annotations.php类，增加class_map
```
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
        ],
        'ignore_annotations' => [
            'mixin',
        ],
        'class_map' => [
            Hyperf\Redis\Pool\PoolFactory::class => __DIR__ . '/../class_map/Redis/Pool/PoolFactory.php',
        ],
    ],
```
添加对应的文件，做替换，保持相同命名空间

### 使用

修改`App\Controller\IndexController` 数据库操作

启动 `php bin/hyperf.php start`

访问地址，修改apollo相关的db或redis配置并发布，再直接刷新页面，看配置是否生效

比如现在是正确的， 修改个错误的，那访问就会报错，反之一样

### 命令行

命令行调用 配置中心，需要增加事件派发选项

```
php bin/hyperf.php gen:model "表名" --enable-event-dispatcher
```

### 注意

`config_center.php` apollo 相应驱动要使用严格模式，进行类型转换
```
'strict_mode' => true,
```