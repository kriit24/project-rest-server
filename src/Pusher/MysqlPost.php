<?php

namespace Project\RestServer\Pusher;

use Project\RestServer\Models\Mysql;
use \Illuminate\Support\Facades\Config;

class MysqlPost
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

        $reflectionProperty = $reflectionClass->getProperty('primaryKey');
        $primaryKey = $reflectionProperty->getValue(new $class);

        $reflectionProperty = $reflectionClass->getProperty('fillable');
        $fillable = $reflectionProperty->getValue(new $class);

        $data = $payload['message'];

        //pre($data);

        $main = (new $class())->setConnection($payload['db']);

        $bindings = Mysql::getBindings($fillable, $data);

        if( !empty($bindings) ) {

            $id = $main
                ->create($bindings)
                ->$primaryKey;
            $d = [(object)[$primaryKey => $id]];
        }

        if (empty($d)) {

            return [];
        }

        $data[$primaryKey] = $id;

        return array_merge($data, ['trigger' => 'insert']);
    }
}
