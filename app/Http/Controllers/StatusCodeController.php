<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class StatusCodeController extends BaseController
{
    /**
     * @author Leslie Kofi Brobbey
     * @desc This is the system defines status codes for sending responses
     * @param Integer|String $code - This represents the status code you want to be sent
     * @param String $custom_msg - Enter your custom message if ypu do not want the generic one
     * @param Array $data - The data you wish to be sent back to the view
     * @return HTTPResponseJson $response
     */
    public function statusCode(?int $code, ?string $custom_msg, ?array $data = [])
    {
        $response = [];
        $response['code'] = $code;
        $response['message'] = $custom_msg;
        if (count($data) > 0) {
            foreach ($data as $key => $val) {
                $response[$key] = $val;
            }
        }
        return response()->json($response, $code);
    }
}
