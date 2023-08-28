<?php

namespace App\Http\Controllers\UserAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    public function getDataFromGoogle($token)
    {
        $gclient = new \GuzzleHttp\Client();
        $response = '';
        try {
            $response = $gclient->request(
                'GET',
                'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . $token,
                [
                    'debug' => false,
                    'verify' => false
                ]
            );
        } catch (\Exception $e) {
            error_log($e->getMessage(), 0);
            return ['status' => 'error', 'code' => 401, 'msg' => 'Invalid Token', 'data' => ["error" => "invalid_request", "error_description" => "Invalid Credentials"]];
        }

        if (!\is_object($response)) {
            return ['status' => 'error', 'code' => 401, 'msg' => 'Error with Google Auth', 'data' => ["error" => "invalid_request", "error_description" => "Invalid Credentials"]];
            // return false;
        }
        $response_code = $response->getStatusCode();
        // return $response->getBody();

        $response_level = substr($response_code, 0, 1);
        if ($response_level == '2') {
            $response = json_decode($response->getBody(), true);
            return ['status' => 'success', 'data' => $response];
            // return $response;
        } else {
            return ['status' => 'error', 'code' => 401, 'msg' => 'Invalid Token', 'data' => ["error" => "invalid_request", "error_description" => "Invalid Credentials"]];
            // return false;
        }
    }

}
