<?php

namespace Project\RestServer\Getter;

use Illuminate\Support\Facades\Config;
use Project\RestServer\Models\Mysql;

class MysqlGetter
{
    public function __construct()
    {

    }

    public function trigger($payload, $request)
    {
        $data = $payload['message'];
        if (isset($data['join']) && $data['join'] && !isset($data['with'])) $data['with'] = $data['join'];

        $class = \Project\RestServer\Config::model($payload['model']);

        $reflectionClass = new \ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty('table');
        $table = $reflectionProperty->getValue(new $class);

        if (!$table) return [];

        $main = (new $class())->setConnection($payload['db']);
        [$main, $relationShipColumns] = self::select($main, $data);

        //die(pre($payload));
        if (isset($data['use'])) {

            $use = (array)$data['use'];
            foreach ($use as $val) {

                $main = $main->$val($data);
            }
        }

        $rows = Mysql::builder($main, $data)
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
                if (isset($data['with']) && !empty($data['with'])) {

                    foreach ($data['with'] as $with) {

                        $join_row = $row->$with;
                        $array[$with] = !empty($join_row) ? $join_row->toArray() : [];
                        if (isset($relationShipColumns[$with]) && !empty($relationShipColumns[$with]) && !empty($array[$with])) {

                            $row_2 = [];

                            foreach ($relationShipColumns[$with] as $withCol) {

                                if (array_is_list($array[$with])) {

                                    foreach ($array[$with] as $k => $v) {

                                        $row_2[$k] = array_merge_recursive((isset($row_2[$k]) ? $row_2[$k] : []), \Project\RestServer\Component\Replace::dotValue($withCol, $v));
                                    }
                                }
                                else {

                                    $row_2 = array_merge_recursive($row_2, \Project\RestServer\Component\Replace::dotValue($withCol, $array[$with]));
                                }
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

                    if (in_array($table, (array)$data['with'])) {

                        $relationShipColumns[$table][] = $col;
                        unset($data['column'][$key]);
                    }
                }
            }

            if (!empty($data['column'])) {

                $main = $main->select($data['column']);
            }
        }

        return [$main, $relationShipColumns];
    }
}
