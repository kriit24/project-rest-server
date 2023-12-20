<?php


Route::middleware([\App\Http\Middleware\Authenticate::class, \App\Http\Middleware\VerifyPostMac::class])->group(function () {

    //make insert request
    Route::post('/post/{db}/{model}', function ($db, $model, Request $request) {

        //FOR TESTING
        return response(['status' => 'ok', 'count' => !empty([]) ? 1 : 0, 'data' => []]);

        if (\App\Http\Requests\ValidateRequest::Broadcast($db, $model, $request)) {

            $event = new \App\Broadcasting\DBBroadcast(
                \App\Pusher\MysqlPush::class
            );
            $data = $event->broadcast($db, $model, $request);

            return response(['status' => 'ok', 'count' => !empty($data) ? 1 : 0, 'data' => $data]);
        }
        return response(['status' => 'error', 'message' => 'POST error:' . \App\Http\Requests\ValidateRequest::getError()], 406);
    });

    //make update request
    Route::post('/put/{db}/{model}', function ($db, $model, Request $request) {

        //FOR TESTING
        return response(['status' => 'ok', 'count' => !empty([]) ? 1 : 0, 'data' => []]);

        if (\App\Http\Requests\ValidateRequest::Broadcast($db, $model, $request)) {

            $event = new \App\Broadcasting\DBBroadcast(
                \App\Pusher\MysqlPush::class
            );
            $data = $event->broadcast($db, $model, $request);

            return response(['status' => 'ok', 'count' => !empty($data) ? 1 : 0, 'data' => $data]);
        }
        return response(['status' => 'error', 'message' => 'POST error:' . \App\Http\Requests\ValidateRequest::getError()], 406);
    });

    //make delete request
    Route::post('/delete/{db}/{model}', function ($db, $model, Request $request) {

        //FOR TESTING
        return response(['status' => 'ok', 'count' => !empty([]) ? 1 : 0, 'data' => []]);

        if (\App\Http\Requests\ValidateRequest::Delete($db, $model, $request)) {

            $event = new \App\Broadcasting\DBBroadcast(
                \App\Pusher\MysqlDelete::class
            );
            $data = $event->broadcast($db, $model, $request);

            return response(['status' => 'ok', 'count' => !empty($data) ? 1 : 0, 'data' => $data]);
        }
        return response(['status' => 'error', 'message' => 'DELETE error:' . \App\Http\Requests\ValidateRequest::getError()], 406);
    });

    //make get request
    Route::post('/fetch/{db}/{model}', function ($db, $model, Request $request) {

        if (\App\Http\Requests\ValidateRequest::Fetch($db, $model, $request)) {

            $event = new \App\Broadcasting\DBBroadcast(
                \App\Getter\MysqlGetter::class
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
        return response(['status' => 'error', 'message' => 'FETCH error:' . \App\Http\Requests\ValidateRequest::getError()], 406);
    });
});
