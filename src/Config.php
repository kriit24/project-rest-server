<?php

namespace Project\RestServer;

class Config
{
    /*
     *
     * [
     * 'auth.hash.key' => '',
     * 'database.connections.CHANNEL_NAME' => '',
     * 'app.model.dir' => '/models url full path',
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
        $dir = config('app.model.dir');
        $namespace = config('app.model.namespace');
        $alias = config('app.model.alias');
        if (isset($alias[$name]))
            $name = $alias[$name];

        if ($name) {

            $file = $name . '.php';

            if (is_file($dir . '/' . $file)) {

                return $namespace . '\\' . $name;
            }
            return $default;
        }

        //app/models/class
        $filterMask = '*.php';
        $files = glob($dir . '/' . $filterMask, GLOB_BRACE);
        $ret = [];
        foreach ($files as $file) {

            $pathinfo = pathinfo($file);

            if (!in_array(strtolower($pathinfo['filename']), ['sql', 'mongo'])) {

                $ret[] = $namespace . '\\' . $pathinfo['filename'];
            }
        }

        return $ret;
    }
}
