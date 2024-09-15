# project-rest-server
Project Rest Server is REST-api server for mysql  
Its based on Laravel 10+ framework

## Installation
This project uses composer.
```
$ composer require kriit24/project-rest-server
```

#### # create tables

```
DROP TABLE IF EXISTS table_relation;

CREATE TABLE `table_relation` (
	`table_relation_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`table_relation_table_name` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_general_ci',
	`table_relation_table_id` BIGINT(20) NOT NULL,
	`table_relation_unique_id` VARCHAR(255) NOT NULL COLLATE 'utf8mb3_general_ci',
	`table_relation_created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
	PRIMARY KEY (`table_relation_id`) USING BTREE,
	INDEX `table_relation_unique_id` (`table_relation_unique_id`) USING BTREE
)
COLLATE='utf8mb3_general_ci'
ENGINE=InnoDB;


DROP TABLE IF EXISTS table_changes;

CREATE TABLE `table_changes` (
    `table_changes_id` INT(11) NOT NULL AUTO_INCREMENT,
    `table_changes_table_name` VARCHAR(150) NOT NULL COLLATE 'utf8mb3_general_ci',
    `table_changes_table_id` BIGINT(20) NOT NULL DEFAULT '0',
    `table_changes_updated_at` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`table_changes_id`) USING BTREE,
    UNIQUE INDEX `table_changes_table_name_table_changes_table_id` (`table_changes_table_name`, `table_changes_table_id`) USING BTREE,
    INDEX `table_changes_updated_at` (`table_changes_updated_at`) USING BTREE
)
COLLATE='utf8mb3_general_ci'
ENGINE=InnoDB;


DROP PROCEDURE IF EXISTS project_rest_event;

DELIMITER //

CREATE PROCEDURE `project_rest_event`(IN `table_name` VARCHAR(150),IN `table_id` INT)
    LANGUAGE SQL
    DETERMINISTIC
    CONTAINS SQL
    SQL SECURITY DEFINER
    COMMENT ''
    BEGIN
    
        INSERT INTO table_changes (table_changes_table_name, table_changes_table_id, table_changes_updated_at)

			SELECT table_name, table_id, NOW()
			
			ON DUPLICATE KEY UPDATE
			table_changes_updated_at = NOW();
    
    END;
    
//    
    
DELIMITER ;
```

## Client for server  
#### react-native: [project-rest-client](https://www.npmjs.com/package/project-rest-client)


## NB!  
All the requests are POST methods because GET queries can distort data like umlauts and other special characters  
If u use json_encode to compile data then allways use it with option JSON_UNESCAPED_UNICODE


## Config

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



## Usage

#### # auth example (app/Http/Middleware/Authenticate.php)

```
//SET project.hash.key for each user after Auth is done
//OR use stationary in config/project.php
namespace App\Http\Middleware;

use App\Component\Auth;
use Closure;

class Authenticate
{
    public function handle($request, Closure $next, $guard = null)
    {
        $check = $request;
        if( $request->get('uuid') && $request->get('token') ){

            $check = $request->all();
        }

        if (($step = \App\Http\Requests\TokenRequest::isValid($check)) == 'ok') {

            $user_key = (new Auth())->UserData('user_key');//logged in user session
            config(['project.hash.key' => $user_key]);
            return $next($request);
        }

        return response('Unauthorized (' . $step . ').', 401);
    }
}
 ```

#### # routes example (routes/api.php)

