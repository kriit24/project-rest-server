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
        if( $request->get('mac') ){

            $server = $_SERVER;
            $server['http_uuid'] = $request->get('uuid');
            $server['http_token'] = $request->get('token');
            $server['http_mac'] = $request->get('mac');

            $request = new \Illuminate\Http\Request(
                server: $server,
                request: [
                    'query' => urldecode($request->get('query'))
                ]
            );
        }

        $SERVER_REQUEST_HEADERS = $request->server();
        $headers = array_change_key_case($SERVER_REQUEST_HEADERS, CASE_LOWER);
        $query_string = urldecode($request->get('query', ''));
        $http_mac = isset($headers['http_mac']) ? $headers['http_mac'] : null;

        if( !config('project.hash.key') ){

            return $next($request);
        }
        if (\Project\RestServer\Http\Requests\MacRequest::isValid($request, $query_string, $http_mac)) {

            return $next($request);
        }

        return response('Bad Request, uuid, token or mac is invalid', 400);
    }
}
