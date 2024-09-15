<?php

namespace Project\RestServer\Http\Requests;

use \Illuminate\Http\Request;
use \Illuminate\Support\Facades\Config;

class ToRequest
{
    public static function Post()
    {
        $request = new \Illuminate\Http\Request();
        $request->setMethod('POST');

        return $request;
    }

    public static function Get()
    {
        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        return $request;
    }
}
