<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;

class TokenAuthClient
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
        if(!($request->header('Authorization'))){
            return response()->json([
                'status' => 400,
                'msg' => 'Unauthorized access Authorization Value is missing',
                'data' => []
            ], 400);
        }
        if(!($request->header('authAccess'))){
            return response()->json([
                'status' => 400,
                'msg' => 'Unauthorized access authAccess is missing',
                'data' => []
            ], 400);
        }
        $jwtAuthToken=substr($request->header('Authorization'), 7);
        
        if(!($jwt = $jwtAuthToken)){
            return response()->json([
                'status' => 400,
                'msg' => 'Unauthorized access Authorization token missing',
                'data' => []
            ], 400);
        }
        $JWTkey = env('JWT_SECRET', '');
        if($JWTkey == ''){
            return response()->json([
                'status' => 401,
                'msg' => 'Error In Jwt Client Secret',
                'data' => []
            ], 401);
        }
        try {
            $decoded = JWT::decode($jwt, $JWTkey, array('HS256'));
        }catch (SignatureInvalidException $e) {
            return response()->json([
                'status' => 401,
                'msg' => 'JWT Signature verification failed',
                'data' => []
            ], 401);
        }catch (ExpiredException $e) {
            return response()->json([
                'status' => 401,
                'msg' => 'JWT Authorization Token expired',
                'data' => []
            ], 401);
        }catch (BeforeValidException $e) {
            return response()->json([
                'status' => 401,
                'msg' => 'Invalid jwt before call',
                'data' => []
            ], 401);
        }
        $request->merge(['_authClient' => $decoded->sub]);
        return $next($request);
    }


}
