<?php

namespace Project\RestServer\Http\Middleware;

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
        $SERVER_REQUEST_HEADERS = $request->server();
        $headers = array_change_key_case($SERVER_REQUEST_HEADERS, CASE_LOWER);
        $query_string = isset($headers['query_string']) ? $headers['query_string'] : '';
        $http_mac = isset($headers['http_mac']) ? $headers['http_mac'] : null;
        if (\Project\RestServer\Http\Requests\MacRequest::isValid($request, $query_string, $http_mac)) {

            return $next($request);
        }

        return response('Bad Request, uuid, token or mac is invalid', 400);
    }
}
