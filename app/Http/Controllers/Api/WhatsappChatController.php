<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Events\MessageSent;
use App\Events\NewComplaint;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;
use App\Whatsapp;
use App\ChatModels\WhatsappChat;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Exception;
use App\Merchant;
use Illuminate\Support\Str;
use App\Http\Controllers\Api\CustomersController;


class WhatsappChatController extends Controller
{
   public function getAuthUserMerchantMsisdn(){
      $merchant_id = Auth::user()->merchant_id;
     return Merchant::select('merchant_phone')->where(['id'=> $merchant_id])->first();

   }




    public function loadChat(Request $request)
    {

        $contacts = $request->json()->get('contacts');
        $messages = $request->json()->get('messages');
        $credential = $request->json()->get('credential');
        
        $customer_name = isset($contacts[0]['profile']['name']) ? $contacts[0]['profile']['name'] : '';
        $customer_msisdn = isset($messages[0]['from']) ? $messages[0]['from'] : '';
        $message = isset($messages[0]['text']['body']) ? $messages[0]['text']['body'] : '';
        $message_type = isset($messages[0]['type']) ? $messages[0]['type'] : '';
        $merchant_msisdn = isset($contacts[0]['wa_id']) ? $contacts[0]['wa_id'] : '';
        $whatsapp = WhatsappChat::create([
            'merchant_id' => $credential['merchant_id'],
            'merchant_name' => $credential['merchant_name'],
            'merchant_msisdn' => $merchant_msisdn,
            'customer_msisdn' => $customer_msisdn,
            'customer_msg_id' => $customer_msisdn,
            'customer_name' => $customer_name,
            'source' => 'customer',
            'message' => $message,
            'status' => 'received',
            'plan' => $credential['plan'],
            'meta' => json_encode(request()->all())
        ]);

        $currentDateTime = Carbon::now();
        $whatsapp->session_start = $currentDateTime->toDateTimeString();
        $whatsapp->session_end = $currentDateTime->addHours(20)->toDateTimeString();

        $whatsapp->complaint_id =Str::random(10);
        $whatsapp->save();

        $customer_has_existing_convo = WhatsappChat::where([
            'customer_msisdn' => $customer_msisdn,
            'merchant_id' => $whatsapp->merchant_id
            ])->whereIn('state', ['opened', 'pending'])->first();
        
        if (!is_null($customer_has_existing_convo)){
            $whatsapp->state=$customer_has_existing_convo->state;
            $whatsapp->complaint_id = $customer_has_existing_convo->complaint_id;
            $whatsapp->save();
        } else {
            $whatsapp->state="pending";
            $whatsapp->save();
        }

        try {
            broadcast(new MessageSent($whatsapp, $whatsapp->merchant_id));
            Log::info("event broadcasted");
        } catch(\Exception $e) {
            Log::info("event not broadcasted");
            Log::info($e);
            return _failed($e);
        }

        return _successful($whatsapp, 201);
        // Log::info($whatsapp_message);
        // $latestComplaint = WhatsappChat::where([
        //     'customer_msisdn' => $customer_msisdn,
        //     'merchant_id' => $credential['merchant_id']
        //     ])
        //     ->whereIn('state', ['opened', 'pending', 'closed'])
        //     ->latest()
        //     ->first();
        // $currentDateTime = Carbon::now();

        // if($latestComplaint && ($latestComplaint->state === "pending" || $latestComplaint->state === "opened")) {
        //     $sessionEnds = Carbon::parse($latestComplaint->session_end);
        //     if($sessionEnds < $currentDateTime) {
        //         $latestComplaint->session_start = $currentDateTime->toDateTimeString();
        //         // $latestComplaint->session_end = $currentDateTime->addHours(20)->toISOString();
        //         $latestComplaint->session_end = $currentDateTime->addSeconds(300000)->toDateTimeString();
        //         $latestComplaint->save();
        //     }
        //     Log::info($latestComplaint->session_end);
        //     Log::info("opened complaint session unchanged");
        //     $whatsapp_message->complaint_id = $latestComplaint->id;
        //     $whatsapp_message->save();

        //     try {
        //         broadcast(new MessageSent($whatsapp_message,$whatsapp_message->merchant_id));
        //     } catch(\Exception $e) {
        //         return _failed($e);
        //     }
        // }
        // if(!$latestComplaint || ($latestComplaint && $latestComplaint->state === "closed")) {
            
        //     if($latestComplaint) {
        //         $sessionEnds = Carbon::parse($latestComplaint->session_end);
        //         if($sessionEnds < $currentDateTime) {
        //             $whatsapp_message->session_start = $currentDateTime->toDateTimeString();
        //             // $whatsapp_message->session_end = $currentDateTime->addHours(20)->toISOString();
        //             $whatsapp_message->session_end = $currentDateTime->addSeconds(300000)->toDateTimeString();
        //             // $whatsapp_message->session_end = $currentDateTime->addSeconds(30000)->toDateTimeString();
        //             Log::info($whatsapp_message->session_end);
        //             Log::info("new complaint session changed");
        //         } else {
        //             $whatsapp_message->session_start = $latestComplaint->session_start;
        //             $whatsapp_message->session_end = $latestComplaint->session_end;
        //             Log::info($whatsapp_message->session_end);
        //             Log::info("new complaint session unchanged");
        //         }
        //     }
        //     if(!$latestComplaint) {
        //         $whatsapp_message->session_start = $currentDateTime->toDateTimeString();
        //         // $whatsapp_message->session_end = $currentDateTime->addHours(20)->toISOString();
        //         $whatsapp_message->session_end = $currentDateTime->addSeconds(300000)->toDateTimeString();

        //         // $whatsapp_message->session_end = $currentDateTime->addSeconds(300000)->toDateTimeString();
        //         Log::info($whatsapp_message->session_end);
        //         Log::info("empty new complaint session changed");
        //     }
            
        //     $whatsapp_message->complaint_id = $whatsapp_message->id;
        //     $whatsapp_message->state = "pending";
        //     $whatsapp_message->save();

        //     try {
        //         broadcast(new NewComplaint($credential['merchant_id']));
        //     } catch(\Exception $e) {
        //         Log::info($e);
        //         return _failed($e);
        //     }
        // }
        // return _successful("message received successfully");
    }