```

Route::middleware([\App\Http\Middleware\AuthenticateOnceWithBasicAuth::class, Project\RestServer\Http\Middleware\VerifyPostMac::class])->group(function () {

    //make insert request
    Route::post('/post/{db}/{model}', function ($db, $model, Request $request) {

        if (Project\RestServer\Http\Requests\ValidateRequest::Broadcast($db, $model, $request)) {

            $event = new Project\RestServer\Broadcasting\DBBroadcast(
                Project\RestServer\Pusher\MysqlPost::class
            );
            $data = $event->broadcast($db, $model, $request);

            return response(['status' => 'ok', 'count' => !empty($data) ? 1 : 0, 'data' => $data]);
        }
        return response(['status' => 'error', 'message' => 'POST error:' . Project\RestServer\Http\Requests\ValidateRequest::getError()], 406);
    });

    //make update request
    Route::post('/put/{db}/{model}', function ($db, $model, Request $request) {

        if (Project\RestServer\Http\Requests\ValidateRequest::Broadcast($db, $model, $request)) {

            $event = new Project\RestServer\Broadcasting\DBBroadcast(
                Project\RestServer\Pusher\MysqlPut::class
            );
            $data = $event->broadcast($db, $model, $request);

            return response(['status' => 'ok', 'count' => !empty($data) ? 1 : 0, 'data' => $data]);
        }
        return response(['status' => 'error', 'message' => 'POST error:' . Project\RestServer\Http\Requests\ValidateRequest::getError()], 406);
    });

    //make insert or update on duplicate key request
    Route::post('/push/{db}/{model}', function ($db, $model, Request $request) {

        if (Project\RestServer\Http\Requests\ValidateRequest::Broadcast($db, $model, $request)) {

            $event = new Project\RestServer\Broadcasting\DBBroadcast(
                Project\RestServer\Pusher\MysqlPush::class
            );
            $data = $event->broadcast($db, $model, $request);

            return response(['status' => 'ok', 'count' => !empty($data) ? 1 : 0, 'data' => $data]);
        }
        return response(['status' => 'error', 'message' => 'POST error:' . Project\RestServer\Http\Requests\ValidateRequest::getError()], 406);
    });

    //make delete request
    Route::post('/delete/{db}/{model}', function ($db, $model, Request $request) {

        if (Project\RestServer\Http\Requests\ValidateRequest::Delete($db, $model, $request)) {

            $event = new Project\RestServer\Broadcasting\DBBroadcast(
                Project\RestServer\Pusher\MysqlDelete::class
            );
            $data = $event->broadcast($db, $model, $request);

            return response(['status' => 'ok', 'count' => !empty($data) ? 1 : 0, 'data' => $data]);
        }
        return response(['status' => 'error', 'message' => 'DELETE error:' . Project\RestServer\Http\Requests\ValidateRequest::getError()], 406);
    });

    //make get request
    Route::post('/fetch/{db}/{model}', function ($db, $model, Request $request) {

        if (Project\RestServer\Http\Requests\ValidateRequest::Fetch($db, $model, $request)) {

            $event = new Project\RestServer\Broadcasting\DBBroadcast(
                Project\RestServer\Getter\MysqlGetter::class
            );
            $data = $event->fetch($db, $model, $request);

            return response(['status' => 'ok', 'count' => count($data), 'data' => $data]);
        }
        return response(['status' => 'error', 'message' => 'FETCH error:' . Project\RestServer\Http\Requests\ValidateRequest::getError()], 406);
    });
    
    //make object API request
    Route::post('/object', function (Request $request) {

        $to_request = \Project\RestServer\Http\Requests\ToRequest::Post();
        $to_request->request->add(['join' => ['address']]);
        $to_request->request->add(['where' => $request->all()]);

        $event = new Project\RestServer\Broadcasting\DBBroadcast(
            Project\RestServer\Getter\MysqlGetter::class
        );
        $data = $event->fetch('localhost_1', 'object', $to_request);

        return response(['status' => 'ok', 'count' => count($data), 'data' => $data]);
    });
    
    //make object API GET request with or without object_id 
    Route::get('/object/{object_id?}', function (Request $request, $object_id = null) {

        $to_request = \Project\RestServer\Http\Requests\ToRequest::Post();
        $to_request->request->add(['join' => ['address']]);
        $to_request->request->add(['where' => array_filter(['object_id' => $object_id])]);

        $event = new Project\RestServer\Broadcasting\DBBroadcast(
            Project\RestServer\Getter\MysqlGetter::class
        );
        $data = $event->fetch('localhost_1', 'object', $to_request);

        return response(['status' => 'ok', 'count' => count($data), 'data' => $data]);
    });
    
    //make event request for live
    Route::post('/event/{db}/{model}', function ($db, $model, Request $request) {

        $ret = \Project\RestServer\Component\Event::handle($db, $model, $request);
        return response($ret);
    });
});

//LIVE request
Route::middleware([\App\Http\Middleware\Authenticate::class, Project\RestServer\Http\Middleware\VerifyGetMac::class])->group(function () {

    Route::get('/live/{db}/{model}', function ($db, $model, Request $request) {

        if (Project\RestServer\Http\Requests\ValidateRequest::Fetch($db, $model, $request)) {

            $event = new Project\RestServer\Broadcasting\DBBroadcast(
                Project\RestServer\Getter\MysqlLive::class
            );

            $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($event, $model, $db, $request) {

                $keep_alive = 60;
                $counter = $keep_alive;
                while (true) {

                    $data = $event->fetch($db, $model, $request);
                    if ($data instanceof Generator || $data instanceof Closure || $data instanceof \Illuminate\Support\LazyCollection) {

                        $rows = iterator_to_array($data);
                    }
                    else {

                        $rows = $data;
                    }

                    if (!empty($rows)) {

                        \Project\RestServer\Component\Event::message('message', json_encode(['status' => 'ok', 'count' => count($rows), 'data' => $rows], JSON_UNESCAPED_UNICODE), null, 5000);
                    }
                    else if( empty($rows) && $counter <= 0 ) {

                        \Project\RestServer\Component\Event::message('ping', 'ping');
                    }
                    if ($counter <= 0)
                        $counter = $keep_alive;

                    // Break the loop if the client aborted the connection (closed the page)
                    if (connection_aborted()) break;

                    $counter--;
                    sleep(1);
                }
            });
            $response->headers->set('Content-Type', 'text/event-stream');
            $response->headers->set('X-Accel-Buffering', 'no');
            $response->headers->set('Cach-Control', 'no-cache');
            $response->headers->set('Connection', 'keep-alive');
            $response->headers->set('Access-Control-Allow-Origin', '*');
            return $response;
        }
        return response(['status' => 'error', 'message' => 'LIVE FETCH error:' . Project\RestServer\Http\Requests\ValidateRequest::getError()], 406);
    });
});


Route::middleware([\App\Http\Middleware\AuthenticateOnceWithBasicAuth::class])->group(function () {

    if (env('APP_DEBUG')) {

        //u can generate mac key for testing purposes
        Route::post('/mac/gen', [\Project\RestServer\Http\Controllers\IndexController::class, 'MacGen']);
    }
});
```


