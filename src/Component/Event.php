<?php

namespace Project\RestServer\Component;

use Illuminate\Support\Facades\Config;
use Project\RestServer\Models\Mysql;

class Event
{
    public static function handle($db_name, $model, $request)
    {
        $class = \Project\RestServer\Config::model($model, \Project\RestServer\Models\Mysql::class);

        $reflectionClass = new \ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty('table');
        $table = $reflectionProperty->getValue(new $class);

        $reflectionProperty = $reflectionClass->getProperty('primaryKey');
        $primary_key = $reflectionProperty->getValue(new $class);
        $table_id = $request->get($primary_key);

        if (!$table) return ['status' => 'error'];

        $db = config('database.connections.' . $db_name);
        Config::set('database.connections.' . Mysql::getConn(), $db);

        $main = Mysql::init(Mysql::getConn());

        $main->select("CALL project_rest_event(?, ?)", [$model, $table_id]);

        return ['status' => 'ok'];
    }

    public static function message($event, $message, $id = null, $retry = null)
    {
        echo 'event: ' . $event . "\n";
        if( $id )
            echo 'id: ' . $id . "\n";
        if( $retry )
            echo 'retry: ' . $retry . "\n";
        echo 'data: ' . $message . "\n\n";

        @ob_end_flush();
        flush();
    }
}
