<?php

namespace Project\RestServer\Http\Requests;

use Project\RestServer\Component\Crypto;
use \Illuminate\Http\Request;

class MacRequest
{
    public static function isValid($request, $data, $mac)
    {
        //die(pre($data));

        if (!$mac)
            return false;

        $server = $_SERVER;
        $server['HTTP_MAC'] = $mac;

        $request = new \Illuminate\Http\Request(
            server: $server,
            request: ['data' => $data]
        );

        $content = self::Mac($request);
        $h = $content;
        if (!empty($h)) {

            $statusCode = $h->getStatusCode();
        }
        else {

            $statusCode = 404;
        }

        if ($statusCode !== 200)
            return false;

        if (!is_array($content->getOriginalContent())) {
            return false;
        }

        if (strtoupper($content->getOriginalContent()['status']) == 'OK') {

            return true;
        }
        return false;
    }

    public static function Mac(Request $request)
    {
        $SERVER_REQUEST_HEADERS = $request->server();
        $headers = array_change_key_case($SERVER_REQUEST_HEADERS, CASE_LOWER);
        $mac = $headers['http_mac'];
        $data = $request->input('data') ?: $request->post('data');

        $user_key = config('project.hash.key');
        $ret = ['data' => Crypto::init($user_key)->verify($data, $mac)];

        if( $ret['data'] == 'data_verified_error' ) {

            $ret['status'] = 'error';
            $ret['step'] = 'mac';
            return response($ret, 400);
        }

        $ret['status'] = 'ok';
        return response()->json($ret);
    }
}
