# project-rest-server
Project Rest Server is REST-api server for mysql  
Its based on Laravel 9+ framework

## Installation
#### # download from packagist
```
composer require kriit24/project-rest-server
```

#### # install package

```
php artisan project-rest-server:install
```

## Client for server  
#### react-native: [project-rest-client](https://www.npmjs.com/package/project-rest-client)


## EASY TO USE

```
//API dynamic request
Route::get('/fetch/{model_name}/{id?}', function (Request $request, $model, $id = null) {

    $primary_key = app("App\\Models\\" . $modelName)->getKeyName();    

    $to_request = \Project\RestServer\Http\Requests\ToRequest::Get();
    $to_request->request->add(['where' => array_filter([$primary_key => $id])]);    
    
    $event = new Project\RestServer\Broadcasting\DBBroadcast(
        Project\RestServer\Getter\MysqlGetter::class
    );
    $data = $event->fetch('channel_name', $model, $to_request);
    return response(['status' => 'ok', 'count' => count($data), 'data' => $data]);
});
//https://localhost/fetch/object
//https://localhost/fetch/address
//https://localhost/fetch/client
//...
```


## DOCS


#### [config](https://github.com/kriit24/project-rest-server/tree/master/docs/config)
#### [relationship](https://github.com/kriit24/project-rest-server/tree/master/docs/relationship)
#### [usage](https://github.com/kriit24/project-rest-server/tree/master/docs/usage)
