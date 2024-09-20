<?php

namespace Project\RestServer;

class Config
{
    public static function model($name = null)
    {
        $dir = config('project.model.dir');
        $namespace = config('project.model.namespace');
        $alias = config('project.model.alias');
        if (isset($alias[$name]))
            $name = $alias[$name];

        if ($name) {

            $file = $name . '.php';

            if (is_file($dir . '/' . $file)) {

                return $namespace . '\\' . $name;
            }
            return $name;
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
