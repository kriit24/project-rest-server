<?php

namespace Project\ProjectRestServer;

class Config
{
    /*
     *
     * [
     * 'auth.hash.key' => '',
     * 'database.connections.CHANNEL_NAME' => '',
     * ]
     */

    public static function set($config)
    {
        foreach($config as $key => $value){

            \Illuminate\Support\Facades\Config::set($key, $value);
        }
    }

    public static function model($name = null, $default = null)
    {

        $alias = ['object' => 'objectT'];
        if (isset($alias[$name]))
            $name = $alias[$name];

        if ($name) {

            $dir = dirname(__DIR__) . '/app/Models/';
            $file = $name . '.php';

            if (is_file($dir . $file)) {

                return '\App\Models\\' . $name;
            }
            return $default;
        }

        //app/models/class
        $dir = dirname(__DIR__) . '/app/Models/';
        $filterMask = '*.php';
        $files = glob($dir . $filterMask, GLOB_BRACE);
        $ret = [];
        foreach ($files as $file) {

            $pathinfo = pathinfo($file);

            if (!in_array(strtolower($pathinfo['filename']), ['sql', 'mongo'])) {

                $ret[] = '\App\Models\\' . $pathinfo['filename'];
            }
        }

        return $ret;
    }
}
