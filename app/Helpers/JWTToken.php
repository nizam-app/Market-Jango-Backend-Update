<?php

namespace App\Helpers;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTToken
{
    public static function registerToken($userType, $userId):string
    {
        $key = env('JWT_KEY');
        $payload = [
            'iss' => env('APP_NAME'),
            'iat' => time(),
            'exp' => time() + 60*60, // test purpose 60 minutes
            'userType' => $userType,
            'userId'=>$userId
        ];
        return JWT::encode($payload, $key, 'HS256');
    }
    public static function loginToken($userEmail, $id):string
    {
        $key = env('JWT_KEY');
        $payload = [
            'iss' => 'School Management System',
            'iat' => time(),
            'exp' => time() + 60 * 60*60,
            'userEmail' => $userEmail,
            'userId' => $id
        ];
        return JWT::encode($payload, $key, 'HS256');
    }
    public static function resetToken($userEmail, $id):string
    {
        $key = env('JWT_KEY');
        $payload = [
            'iss' => 'School Management System',
            'iat' => time(),
            'exp' => time() + 60*60, // test purpose 60 minutes
            'userEmail' => $userEmail,
            'userId' => $id
        ];
        return JWT::encode($payload, $key, 'HS256');
    }

    public static function VerifyToken($bearerToken):object|array
    {
        try {
            if (!$bearerToken) {
                return [
                    'status' => 'failed',
                    'message' => 'Unauthorized: No token provided'
                ];
            }
            //token key value define explode
            $parts = explode(' ', $bearerToken);
            if (count($parts) !== 2 || $parts[0] !== 'Bearer') {
                return [
                    'status' => 'failed',
                    'message' => 'Unauthorized: Invalid token format'
                ];
            }

            $token = $parts[1];
            $key = env('JWT_KEY');
            $decode = JWT::decode($token, new Key($key, 'HS256'));
            return (array)$decode;

        } catch (Exception $e) {
            return [
                'status' => 'failed',
                'message' => 'Unauthorized: Invalid token'
            ];
        }
    }
}