#### PARAMS
```
$params = [
    //get all columns
    'column' => null,
    //get current columns
    'column' => ['column_1', 'column_2'],
    //get all parent columns also "use statement" columns
    'column' => ['*'],
    //get join columns 
    'column' => ['child_table.child_table_column', 'child_table_1.child_table_2.child_table_column'],
    //join sibling data, create "address" relationship method in App\Models\objectT
    'join' => ['join_1', 'join_2'],
    //use query as query builder
    'use' => ['use_1', 'use_2'],
    //u can use operands like RAW
    //if operand is RAW then first argument is used as where statement
    'where' => [['object_id', '=', 1]],
    'where' => [['object_id BETWEEN 1 AND 10', 'RAW']],
    'where' => [['object_id BETWEEN ? AND ?', 'RAW', [1,10]]],
    'group' => ["object_id", "object_name"],
    'order' => [["object_id", "DESC"], ["object_name", "ASC"]],
    'limit' => 1,
    'offset' => 10,
];
```

#### RELATIONAL INSERT

#### # setup relational child table


```
//App\Models\address.php - set relation after inserted
protected $dispatchesEvents = [
    'inserted' => AddressAfterInsert::class,
];

//App\Models\Event\AddressAfterInsert.php - call relation

declare(strict_types=1);

namespace App\Models\Events;

use App\Models\address;

class AddressAfterInsert extends address
{
    public function __construct($bindings, $tableData)
    {
        new \Project\RestServer\Models\Events\TableRelation($this->getTable(), $this->getKeyName(), $bindings, $tableData);
    }
}

```

#### # request

```
$unique_id = unique_id();
```

```
curl -i -X POST \
   -H "uuid:KgfMRZG3GWG9hRP7tHQz5qukD9T4Yg" \
   -H "token:5751d40d2e9ab5a163d772fbc6d8f7027180ad65f1345cf60534b5d0d1f04facd35271987f05e0c8c9e8b5ba6a881bbe7bcce7521d5d995bdf08bc2ea00bc7dd" \
   -H "Content-Type:application/json" \
   -H "mac:ZTRhMGQyY2M3YWJkNDAxN2NmMThjY2I1MTU1Yjk2ZjEzYWZlYjYxNTk2Y2ZkMmE5YTczNzhkMmE2ZmI0ZjE4MzRkODcyMTY2M2YyOTc1MGRhZjBkMzY5M2EyMTZkYzQ0" \
   -d \
'{"address_name":"test","data_unique_id":$unique_id}' \
 'https://localhost/post/localhost_1/address'
```

#### # setup relational parent table

```
//App\Models\objectT.php - set relation before insert
 protected $dispatchesEvents = [
    'inserting' => ObjectBeforeInsert::class,
];

//App\Models\Events\ObjectBeforeInsert.php - get relation id
declare(strict_types=1);

namespace App\Models\Events;

class ObjectBeforeInsert
{
    public function __construct(&$bindings)
    {
        if (isset($bindings['table_relation_unique_id'])) {

            $relation = \Project\RestServer\Models\Events\TableRelation::fetch($bindings['table_relation_unique_id']);
            if( !empty($relation) ) {
                
                //die(pre($relation));
                $bindings['object_address_id'] = $relation->table_relation_table_id;
            }
        }
    }
}
```

#### MORE EXAMPLES

#### # request example - dynamic

```
curl -i -X POST \
   -H "uuid:KgfMRZG3GWG9hRP7tHQz5qukD9T4Yg" \
   -H "token:5751d40d2e9ab5a163d772fbc6d8f7027180ad65f1345cf60534b5d0d1f04facd35271987f05e0c8c9e8b5ba6a881bbe7bcce7521d5d995bdf08bc2ea00bc7dd" \
   -H "Content-Type:application/json" \
   -H "mac:ZTRhMGQyY2M3YWJkNDAxN2NmMThjY2I1MTU1Yjk2ZjEzYWZlYjYxNTk2Y2ZkMmE5YTczNzhkMmE2ZmI0ZjE4MzRkODcyMTY2M2YyOTc1MGRhZjBkMzY5M2EyMTZkYzQ0" \
   -d \
'{"object_name":"test","table_relation_unique_id":$unique_id}' \
 'https://localhost/post/localhost_1/object'
```

#### # request example - regular api

```
curl -i -X GET \
   -H "uuid:KgfMRZG3GWG9hRP7tHQz5qukD9T4Yg" \
   -H "token:1ecbe474378a669d48560c0f4d875cf65bd73b06679dd9cd9d43f769aad8fb449206141189fd8cbef358daa8ebaaa6e017ba14c43567f42dac59a6266cf4292e" \
 'https://localhost/object/5'
```
