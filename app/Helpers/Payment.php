<?php


namespace   App\Helpers;

class Payment
{

public static function paymentVariables($order){
    return [
        "order_reference" => $order['order_reference'],
        "msisdn_to_send_to" =>  $order['msisdn_to_send_to']
    ];
}


public static function  replacePlaceHolders($variables, $messageTemp){
    foreach($variables as $key => $value){
        $messageTemp = str_replace('{{'.$key.'}}', $value, $messageTemp);
     }
  return $messageTemp;
}

}