<?php

namespace Project\RestServer\Models;

use \Illuminate\Support\Facades\DB;

class Mysql extends DB
{
    protected $connection = 'mysql_dynamic';

    public static function init($connection)
    {
        $self = new Mysql();
        if (is_object($connection)) {

            $connection->setConnection($self->connection);
            return $connection;
        }
        else {

            return $self::connection($connection ?: $self->connection);
        }
    }

    public static function builder($q, $data)
    {
        return $q
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
            });
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
        if (empty($where))
            return $q;

        if (array_is_list($where)) {

            foreach ($where as $whereStmt) {

                $values = array_values($whereStmt);
                $column = $values[0];
                $operand = count($values) == 2 && $values[1] !== 'RAW' ? '=' : $values[1];
                $value = count($values) == 2 ? ($values[1] !== 'RAW' ? $values[1] : null) : $values[2];

                if (gettype($value) == 'string') {

                    if (
                        preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})T([0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{1,3})Z/i', $value)
                        ||
                        preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})T([0-9]{2}:[0-9]{2}:[0-9]{1,2})Z/i', $value)
                        ||
                        preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{1,2})Z/i', $value)
                        ||
                        preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{1,2})/i', $value)
                    ) {

                        $dt = date('Y-m-d H:i:s', strtotime($value));
                        $value = $dt;
                    }
                    else {

                        if (
                            preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $value)
                            ||
                            preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})/i', $value)
                        ) {

                            $dt = date('Y-m-d', strtotime($value));
                            $value = $dt;
                        }
                    }
                }

                if (strtoupper($operand) == 'IN') {

                    $q->whereIn($column, $value);
                }
                elseif (strtoupper($operand) == 'NOT_IN') {

                    $q->whereNotIn($column, $value);
                }
                elseif (strtoupper($operand) == 'RAW') {

                    $q->whereRaw($column, $value);
                }
                else {

                    $q->where($column, $operand, $value);
                }
            }
        }
        else{

            foreach(array_keys($where) as $column){

                $operand = '=';
                $value = $where[$column];
                $q->where($column, $operand, $value);
            }
        }
        return $q;
    }

    public static function getConn()
    {
        return (new self())->connection;
    }
}
