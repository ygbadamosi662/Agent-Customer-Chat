<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\BulkAirtimeTransaction;
use App\Services\MerchantWebhook;
use \GuzzleHttp\Client;
use \GuzzleHttp\RequestOptions;
use Exception;
use App\Merchant;
use App\Transaction;



class ExecuteBulkAirtimeTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $transaction;
    public $merchant_name;
    public $cost_to_merchant;
    public $other_wallet;
    public $wallet;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BulkAirtimeTransaction $transaction)
    {
        $this->transaction = $transaction;
        $this->merchant_name = $this->transaction->merchant_name;
        $this->cost_to_merchant = $transaction->cost_to_merchant;
        $this->other_wallet =  $this->transaction->other_wallet;


    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $response = $this->execute();

        $payload = [
            'response' => json_encode($response),
            'processed_at' => date('Y-m-d H:i:s'),
            'status' => 'processed',
        ];

        if (isset($response->code)) {

            if ($response->code == 100) {
                $payload['disbursed_at'] = date('Y-m-d H:i:s');
                $payload['notes'] = NULL;
                $this->wallet = Merchant::where('id', '=', $this->transaction->bulk_airtime->merchant_id)->first()->other_wallet;
                $this->wallet -=  $this->cost_to_merchant;
                $this->chargeMerchantOtherWallet($this->transaction, $this->cost_to_merchant, $this->wallet);

            } else {
                $payload['status'] = 'failed';
                $payload['notes'] = 'Failed! Please retry again';
            }
        }
        else {
            $payload['status'] = 'failed';
            $payload['notes'] = 'Failed! Please retry again';
        }
        unset($this->transaction['other_wallet']);
        $this->transaction->update($payload);
    }

   
    private function execute(){

        if ($this->transaction->bulk_airtime->mock_mode != 'live') {
            return (object)['code' => 100, 'mocked' => true];
        }

       
        $network =[
            'MTN' => 15,
            'MTN AWUFU' => 20,
            'GLO' => 6,
            'Airtel' => 1,
            '9mobile' => 2 ,
            'Glo' => 6,
            'Celtel (Airtel)'=>1,
            'globacom' => 6
        ];

        $url = env('MOBILE_AIRTIME_URL');
        $phone = str_replace("234","0", $this->transaction->msisdn);
        $user_ref = $phone.'-'.date('ymdhis');
        $query_str = http_build_query(array('userid' => env('MOBILE_AIRTIME_USERNAME'), 'pass' => env('MOBILE_AIRTIME_PASS'),
         'network' => $network[$this->transaction->telco], 'phone' => $phone, 'amt' => $this->transaction->amount, 'user_ref' => $user_ref, 'jsn'=>'json'));
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$url}?{$query_str}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        $payload = [
            'phone' => $phone,
            'networK' => $network[$this->transaction->telco],
            'amt' => $this->transaction->amount,
            'user_ref' => $user_ref,
         ];
 
         $this->transaction->update(['payload' => json_encode($payload)]);

        return json_decode($response);
       
    }


    private function chargeMerchantOtherWallet($transaction, $cost_to_merchant, $closing_balance)
    {
        Transaction::create([
            'member_id' => $transaction->bulk_airtime->merchant_id,
            'order_id' => 0,
            'amount' => $cost_to_merchant,
            'closing_balance' => $closing_balance,
            'transaction_type' => 'out',
            'description' => 'Bulk airtime recharge for '.$transaction->msisdn,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Merchant::where('id', '=', $transaction->bulk_airtime->merchant_id)->update(['other_wallet' => $closing_balance]);
    }

    private function execute_old()
    {
        if ($this->transaction->bulk_airtime->mock_mode != 'live') {
            return (object)['response_code' => 200, 'mocked' => true];
        }

        $payload = [
           'msisdn' => $this->transaction->msisdn,
           'request_id' => $this->transaction->reference,
           'product_id' => $this->transaction->telco_product_code,
           'user_id' => 'ogaranya101',
           'api_access_key' => 'b88e1214ca02a125390536fbb8998f1b74378fb4dd42f0bc6841df2f5c6aa544',
           'amount' => $this->transaction->amount
        ];

        $this->transaction->update(['payload' => json_encode($payload)]);

        try {
            $response = (new Client)->post('https://pixel.eazybiz.ng/buyvtu.php', [

                'headers' => [
                    'Accept' => 'application/json',
                ],

                RequestOptions::JSON => $payload
            ]);

            return json_decode($response->getBody()->getContents());
        } catch (Exception $e) {
            return MerchantWebhook::getErrorObject($e);
        }
    }
}
