<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Exception;

class CustomersController extends Controller
{
    public static function sendSMSV2($payload )
    {
        $phone_error = $processed_phone= [];

        foreach ($payload as $customer) {
         
            $param = [
                'messages' => [
                    [
                        'msisdn' => $customer['msisdn'],
                        'message' => $customer['message'],
                        'device_id' => $customer['device_id']
                    ]
                ]
            ];
           

            $client = new \GuzzleHttp\Client(['verify' => false]);

             try {
                    $response = $client->request('POST', 'https://channels.ogaranya.com/api/notification',
                    [\GuzzleHttp\RequestOptions::JSON => $param, 
                    'verify' => false, 
                    'headers' => ['Device-ID' =>$customer['device_id']]]);
                    $body  = json_decode($response->getBody());
                    \Log::info('Guzzle body: ',[$body]);

                    if(isset($body->response) && $body->response->status == 'SUCCESS') {
                        return true;
                    }

                } catch (\Exception $ex) {

                    $request = json_encode($param);
                    $result = json_encode($ex->getMessage());

                    \Log::info('Guzzle Exception Request: ' . $request);
                    \Log::info('Guzzle Exception: ' . $result);

                    return null;
                }

        }
       return [$phone_error, $processed_phone];
    }

}

