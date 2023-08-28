<?php

namespace App\Http\Controllers;

use App\Models\LoginCountry;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Http\Request;
use Stevebauman\Location\Facades\Location;

class CountryController extends Controller
{
    public function search(Request $request)
    {
        $myArray = [];
        $gclient = new \GuzzleHttp\Client();
        $response = '';
        try {
            $response = $gclient->request(
                'GET',
                'https://restcountries.com/v3.1/name/' . $request->country,
                [
                    'debug' => false,
                    'verify' => false
                ]
            );
        } catch (\Exception $e) {
            error_log($e->getMessage(), 0);
        }

        if (!\is_object($response)) {
            return false;
        }
        $response_code = $response->getStatusCode();

        $response_level = substr($response_code, 0, 1);
        if ($response_level == '2') {
            $response = json_decode($response->getBody(), true);
            if (count($response) > 0) {
                foreach ($response as $res) {
                    $myArray[] = [$res['name']['common'], $res['cca2']];
                }
            }
            return response()->json($myArray);
        } else {
            return false;
        }
    }

    public function storeCountry($user_id)
    {
        $ip = FacadesRequest::ip();
        $ip = $ip == "127.0.0.1" ? '196.50.24.226' : $ip;
        $location = Location::get($ip);
        $continent = '';
        if($this->checkCountry(trim($location->countryCode), trim($user_id)) == false){
            if (isset($location->timezone)) {
                $arr = explode('/', $location->timezone);
                $continent = trim($arr[0]);
            }

            LoginCountry::create([
                'country' => trim($location->countryName),
                'countryCode' => trim($location->countryCode),
                'cityName' => trim($location->cityName),
                'regionName' => trim($location->regionName),
                'regionCode' => trim($location->regionCode),
                'zipCode' => trim($location->zipCode),
                'latitude' => trim($location->latitude),
                'longitude' => trim($location->longitude),
                'continent' => trim($continent),
                'user_id' => trim($user_id),
                'ip' => $ip
            ]);
        }
    }

    public function checkCountry($countryCode, $user_id){
        $location = LoginCountry::where('countryCode',$countryCode)->whereUser_id($user_id)->count();
        return $location > 0 ? true : false;
    }
}
