## [<<<](https://github.com/kriit24/project-rest-server/) Usage

#### # get user key from auth

```
//SET project.hash.key for each user after Auth is done
//OR use stationary in config/project.php

$user_key = (new Auth())->UserData('user_key');//logged in user session
config(['project.hash.key' => $user_key]);
 ```

#### # DYNAMIC routes for react native

```
Route::middleware([\App\Http\Middleware\AuthenticateOnceWithBasicAuth::class, Project\RestServer\Http\Middleware\VerifyPostMac::class])->group(function () {

    //make insert request
    Route::post('/post/{db}/{model}', function ($db, $model, Request $request) {

        if (Project\RestServer\Http\Requests\ValidateRequest::Broadcast($db, $model, $request)) {

            $event = new Project\RestServer\Broadcasting\DBBroadcast(
                Project\RestServer\Pusher\MysqlPost::class
            );
            $data = $event->post($db, $model, $request);

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
            $data = $event->post($db, $model, $request);

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
            $data = $event->post($db, $model, $request);

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
            $data = $event->delete($db, $model, $request);

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
    
    //make event request for live
    Route::post('/event/{db}/{model}', function ($db, $model, Request $request) {

        $ret = \Project\RestServer\Component\Event::handle($db, $model, $request);
        return response($ret);
    });
});

```

#### # API routes 

```
Route::middleware([\App\Http\Middleware\Authenticate::class])->group(function () {

    $eventGet = new Project\RestServer\Broadcasting\DBBroadcast(
        Project\RestServer\Getter\MysqlGetter::class
    );
    $eventPost = new Project\RestServer\Broadcasting\DBBroadcast(
        Project\RestServer\Pusher\MysqlPost::class
    );
    $eventPut = new Project\RestServer\Broadcasting\DBBroadcast(
        Project\RestServer\Pusher\MysqlPut::class
    );
    $eventPush = new Project\RestServer\Broadcasting\DBBroadcast(
        Project\RestServer\Pusher\MysqlPush::class
    );
    $eventDelete = new Project\RestServer\Broadcasting\DBBroadcast(
        Project\RestServer\Pusher\MysqlDelete::class
    );

    Route::prefix('object')->group(function () use ($eventPush, $eventDelete, $eventPost, $eventPut, $eventGet) {

        //make object API request
        Route::get('/{object_id?}', function (Request $request, $object_id = null) use ($eventGet) {

            $to_request = \Project\RestServer\Http\Requests\ToRequest::Get();
            $to_request->request->add(['with' => ['address']]);
            $to_request->request->add(['where' => array_filter(['object_id' => $object_id])]);
            $data = $eventGet->fetch('haldus_projectpartner_ee', \App\Models\objectT::class, $to_request);

            return response(['status' => 'ok', 'count' => count($data), 'data' => $data]);
        });

        //make object API delete request
        Route::get('/delete/{object_id}', function (Request $request, $object_id) use ($eventDelete, $eventGet) {

            $to_request = \Project\RestServer\Http\Requests\ToRequest::Get();
            $to_request->request->add(['where' => ['object_id' => $object_id]]);
            $data = $eventDelete->delete('haldus_projectpartner_ee', \App\Models\objectT::class, $to_request);

            return response(['status' => 'ok', 'count' => count($data), 'data' => $data]);
        });

        //make insert request
        Route::post('/', function (Request $request) use ($eventPut, $eventPost) {

            $to_request = \Project\RestServer\Http\Requests\ToRequest::Post();
            $to_request->request->add($request->all());
            $data = $eventPost->post('haldus_projectpartner_ee', \App\Models\objectT::class, $to_request);

            return response(['status' => 'ok', 'count' => count($data), 'data' => $data]);
        });

        //make push request
        Route::post('/push/{object_id?}', function (Request $request, $object_id = null) use ($eventPush) {

            $to_request = \Project\RestServer\Http\Requests\ToRequest::Post();
            $to_request->request->add(['set' => $request->all()]);
            $to_request->request->add(['where' => array_filter(['object_id' => $object_id])]);
            $data = $eventPush->post('haldus_projectpartner_ee', \App\Models\objectT::class, $to_request);

            return response(['status' => 'ok', 'count' => count($data), 'data' => $data]);
        });

        //make update request
        Route::post('/{object_id}', function (Request $request, $object_id) use ($eventPut) {

            $to_request = \Project\RestServer\Http\Requests\ToRequest::Post();
            $to_request->request->add(['set' => $request->all()]);
            $to_request->request->add(['where' => ['object_id' => $object_id]]);
            $data = $eventPut->post('haldus_projectpartner_ee', \App\Models\objectT::class, $to_request);

            return response(['status' => 'ok', 'count' => count($data), 'data' => $data]);
        });
    });
});

```

