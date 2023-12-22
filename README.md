# project-rest-server
Project Rest Server is REST-api server for mysql  
Its based on Laravel 10 framework

## Installation
This project using composer.
```
$ composer require kriit24/project-rest-server
```

## Clients for this server  
#### [react-native](https://www.npmjs.com/package/project-rest-client)

## Usage

```php
<?php

//route/api.php
$config = [
    //128 AES key
    //u can generate key - Project\RestServer\Component\Crypto::generateKey()
    'auth.hash.key' => '',
    'database.connections.CHANNEL_NAME' => config('database.connections.mysql'),
    'app.model.dir' => dirname(__DIR__) . '/app/Models',
    'app.model.namespace' => '\App\Models',
    //table to class name alias, lets say table is object, but in php u cannot make class object, so u add an alias objectT
    'app.model.alias' => ['object' => 'objectT'],
];
Project\RestServer\Config::set($config);
//pre($config);

Route::middleware([\App\Http\Middleware\AuthenticateOnceWithBasicAuth::class, Project\RestServer\Http\Middleware\VerifyPostMac::class])->group(function () {

    //make insert request
    Route::post('/post/{db}/{model}', function ($db, $model, Request $request) {

        if (Project\RestServer\Http\Requests\ValidateRequest::Broadcast($db, $model, $request)) {

            $event = new Project\RestServer\Broadcasting\DBBroadcast(
                Project\RestServer\Pusher\MysqlPush::class
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
https://arikonto.projectpartner.ee/api/fetch/arikonto/loan_application
```  
  
#### PARAMS
```php
$params = [
    'column' => ['column_1', 'column_2'],
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