    public function getChats()
    {

        $state=request('state', 'all');
        if ($state=='expired'){
            $state='session_expired';
        }
        $customer = request('customer', '');

        $chatQuery=[];
        $merchant_details =$this->getAuthUserMerchantMsisdn();
        $merchant_msisdn= $merchant_details->merchant_phone;
        $chatQuery = WhatsappChat::selectRaw('max(customer_msisdn) as customer_msisdn')
        ->where(['merchant_msisdn'=>$merchant_msisdn]);

        $pending_chats =clone $chatQuery;
        $active_chats =clone $chatQuery;
        $closed_chats=clone $chatQuery;
        $session_expired_chats= clone $chatQuery;
        $all_chats= clone $chatQuery;

       
        if(request('count') === "true") {
            $count = $chatQuery->count();
            return _successful($count);
        } else {

            $chatQuery = WhatsappChat::select('id','merchant_id','merchant_msisdn','customer_msisdn',
            'customer_name','agent_name','session_start','session_end','agent_id','source','complaint_id',
            'complaint_id','state','message','updated_at','created_at','last_message')
            ->whereIn('id', function ($query) use ($merchant_msisdn, $state,$customer) {
                $query = $query->select(DB::raw('MAX(id)'))
                    ->from('whatsapp_chat')
                    ->where(['merchant_msisdn'=>$merchant_msisdn]);
                  
                    if ($customer && $state == 'expired'){
                        $query = $query->where('customer_msisdn', '<>', $customer);
                    }
                    if ($state != 'all' &&  $state != 'expired'){
                        $query = $query->where('state',$state);
                    }
                    if ($state == 'all'){
                        $query = $query->whereIn('state',["pending","opened"]);
                    }
                    if ($state == 'expired'){
                        $query = $query->where(['state'=>'session_expired']);
                    }
                    
                    $query= $query->orderBy('created_at', 'asc')->groupBy('customer_msisdn');
            });
            $chatQuery = $chatQuery->orderBy('created_at', 'desc')
            ->paginate(15);


            return _successful([
                "pending_count"=>$pending_chats->where(['state'=>'pending'])->groupBy('customer_msisdn')
                ->get()->count(),

                "active_count"=>$active_chats->where(['state'=>'opened'])->groupBy('customer_msisdn')
                ->get()->count(),

                "closed_count"=>$closed_chats->where(['state'=>'closed'])->groupBy('customer_msisdn')
                ->get()->count(),

                "expired"=>$session_expired_chats->where(['state'=>'session_expired'])->groupBy('customer_msisdn')
                ->get()->count(),

                "all"=>$all_chats->whereIn('state',["pending","opened"])->groupBy('customer_msisdn')
                ->get()->count(),

                "chats"=> $chatQuery

            ]);
        }
    }

   

    public function getComplaints($id, $state, $count)
    {

        $complaintQuery = WhatsappChat::whereIn('id', function ($query) {
            $query->select(DB::raw('MAX(id)'))
                ->from('whatsapp_chat')
                ->groupBy('complaint_id');
        });

        if($count === "true") {
            $complaintCount = $complaintQuery->count();
            return _successful($complaintCount);
        } else {
            $latestOnComplaints = $complaintQuery
                ->orderBy('created_at', 'desc')
                ->get()
                ->reject(function ($chat) use ($state) {
                    if ($chat->state) {
                        return $chat->state !== $state;
                    } else {
                        $complaint = WhatsappChat::where(['id' => $chat->complaint_id])->first();
                        return $complaint->state !== $state;
                    }
                });

            return _successful($latestOnComplaints);
        }
    }

