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
            ->when(isset($data['where']), function ($q) use ($data) {

                foreach ($data['where'] as $where) {

                    $values = array_values($where);
                    $column = $values[0];
                    $operand = count($values) == 2 ? '=' : $values[1];
                    $value = count($values) == 2 ? $values[1] : $values[2];

                    if (gettype($value) == 'string') {

                        if (
                            preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})T([0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{1,3})Z/i', $value)
                            ||
                            preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})T([0-9]{2}:[0-9]{2}:[0-9]{1,2})Z/i', $value)
                        ) {

                            $dt = date('Y-m-d H:i:s', strtotime($value));
                            $value = $dt;
                        }

                        if (
                            preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $value)
                            ||
                            preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $value)
                        ) {

                            $dt = date('Y-m-d', strtotime($value));
                            $value = $dt;
                        }
                    }

                    if (strtoupper($operand) == 'IN') {

                        $q->whereIn($column, $value);
                    }
                    elseif (strtoupper($operand) == 'NOT_IN') {

                        $q->whereNotIn($column, $value);
                    }
                    elseif (strtoupper($operand) == 'RAW') {

                        $q->whereRaw($column);
                    }
                    else {

                        $q->where($column, $operand, $value);
                    }
                }

                return $q;
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

                die(pre(str_replace_array('?', array_map(function ($val) {
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

                            $row = [];

                            foreach ($relationShipColumns[$with] as $withCol) {

                                $row = array_merge_recursive($row, \Project\RestServer\Component\Replace::dotValue($withCol, $array[$with]));
                            }
                            $array[$with] = $row;
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
