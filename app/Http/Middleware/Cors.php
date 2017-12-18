<?php

namespace App\Http\Middleware;

use Closure;

class Cors {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $headers = [
            // Poner el servidor que origina la peticion aqui para permitirlo
            'Access-Control-Allow-Origin' => 'http://localhost:8080',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Authorization, X-Requested-With, Content-Type, DNT, X-Mx-ReqToken, Keep-Alive, User-Agent, If-Modified-Since, Cache-Control',
            'Access-Control-Allow-Credentials' => 'true',
        ];

        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);

            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }

            $response->headers->set('Access-Control-Max-Age', '86400');

            return $response;
        }

        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

}
