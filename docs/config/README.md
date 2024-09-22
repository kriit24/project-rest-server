## [<<<](https://github.com/kriit24/project-rest-server/) Config

#### # create file config/project.php

```
return [
    'hash' => [
        //128 AES key
        //if u use dynamic hash key then leave empty. IF auth key is empty then mac is not checked
        //u can generate key - Project\RestServer\Component\Crypto::generateKey()
        'key' => ''
    ],
    'model' => [
        'dir' => dirname(__DIR__) . '/app/Models',
        'namespace' => '\App\Models',
        //table to class name alias, lets say table is object, but in php u cannot make class object, so u add an alias objectT
        'alias' => ['object' => 'objectT'],
    ],
];
```

#### # database connections must be based on channel name what named in request as {db} config/database.php

```
<?php

return [

    'default' => env('DB_CONNECTION', 'localhost_1'),

    'connections' => [

        //TEST
        'localhost_1' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_LOCALHOST_1_HOST', '127.0.0.1'),
            'port' => env('DB_LOCALHOST_1_PORT', '3306'),
            'database' => env('DB_LOCALHOST_1_DATABASE', 'forge'),
            'username' => env('DB_LOCALHOST_1_USERNAME', 'forge'),
            'password' => env('DB_LOCALHOST_1_PASSWORD', ''),
            'unix_socket' => env('DB_LOCALHOST_1_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
        'localhost_2' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_LOCALHOST_2_HOST', '127.0.0.1'),
            'port' => env('DB_LOCALHOST_2_PORT', '3306'),
            'database' => env('DB_LOCALHOST_2_DATABASE', 'forge'),
            'username' => env('DB_LOCALHOST_2_USERNAME', 'forge'),
            'password' => env('DB_LOCALHOST_2_PASSWORD', ''),
            'unix_socket' => env('DB_LOCALHOST_2_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
    ],
];

```

#### # live config

```
//ADD desired trigger on each table 

CREATE TRIGGER `object_after_update` AFTER UPDATE ON `object` FOR EACH ROW BEGIN

CALL project_rest_event('object', OLD.object_id);

END
```
