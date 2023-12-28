<?php

namespace Project\RestServer\Models;

use \Illuminate\Support\Facades\DB;

class Mysql extends DB
{
    protected $connection = 'mysql_dynamic';

    public function init($connection)
    {
        if (is_object($connection)) {

            return $connection;
        }
        else {

            $this->connection = $connection;
        }
        return $this;
    }

    public static function getDataValue($data, $type = 'where', $column = null)
    {
        if ($column) {

            if (is_array($data[$type])) {

                foreach ($data[$type] as $row) {

                    if ($row[0] == $column) {

                        return end($row);
                    }
                }
            }
        }
        else {

            if (count($data[$type]) != count($data[$type], COUNT_RECURSIVE)) {

                return array_map(function ($row) {
                    return end($row);
                }, $data[$type]);
            }
        }

        return $data[$type];
    }

    public static function whereArray($q, $where)
    {
        if( empty($where) )
            return $q;

        foreach ($where as $whereStmt) {

            $values = array_values($whereStmt);
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
    }
}
