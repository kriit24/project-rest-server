<?php

namespace App\Http\Requests;

use App\Component\Crypto;

class MacRequest
{
    public static function isValid($request, $data, $mac)
    {
        $uuid = $request->header('uuid');
        $token = $request->header('token');

        //die(pre($data));

        if (!$uuid || !$token || !$mac)
            return false;

        $server = $_SERVER;
        $server['HTTP_UUID'] = $uuid;
        $server['HTTP_TOKEN'] = $token;
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

        $user_key = config('auth.hash.key');
        $token['data'] = Crypto::init($user_key)->verify($data, $mac);

        if( $token['data'] == 'data_verified_error' ) {

            $token['status'] = 'error';
            $token['step'] = 'mac';
            return response($token, 400);
        }

        $token['status'] = 'ok';
        return response()->json($token);
    }
}
