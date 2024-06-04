<?php

namespace Project\RestServer\Http\Middleware;

use Closure;

//ini_set('precision', 17);

class VerifyPostMac
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string|null $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $SERVER_REQUEST_HEADERS = $request->server();
        $headers = array_change_key_case($SERVER_REQUEST_HEADERS, CASE_LOWER);
        $headers['content_type'] = $headers['content_type'] ?? null;

        $json = json_decode($request->getContent(), true );
        $data = $request->getContent();
        $http_mac = isset($headers['http_mac']) ? $headers['http_mac'] : null;

        //die(pre($json));

        //pre($request->getContent());
        //pre($data);
        //pre(0.000005);
        //pre((string)$data['object_radius']);
        //pre($http_mac);

        if( !config('auth.hash.key') ){

            return $next($request);
        }
        if (\Project\RestServer\Http\Requests\MacRequest::isValid($request, $data, $http_mac)) {

            return $next($request);
        }

        return response('Bad Request: uuid, token or mac is invalid', 400);
    }
}
