<?php

namespace Project\RestServer\Getter;

use Illuminate\Support\Facades\Config;
use Project\RestServer\Models\Mysql;

class MysqlLive
{
    static $event = [];

    public function __construct()
    {

    }

    public function trigger($payload, $request)
    {
        //die(pre($request->get('uuid')));
        $header = $payload['header'];
        $name = $request->get('uuid') . '_' . base64_encode(json_encode($header)) . '_' . base64_encode($request->get('query'));
        $full = $request->get('full', null) && $request->get('full') == 'true';
        $rows = [];

        $data = $payload['message'];
        $db = config('database.connections.' . $payload['db']);
        Config::set('database.connections.' . Mysql::getConn(), $db);

        if (!isset(self::$event[$name])) {

            $table = $payload['model'];
            $class = \Project\RestServer\Config::model($table, \Project\RestServer\Models\Mysql::class);

            $reflectionClass = new \ReflectionClass($class);
            $reflectionProperty = $reflectionClass->getProperty('primaryKey');
            $primary_key = $reflectionProperty->getValue(new $class);

            self::$event[$name] = ['table' => $table, 'primary_key' => $primary_key, 'updated_at' => date('d.m.Y H:i:s')];
            $rows = (new \Project\RestServer\Getter\MysqlGetter())->trigger($payload, $request);
        }
        else {

            $main = Mysql::init(Mysql::getConn());

            $last_table_change = date('01.01.1970 00:00:00');
            $last_updated_at = self::$event[$name]['updated_at'];

            $updated = $main->select("
            SELECT * FROM table_changes
            WHERE `table_changes_table_name` IN(?)
            AND table_changes_updated_at > ?
            ", [self::$event[$name]['table'], date('Y-m-d H:i:s', strtotime($last_updated_at))]);

            if (!empty($updated)) {

                $table_ids = [];
                $payload['message']['where'] = $payload['message']['where'] ?: [];

                foreach ($updated as $row) {

                    //get only current row
                    if ($row->table_changes_table_id) {

                        $table_ids[] = $row->table_changes_table_id;
                    }
                    //get all rows
                    if ($row->table_changes_table_id == 0) {

                        $table_ids = [];
                    }
                    $last_table_change = strtotime($row->table_changes_updated_at) > strtotime($last_table_change) ? $row->table_changes_updated_at : $last_table_change;
                }

                if (!empty($table_ids) && !$full)
                    $payload['message']['where'][] = [self::$event[$name]['primary_key'], 'IN', $table_ids];

                self::$event[$name]['updated_at'] = $last_table_change;
                $rows = (new \Project\RestServer\Getter\MysqlGetter())->trigger($payload, $request);
            }
            //pre($updated);
            //pre($payload);
            //die(pre(self::$event[$name]));
        }
        return $rows;
    }
}
