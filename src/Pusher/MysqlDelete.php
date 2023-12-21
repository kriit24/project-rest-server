<?php

namespace Project\RestServer\Pusher;

use Project\RestServer\Models\Mysql;
use \Illuminate\Support\Facades\Config;

class MysqlDelete
{
    public function __construct()
    {

    }

    public function trigger($payload, $request)
    {
        $class = \Project\RestServer\Config::model($payload['model']);

        $reflectionClass = new \ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty('table');
        $table = $reflectionProperty->getValue(new $class);

        if (!$table) return [];

        $db = config('database.connections.' . $payload['db']);
        Config::set('database.connections.mysql_dynamic', $db);

        $reflectionProperty = $reflectionClass->getProperty('primaryKey');
        $primaryKey = $reflectionProperty->getValue(new $class);

        $reflectionProperty = $reflectionClass->getProperty('fillable');
        $fillable = $reflectionProperty->getValue(new $class);

        $dispatchesEvents = null;
        if ($reflectionClass->hasProperty('dispatchesEvents')) {

            $reflectionProperty = $reflectionClass->getProperty('dispatchesEvents');
            $dispatchesEvents = $reflectionProperty->getValue(new $class);
        }

        $data = $payload['message'];
        if ($data[$primaryKey]) {

            if (isset($dispatchesEvents['deleting'])) {

                $dispatcher = $dispatchesEvents['deleting'];
                new $dispatcher($data);
            }

            $d = Mysql::select("DELETE FROM `" . $table . "` WHERE `" . $primaryKey . "`= ? RETURNING " . implode(',', $fillable), [$data[$primaryKey]]);

            if (!empty($d)) {

                if (isset($dispatchesEvents['deleted'])) {

                    $dispatcher = $dispatchesEvents['deleted'];
                    new $dispatcher($data, $d);
                }

                return array_merge((array)$d[0], ['trigger' => 'delete']);
            }
        }

        return [];
    }
}
