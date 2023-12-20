<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

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
}
