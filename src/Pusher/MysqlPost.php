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

        //pre($payload);

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
        if (isset($data['_id']))
            unset($data['_id']);
        if (isset($data['updated_at']))
            unset($data['updated_at']);
        if (isset($data['trigger']))
            unset($data['trigger']);

        //pre($data);

        $main = new $class();
        $arrayColumns = [];
        foreach ($fillable as $col) {

            if (isset($data[$col]) || array_key_exists($col, $data))
                $arrayColumns[] = $col;
        }
        $values = $data;

        $bindings = (function () use ($fillable, $dispatchesEvents, $main, $primaryKey, &$arrayColumns, $values) {

            if ($dispatchesEvents) {

                if (!isset($values[$primaryKey]) && isset($dispatchesEvents['inserting'])) {

                    $dispatcher = $dispatchesEvents['inserting'];
                    new $dispatcher($values);

                    foreach ($fillable as $col) {

                        if ((isset($values[$col]) || array_key_exists($col, $values)) && !in_array($col, $arrayColumns))
                            $arrayColumns[] = $col;
                    }
                }
            }

            $returnValues = [];
            if( !empty($values) ) {

                foreach ($arrayColumns as $col) {

                    if ($main->hasAttributeMutator($col)) {

                        $m = $main->setAttribute($col, $values[$col]);
                        $ms = $m->getAttributes();
                        $values[$col] = $ms[$col];
                    }

                    $val = $values[$col];
                    if ($val === null)
                        $returnValues[$col] = null;
                    else
                        $returnValues[$col] = $val;
                }
            }
            return $returnValues;

        })();

        if( !empty($arrayColumns) && !empty($bindings) ) {

            $id = Mysql::table($payload['model'])->insertGetId($bindings);
            $d = [(object)[$primaryKey => $id]];

            if ($dispatchesEvents && $id) {

                if (isset($dispatchesEvents['inserted'])) {

                    $dispatcher = $dispatchesEvents['inserted'];
                    new $dispatcher(Mysql::table($payload['model'])->where($primaryKey, $id)->first());
                }
            }
        }

        if (empty($d)) {

            return [];
        }

        $data[$primaryKey] = $id;

        return array_merge($data, ['trigger' => 'insert']);
    }
}
