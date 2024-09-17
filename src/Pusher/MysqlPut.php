<?php

namespace Project\RestServer\Pusher;

use Project\RestServer\Models\Mysql;
use \Illuminate\Support\Facades\Config;

class MysqlPut
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

        if (!$payload['message']['where']) return [];

        $reflectionProperty = $reflectionClass->getProperty('primaryKey');
        $primaryKey = $reflectionProperty->getValue(new $class);

        $reflectionProperty = $reflectionClass->getProperty('fillable');
        $fillable = $reflectionProperty->getValue(new $class);

        $data = $payload['message'];

        //pre($data);

        $main = (new $class())->setConnection($payload['db']);

        $bindings = Mysql::getBindings($fillable, $data['set']);

        if (!empty($bindings)) {

            $rows = $main
                ->when($data['where'], function ($q) use ($data) {

                    Mysql::whereArray($q, $data['where']);
                })
                //->when(true, fn($q) => die(pre(str_replace_array('?', array_map(function($val){ return "'".$val."'" ;}, $q->getBindings()), $q->toSql()))))
                ->get();

            foreach ($rows as $row) {

                $row->fill($data['set'])->save();
            }

            $d = !empty($rows) ? array_map(fn($val) => (object)[$primaryKey => $val], $rows->pluck($primaryKey)->toArray()) : [];
        }

        if (empty($d)) {

            return [];
        }

        $data['set'][$primaryKey] = count($d) == 1 ? $rows[0]->$primaryKey : array_map(fn($row) => $row->$primaryKey, $d);

        return array_merge($data['set'], ['trigger' => 'update']);
    }
}
