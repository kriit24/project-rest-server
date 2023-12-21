<?php

namespace Project\RestServer\Http\Requests;

use \Illuminate\Http\Request;
use \Illuminate\Support\Facades\Config;

class ValidateRequest
{
    private static $error = [];

    public static function Broadcast($db, $model, Request $request)
    {
        if (
            (string)$db
            && (string)$model
            && !empty($request->post())
        ) {

            $conf = config('database.connections');
            if (!isset($conf[$db])) {

                self::$error[] = 'db "' . $db . '" does not exists';
                return false;
            }

            /*
            $post = $request->post();
            if (!isset($post[$model . '_id'])) {

                self::$error[] = 'db "' . $db . '" primary key "' . $model . '_id" is missing';
                return false;
            }
            */

            $class = \Project\RestServer\Config::model($model);

            if (!$class) {

                self::$error[] = 'model is missing';
                return false;
            }

            $reflectionClass = new \ReflectionClass($class);
            $reflectionProperty = $reflectionClass->getProperty('table');
            $table = $reflectionProperty->getValue(new $class);

            if (!$table) {

                self::$error[] = 'model is missing';
                return false;
            }

            $reflectionProperty = $reflectionClass->getProperty('primaryKey');
            $primaryKey = $reflectionProperty->getValue(new $class);

            $reflectionProperty = $reflectionClass->getProperty('fillable');
            $fillable = $reflectionProperty->getValue(new $class);

            if( !in_array($primaryKey, $fillable) ){

                self::$error[] = 'primaryKey not in fillable list';
                return false;
            }

            return true;
        }

        $error = [];
        if (empty($request->post()))
            array_push($error, ['msg' => 'regular POST data in JSON format']);

        $error = array_filter(array_map(function ($row) {

            return is_array($row) && isset($row['msg']) ? $row['msg'] : null;
        }, $error));

        self::$error = $error;

        return false;
    }

    public static function Fetch($db, $model, Request $request)
    {
        if (
            (string)$db
            && (string)$model
        ) {

            $conf = config('database.connections');
            if (!isset($conf[$db])) {

                self::$error[] = 'db "' . $db . '" does not exists';
                return false;
            }

            $class = \Project\RestServer\Config::model($model);

            if (!$class) {

                self::$error[] = 'model is missing';
                return false;
            }

            return true;
        }

        self::$error[] = 'missing data db and/or model';

        return false;
    }

    public static function Post($db, $model, Request $request)
    {
        return self::Broadcast($db, $model, $request);
    }

    public static function Delete($db, $model, Request $request)
    {
        $class = \Project\RestServer\Config::model($model);

        if (!$class) {

            self::$error[] = 'model is missing';
            return false;
        }

        if (self::Broadcast($db, $model, $request)) {

            if ($request->post($model . '_id')) {

                return true;
            }
        }
        return false;
    }

    public static function getError()
    {
        return implode(', ', self::$error);
    }
}
