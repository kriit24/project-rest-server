<?php

namespace Project\RestServer\Getter;

use Project\RestServer\Models\Mysql;
use \Illuminate\Support\Facades\Config;
use \Illuminate\Support\Facades\DB;

class MysqlGetter
{
    public function __construct()
    {

    }

    public function trigger($payload, $request)
    {
        $data = $payload['message'];

        $class = \Project\RestServer\Config::model($payload['model'], \Project\RestServer\Models\Mysql::class);

        $reflectionClass = new \ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty('table');
        $table = $reflectionProperty->getValue(new $class);

        if (!$table) return [];

        $db = config('database.connections.' . $payload['db']);
        Config::set('database.connections.mysql_dynamic', $db);

        //die(pre($payload));
        if (isset($data['use'])) {

            $use = (array)$data['use'];
            $main = (new Mysql())
                ->init(new $class());

            $relationShipColumns = self::select($main, $data);

            foreach ($use as $val) {

                $main->$val($data);
            }
        }
        else {

            $main = (new Mysql())
                ->init(new $class());

            $relationShipColumns = self::select($main, $data);
        }

        $rows = $main
            ->when(1 == 1, function ($q) use ($data) {

                Mysql::whereArray($q, $data['where']);
            })
            ->when(isset($data['order']), function ($q) use ($data) {

                foreach ($data['order'] as $order) {

                    $q->orderBy($order[0], $order[1] ?? 'asc');
                }
            })
            ->when(isset($data['group']), function ($q) use ($data) {

                foreach ($data['group'] as $group) {

                    $q->groupBy(DB::raw($group));
                }
            })
            ->when(isset($data['offset']), function ($q) use ($data) {

                $q->skip($data['offset']);
            })
            ->when(isset($data['limit']), function ($q) use ($data) {

                $q->take($data['limit']);
            })
            ->when(isset($payload['header']['debug']), function ($q) {

                die(pre(\Str::replaceArray('?', array_map(function ($val) {
                    return is_object($val) || is_array($val) ? "'" . print_r($val, true) . "'" : "'" . $val . "'";
                }, $q->getBindings()), $q->toSql()
                )));
            })
            ->cursor();


        //return array_map(function($row){ return array_merge($row, ['trigger' => 'fetch']); }, $rows->toArray());
        return (function () use ($rows, $data, $relationShipColumns) {

            foreach ($rows as $row) {

                $array = array_merge($row->toArray(), ['trigger' => 'fetch']);
                if (isset($data['join']) && !empty($data['join'])) {

                    foreach ($data['join'] as $with) {

                        $join_row = $row->$with;
                        $array[$with] = !empty($join_row) ? $join_row->toArray() : [];
                        if (isset($relationShipColumns[$with]) && !empty($relationShipColumns[$with]) && !empty($array[$with])) {

                            $row_2 = [];

                            foreach ($relationShipColumns[$with] as $withCol) {

                                $row_2 = array_merge_recursive($row_2, \Project\RestServer\Component\Replace::dotValue($withCol, $array[$with]));
                            }
                            $array[$with] = $row_2;
                        }
                        /*
                        $array[$with] = isset($relationShipColumns[$with]) && !empty($relationShipColumns[$with]) ? array_filter($array[$with], function ($v, $k) use ($with, $relationShipColumns) {
                            return in_array($k, $relationShipColumns[$with]) || is_array($v);
                        },
                            ARRAY_FILTER_USE_BOTH) : $array[$with];
                        */
                    }
                }

                yield $array;
            }
        })();
    }

    private static function select($main, $data)
    {

        $relationShipColumns = [];

        if (isset($data['column'])) {

            foreach ($data['column'] as $key => $column) {

                if (preg_match('/\./i', $column)) {

                    [$table, $col] = array_map('trim', explode('.', $column, 2));

                    if (in_array($table, (array)$data['join'])) {

                        $relationShipColumns[$table][] = $col;
                        unset($data['column'][$key]);
                    }
                }
            }

            if (!empty($data['column'])) {

                $main->select($data['column']);
            }
        }

        return $relationShipColumns;
    }
}