    public function closeMessage($id)
    {
    
        $complaint = WhatsappChat::where(['state'=>  'opened', 'complaint_id'=>$id])->first();
        if(!is_null($complaint)) {
            WhatsappChat::where('complaint_id','LIKE',$id)->where(['state'=>  'opened'])->update([
                "state"=>"closed",
                "closed_by_id"=>Auth::user()->id,
                "closed_by"=>Auth::user()->name,
            ]);
            return _successful('Complaint closed succesfully');

        }
        _failed('You can close a only opened complaint');
    }

   

    public function agentReply(Request $request)
    {
        request()->validate([
            'message_id' => 'required',
            'customer_msisdn' => 'required',
            'message' => 'required',
        ]);

     
        $messages = WhatsappChat::where(['complaint_id' => request('message_id')])->first();
        if($messages->state === 'closed') {
            return _failed('This messages$messages is closed');
        }

        $currentDateTime = Carbon::now();
        $sessionEnds = Carbon::parse($messages->session_end);
        if($sessionEnds < $currentDateTime) {
            return _failed('This customers whatsapp session has ended,
            send an sms to this customer to initiate a new session');
        }

        $agent = Auth::user();
        $whatsapp_message = WhatsappChat::create([
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'complaint_id' => $messages->complaint_id,
            'merchant_id' => $agent->merchant_id,
            'merchant_name' => $messages->merchant_name,
            'merchant_msisdn' => $messages->merchant_msisdn,
            'customer_msisdn' => request('customer_msisdn'),
            'customer_name' => $messages->customer_name,
            'customer_msg_id' => $messages->customer_msg_id,
            'source' => 'agent',
            'message' => request('message'),
            'status' => 'sent',
            'plan' => 'charge_per_contact',
            'meta' => json_encode(request()->all())
        ]);

            if($messages->state === 'pending') {
                $messages->state = 'opened';
                $messages->save();
            }

           

            $this->broadcastReply($whatsapp_message, $agent);
            $sentToService = self::sendWhatsappChat($request);

            return _successful($whatsapp_message,201);
        
    }


    public function broadcastReply($whatsapp_message, $agent)
    {
        try {
            broadcast(new MessageSent($whatsapp_message, $agent->merchant_id))->toOthers();
            Log::info("event broadcasted to others");
            return _successful('Broadcast sent succesfully');
        } catch(\Exception $e) {
            Log::info("event not broadcasted");
            Log::info($e);
            return _failed($e);
        }
    }


    public function authPusher(Request $request)
    {
        $authData = Broadcast::auth($request);
        return response()->json($authData);
    }


    public static function getCredential($request, $merchant_msisdn)
    {
        $credential = Whatsapp::select('whatsapps.msisdn', 'whatsapps.plan', 'whatsapps.whatsapp_url',
             'whatsapps.whatsapp_token', 'whatsapps.whatsapp_secret', 'whatsapps.type',
             'whatsapps.service_endpoint',  'whatsapps.service_token', 'merchant.id as merchant_id',
              'merchant.merchant_name', 'merchant.merchant_phone', 'merchant.merchant_email',
               'merchant.webhook_url', 'whatsapps.service_provider')
            ->leftjoin('merchant', 'merchant.id', '=', 'whatsapps.merchant_id')
            ->where([
                'whatsapps.msisdn' => $merchant_msisdn,
                'whatsapps.status' => 'activated'
            ])
            ->first();
            
        return $credential;
    }


    
    public static function createWhatsappChat(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'customer_name' => 'required',
            "wa_id" => 'required',
            "from" => 'required',
            "id" => 'required',
            "message" => 'required',
            "type" => 'required',
            "timestamp" =>  'required',
            "merchant_msisdn"=>'required',
        ],
        [
            'customer_name.required'=> 'Customer name is required',
            'wa_id.required'=> 'Customer wa_id is required',
            'from.required'=> 'Customer phone is required',
            'body.required'=> 'Message is required',
            'type.required'=> 'Message type is required',
            'timestamp.required'=> 'Message timestamp is required',
        ]);

        if ($validator->fails()){
            return _failed($validator->messages(), 400);
        }
        
        $merchant_msisdn=request('merchant_msisdn');
        $merchant_exist_on_whatsapp_tbl=self::getCredential($request,$merchant_msisdn);

        if (is_null($merchant_exist_on_whatsapp_tbl)){
            return _failed('Merchant whatsapp integration has been setup', 400);
        }

