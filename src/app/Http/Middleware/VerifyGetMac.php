<?php

namespace App\Http\Middleware;

use Closure;

class VerifyGetMac
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
        $user_key =  \Project\ProjectRestServer\Config::get('user_key');
        config(['auth.hash.key' => $user_key]);

        $SERVER_REQUEST_HEADERS = $request->server();
        $headers = array_change_key_case($SERVER_REQUEST_HEADERS, CASE_LOWER);
        $query_string = isset($headers['query_string']) ? $headers['query_string'] : '';
        $http_mac = isset($headers['http_mac']) ? $headers['http_mac'] : null;
        if (\App\Http\Requests\MacRequest::isValid($request, $query_string, $http_mac)) {

            return $next($request);
        }

        return response('Bad Request, uuid, token or mac is invalid', 400);
    }
}
