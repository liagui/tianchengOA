<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $IlluminateResponse = 'Illuminate\Http\Response';
        $SymfonyResopnse = 'Symfony\Component\HttpFoundation\Response';
        $origin = $request->server('HTTP_ORIGIN') ? $request->server('HTTP_ORIGIN') : '';
        $headers = [
            'Access-Control-Max-Age'=>'1728000',

            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Credentials' => 'false', //true;
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, PATCH, DELETE',
            'Access-Control-Expose-Headers' => 'Content-Type, Access-Control-Allow-Origin, Access-Control-Allow-Headers, X-Requested-By, Access-Control-Allow-Methods',
            'Access-Control-Allow-Headers'=>'Authorization'
        ];
 
        if ($response instanceof $IlluminateResponse) {
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }
            return $response;
        }
 
        if ($response instanceof $SymfonyResopnse) {
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
            return $response;
        }
        return $response;

        // return $next($request)
        // ->header('Access-Control-Max-Age', '1728000')
        // ->header('Access-Control-Allow-Credentials', 'false')
        // ->header('Access-Control-Allow-Origin', '*')
        // ->header('Access-Control-Allow-Headers', 'Authorization')
        // ->header('Access-Control-Expose-Headers', 'Content-Type, Access-Control-Allow-Origin, Access-Control-Allow-Headers, X-Requested-By, Access-Control-Allow-Methods')
        // ->header('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, OPTIONS');
    }
}