        $whatsapp = WhatsappChat::create([
            'merchant_id' => $merchant_exist_on_whatsapp_tbl->merchant_id,
            'merchant_name' => $merchant_exist_on_whatsapp_tbl->merchant_name,
            'merchant_msisdn' => request('merchant_msisdn'),
            'customer_msisdn' => request('from'),
            'customer_msg_id' => request('from'),
            'customer_name' => request('customer_name'),
            'source' => 'customer',
            'message' => request('message'),
            'status' => 'pending',
            'meta' => json_encode(request()->all()),
            "state"=>'pending',
            "session_start"=> Carbon::now(),
        ]);

        $currentDateTime = Carbon::now();
        $whatsapp->session_start = $currentDateTime->toDateTimeString();
        $whatsapp->session_end = $currentDateTime->addHours(20)->toDateTimeString();

        $whatsapp->complaint_id =Str::random(10);
        $whatsapp->save();

        $customer_has_existing_convo = WhatsappChat::where([
            'customer_msisdn' => request('from'),
            'merchant_id' => $whatsapp->merchant_id
            ])->whereIn('state', ['opened', 'pending'])->first();
        
        if (!is_null($customer_has_existing_convo)){
            $whatsapp->state=$customer_has_existing_convo->state;
            $whatsapp->complaint_id = $customer_has_existing_convo->complaint_id;
            $whatsapp->save();
        }

        try {
            broadcast(new MessageSent($whatsapp, $whatsapp->merchant_id));
            Log::info("event broadcasted");
        } catch(\Exception $e) {
            Log::info("event not broadcasted");
            Log::info($e);
            return _failed($e);
        }
        return _successful($whatsapp, 201);

    }

    public static function sendWhatsappChat(Request $request){

        $param=[
            'customer_msisdn' => request('customer_msisdn'),
            'source' => 'agent',
            'message' => request('message'),
        ];
        
        $device=request('merchant_msisdn');
        $client = new \GuzzleHttp\Client(['verify' => false]);
        $endpoint = config('config_'.env('APP_ENV').'.SMS_SEND_CHAT_URL');

        try {
            $response = $client->request('POST', $endpoint.$device,
            [\GuzzleHttp\RequestOptions::JSON => $param, 
            'verify' => false, 
        ]);

        \Log::info(' $endpoint'.$endpoint, [$response,$device ]);

            $body  = json_decode($response->getBody());
            if ($body->status== "Successful"){
                return _successful($body->message, 200);
            }
            return _failed($body->message, 400);

        } catch (\Exception $ex) {
            $request = json_encode($param);
            $result = json_encode($ex->getMessage());
            \Log::info('Guzzle Exception Request: ' . $request);
            \Log::info('Guzzle Exception: ' . $result);
            return _failed($result, 400);
        }

    }

    public function updateRet($a, $b) {
        return $b->id - $a->id;
    }

    public function getSingleChat($chat_id)
    {
        $chats = WhatsappChat::where(['complaint_id' => $chat_id])->where('state','<>','closed');
        if (!is_null($chats)){
            $chats->update([
                "state"=>"opened",
            ]);
        }
        $chatQuery = WhatsappChat::select('id','merchant_id','merchant_msisdn','customer_msisdn','customer_name',
        'agent_name','session_start','session_end', 'agent_id','source','complaint_id','state',
        'message','updated_at','created_at')
        ->where(['complaint_id'=>$chat_id]);

        $latest_customer_message=clone $chatQuery;
        $latest_customer_message= $latest_customer_message->where('source', 'customer')
        ->orderBy('created_at', 'asc')->first();
        $session_has_ended=0;

      

     

        if (!is_null($latest_customer_message)){
            $currentDateTime = Carbon::now();
            $sessionEnds = Carbon::parse($latest_customer_message->session_end);

            if($sessionEnds < $currentDateTime) {
                $session_has_ended=1;
                $chatQuery->update(['state'=>'session_expired']);
            }
        }

        $per_page = request('per_page',5);
        $chatQuery=$chatQuery->orderBy('created_at', 'asc')->paginate($per_page);

        return _successful([
            "chat_list"=>$chatQuery,
            "session_has_ended"=>$session_has_ended
            

        ]);
    }

    public function agentReplyWithSms(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            "message" => 'required',
            "customer_msisdn"=>'required',
        ],
        [
            'customer_msisdn.required'=> 'Customer phone is required',
            'message.required'=> 'Message is required',
          
        ]);

        if ($validator->fails()){
            return _failed($validator->messages(), 400);
        }

        $sms_data=[
            [
            'msisdn' => request('customer_msisdn'),
            'message' =>request('message'),
            'device_id'=>'2349163519214'
            ]
        ];

       $send_customer_sms = new CustomersController();
       $sms_response  =$send_customer_sms->sendSMSV2($sms_data);
        if (count($sms_response[0]) > 0)
           return _failed(implode(',', $sms_response,400));
        
        return _successful('Message sent succesfully');
    }
}