#### # LIVE routes
```
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
```

#### # MAC generator

```
Route::middleware([\App\Http\Middleware\AuthenticateOnceWithBasicAuth::class])->group(function () {

    if (env('APP_DEBUG')) {

        //u can generate mac key for testing purposes
        Route::post('/mac/gen', [\Project\RestServer\Http\Controllers\IndexController::class, 'MacGen']);
    }
});
```


#### # PARAMS
```
//GET,LIVE
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
    'with' => ['join_1', 'join_2'],
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
//POST
$params = [
    'column' => 'value',
];
//PUT,PUSH
$params = [
    'set' => [
        'column' => 'value'
    ],
    'where' => [
        ['column', 'operator', 'value']
    ],
];
//DELETE
$params = [
    'where' => [
        ['column', 'operator', 'value']
    ],
];
```

#### # request example

```
curl -i -X GET \
   -H "uuid:KgfMRZG3GWG9hRP7tHQz5qukD9T4Yg" \
   -H "token:1ecbe474378a669d48560c0f4d875cf65bd73b06679dd9cd9d43f769aad8fb449206141189fd8cbef358daa8ebaaa6e017ba14c43567f42dac59a6266cf4292e" \
 'https://localhost/object/5'
```

#### # live example

```
<script type="text/javascript">
    const uuid = 'KgfMRZG3GWG9hRP7tHQz5qukD9T4Yg';
    const token = '92ff8dcf2223508fe3c1228ac39f5d68af9eb38ea274d90f87d9262e25c9e2e8d78eb595083ff69dc7a80acd16494b68b7a85e220b6596cba0dfac5e4ff373b9';
    const query = encodeURIComponent('{"column":null,"with":["address"],"use":null,"where":null,"group":null,"order":null,"limit":10,"offset":0}');

    //full - get full json (when false then u get only updated rows)
    let url = 'https://localhost/live/localhost_1/object?full=false&uuid=' + uuid + '&token=' + token +
        '&query=' + query;

    const evtSource = new EventSource(url);

    evtSource.addEventListener("message", (e) => {
        console.log(JSON.parse(e.data));
    });
</script>

```

#### # react native example

```
curl -i -X POST \
   -H "uuid:KgfMRZG3GWG9hRP7tHQz5qukD9T4Yg" \
   -H "token:1ecbe474378a669d48560c0f4d875cf65bd73b06679dd9cd9d43f769aad8fb449206141189fd8cbef358daa8ebaaa6e017ba14c43567f42dac59a6266cf4292e" \
   -H "Content-Type:application/json" \
   -H "mac:MWRkYzgzZTE0NDI1MTIxOTI1M2JiYWI1NzllZWY2MWU2NDIzYjcwZjFhZDE5ZTAxODA0MzRmMTI4ODBiNTkwNTcyNmQ4NWVjMjg4N2NiOGEyMTVjMGRiZWViZDA1OGY5" \
   -d \
'{"column":null,"with":["address"],"use":null,"where":[["object_id","=", 5]],"group":null,"order":null,"limit":10,"offset":0}' \
 'https://localhost/fetch/localhost_1/object'
```
