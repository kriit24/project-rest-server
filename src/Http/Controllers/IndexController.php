<?php

namespace Project\RestServer\Http\Controllers;

use Project\RestServer\Component\Crypto;
use \Illuminate\Http\Request;

class IndexController
{
    public function MacGen(Request $request)
    {
        $SERVER_REQUEST_HEADERS = $request->server();
        $headers = array_change_key_case($SERVER_REQUEST_HEADERS, CASE_LOWER);
        $headers['content_type'] = $headers['content_type'] ?? null;

        //JSON
        if( preg_match('/application\/json/i', $headers['content_type']) )
            $data = $request->getContent();
        //POST
        else if( preg_match('/application\/x-www-form-urlencoded/i', $headers['content_type']) )
            $data = $request->getContent();
        //POST with files
        elseif (preg_match('/multipart\/form-data/i', $headers['content_type']))
            $data = $request->getContent();
        //GET
        else
            $data = $headers['query_string'];

        //die(pre($data));

        $user_key = config('auth.hash.key');
        $token['data'] = Crypto::init($user_key)->sign($data);

        if( !$token['data'] ) {

            $token['status'] = 'error';
            return response($token, 400);
        }

        $token['status'] = 'ok';
        return response()->json($token);
    }
    //
}
