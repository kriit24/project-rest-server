# project-rest-server
Project Rest Server is REST-api server for mysql  
Its based on Laravel 10 framework

## Installation
This project using composer.
```
$ composer require kriit24/project-rest-server
```


```
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
ENGINE=InnoDB
;


CREATE TABLE IF NOT EXISTS `table_changes` (
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
#### php: coming soon


## NB!  
All the requests are POST methods because GET queries can distort data like umlauts and other special characters  
If u use json_encode to compile data then allways use it with option JSON_UNESCAPED_UNICODE

## Usage

```php
<?php

//route/api.php
$config = [
    //128 AES key
    //u can generate key - Project\RestServer\Component\Crypto::generateKey()
    'auth.hash.key' => '',//if u use dynamic hash key then leave empty. IF auth key is empty then mac is not checked
    'database.connections.CHANNEL_NAME' => config('database.connections.mysql'),
    'app.model.dir' => dirname(__DIR__) . '/app/Models',
    'app.model.namespace' => '\App\Models',
    //table to class name alias, lets say table is object, but in php u cannot make class object, so u add an alias objectT
    'app.model.alias' => ['object' => 'objectT'],
];
Project\RestServer\Config::set($config);
//pre($config);

//SET auth.hash.key for each user after Auth is done
//OR use stationary in $config
//app/Http/Middleware/Authenticate.php
/*
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
            config(['auth.hash.key' => $user_key]);
            return $next($request);
        }

        return response('Unauthorized (' . $step . ').', 401);
    }
}
 */

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

            if ($data instanceof Generator || $data instanceof Closure || $data instanceof \Illuminate\Support\LazyCollection) {

                $rows = iterator_to_array($data);
            }
            else {

                $rows = $data;
            }

            return response(['status' => 'ok', 'count' => count($rows), 'data' => $rows]);
        }
        return response(['status' => 'error', 'message' => 'FETCH error:' . Project\RestServer\Http\Requests\ValidateRequest::getError()], 406);
    });
    
    //make event request for live
    Route::post('/event/{db}/{model}', function ($db, $model, Request $request) {

        $ret = \Project\RestServer\Component\Event::handle($db, $model, $request);
        return response($ret);
    });
});


Route::middleware([\App\Http\Middleware\Authenticate::class, Project\RestServer\Http\Middleware\VerifyGetMac::class])->group(function () {

    //make live request
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


## QUERIES

### FETCH  
```
curl -X POST 
-H "Content-Type: application/json"
-H "mac: NWM3NzIzMjFjYjQ0ZmQ4MGZjODg5MTg5OTkxMWYwYWRhYWFlOTNlMjUzNWE2MTY3OTAxNGM4M2MzNjY3OWY4MWRjYzA1MjQ2ZGZhNTc3OWVhNzc3MjMyNTRiZGNiY2Ew"
-d '{"column":null,"join":null,"use":null,"where":null,"group":null,"order":null,"limit":null,"offset":null}'
https://your.api.domain/api/fetch/{database}/{model}
```  
  
#### PARAMS
```php
$params = [
    //get all columns
    'column' => null,
    //get current columns
    //'column' => ['column_1', 'column_2'],
    //get all parent columns also "use" columns
    //'column' => ['*'],
    //get join columns 
    //'column' => ['child_table.child_table_column', 'child_table_1.child_table_2.child_table_column'],
    //join sibling data 
    'join' => ['join_1', 'join_2'],
    //use query as query builder
    'use' => ['use_1', 'use_2'],
    //u can use operands line IN, NOT_IN AND RAW
    //if operand is RAW then first argument is used as where statement
    'where' => [['object_id', '=', 1], ['object_id BETWEEN 1 AND 2', 'RAW']],
    'group' => ["object_id", "object_name"],
    'order' => [["object_id", "DESC"], ["object_name", "ASC"]],
    'limit' => 1,
    'offset' => 10,
];
```

### POST
```
curl -X POST 
-H "Content-Type: application/json"
-H "mac: NWM3NzIzMjFjYjQ0ZmQ4MGZjODg5MTg5OTkxMWYwYWRhYWFlOTNlMjUzNWE2MTY3OTAxNGM4M2MzNjY3OWY4MWRjYzA1MjQ2ZGZhNTc3OWVhNzc3MjMyNTRiZGNiY2Ew"
-d '{"column_name":value}'
https://your.api.domain/api/post/{database}/{model}
```  

### PUT
```
curl -X POST 
-H "Content-Type: application/json"
-H "mac: NWM3NzIzMjFjYjQ0ZmQ4MGZjODg5MTg5OTkxMWYwYWRhYWFlOTNlMjUzNWE2MTY3OTAxNGM4M2MzNjY3OWY4MWRjYzA1MjQ2ZGZhNTc3OWVhNzc3MjMyNTRiZGNiY2Ew"
-d '{"set":{"column_name" : value},"where":[["column_name", "=", "value"]]}'
https://your.api.domain/api/put/{database}/{model}
```

### PUSH
```
curl -X POST 
-H "Content-Type: application/json"
-H "mac: NWM3NzIzMjFjYjQ0ZmQ4MGZjODg5MTg5OTkxMWYwYWRhYWFlOTNlMjUzNWE2MTY3OTAxNGM4M2MzNjY3OWY4MWRjYzA1MjQ2ZGZhNTc3OWVhNzc3MjMyNTRiZGNiY2Ew"
-d '{"primary_id":value,"column_name":value}'
https://your.api.domain/api/put/{database}/{model}
```  


### DELETE
```
curl -X POST 
-H "Content-Type: application/json"
-H "mac: NWM3NzIzMjFjYjQ0ZmQ4MGZjODg5MTg5OTkxMWYwYWRhYWFlOTNlMjUzNWE2MTY3OTAxNGM4M2MzNjY3OWY4MWRjYzA1MjQ2ZGZhNTc3OWVhNzc3MjMyNTRiZGNiY2Ew"
-d '{"parimary_id":value,"column_name":value}'
https://your.api.domain/api/delete/{database}/{model}
``` 
