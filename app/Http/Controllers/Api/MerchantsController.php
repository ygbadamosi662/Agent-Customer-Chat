<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use App\Order;
use App\Premium;
use App\Merchant;
use App\RegistrationCode;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Api\MerchantRegistrationRequest;
use App\AccountDetail;
use App\Http\Requests\Api\SendMerchantCustomerSmsRequest;
use Illuminate\Support\Facades\DB;


class MerchantsController extends Controller
{


    // public function index($type = 'merchant', $status = null)
    // {
    //     if ($type == 'merchant') {
    //         $merchants = Merchant::whereIsParent(0);
    //     } elseif ($type == 'parent') {
    //         $merchants = Merchant::whereIsParent(1);
    //     } else {
    //         $merchants = Merchant::whereIn('id', Premium::select('merchant_id')->get());
    //     }

    //     if ($status) {
    //         $merchants = $merchants->whereStatus($status);
    //     }
    //     return _successful([

    //         'columns' => Merchant::columns(),
    //         'rows' => $merchants->where('status', '!=', 'deleted')->get(),
    //     ]);
    // }


    public function index($type = 'merchant', $status = null)
    {

        if ($type == 'merchant') {

            $q =  Merchant::merchantSelect()->where('status', '!=', 'deleted');
            // $q =  Merchant::merchantSelect()->where('status', '!=', 'deleted')->whereIsParent(0);
          
        } elseif ($type == 'parent') {
            $q =  Merchant::merchantSelect()->where('status', '!=', 'deleted')->whereIsParent(1);
    
        } else {
            $q = Merchant::merchantSelect()->whereIn('id', Premium::select('merchant_id')->get());
        }

        if ($status) {
            $q = $q->whereStatus($status);
        }


        if(isset($this->params()->searchTerm) || $this->params()->sorts || $this->params()->filters){
            $merchants =  Merchant::filterN(['merchant_name','merchant_phone', 'merchant_email', 'wallet', 'other_wallet', 'status'], 'wallet', [], [],
            true, $q);
            $this->params()->filter = true;
           }

        $data =  $this->params()->filter? $merchants->query: $q->orderBy('wallet', 'desc')->paginate($this->params()->perPage);
        $count = $this->params()->filter? $merchants->query->total() : $data->total() ;
    
        $new_data= [];
        foreach ($data as $row) {
            $row->wallet = _currencyNoNaira($row->wallet, false);
            $row->other_wallet = _currencyNoNaira($row->other_wallet, false);
            $new_data[]=$row;
        }

        return _successful([
            'columns' => Merchant::columns(),
            'rows' => $new_data,
            'totalRecords' => $count
        ]);

    }


    public function params(){

        return $fields = (object) [
        'page'           => request('page', 1),
        'perPage'        => request('perPage', 50),
        'searchTerm'     => request('searchTerm', null),
        'orders'        =>[],
        'filter'         => false,
        'filters'        => request('columnFilters', []),
        'sorts'         => request('sort', []),
        ];
    }

    public function single($id = 0)
    {
        $object = Merchant::leftjoin('account_details', 'member_id', '=', 'merchant.id')
             ->where('id', $id)
             ->select('merchant.*', 'account_details.*')
             ->first();

        if (!$object) {
            $object = Merchant::empty();
        }

        if (request('duplicate') == true) {
            $object->id = 0;
        }

        $object->inventory_settings = json_encode($object->inventory_settings);
        $object->inventory_settings = json_decode($object->inventory_settings, true);
        $object->payment_settings = json_encode($object->payment_settings);
        $object->payment_settings = json_decode($object->payment_settings, true);


        return _successful([
            'pwd' => $object->password,
            'object' => $object,
            'fields' => Merchant::fields($object),
        ]);
    }

 

    public function save(Request $request, $id = 0)
    {
        
        $this->validate($request, [

            'merchant_name' => 'required',
            'merchant_phone' => 'required|unique:merchant,merchant_phone,' . request('id'),
            'merchant_email' => 'required|email',
        ]);
        
        $password = Str::random(8);
        $merchant = Merchant::updateOrCreateFromRequest($id, 0, $password);

        if ($merchant == null) {
            return _failed('Phone Number has already been taken.', 400);
        }

        return _successful($merchant, 'Merchant account saved successfully.');
    }

    public function register(MerchantRegistrationRequest $request)
    {
        $registration_code = RegistrationCode::whereCode(request('code'))->where('status', '!=', 'used')->first();

        if (!$registration_code) {
            return _failed('Invalid Invitation Code', 400);
        }

      

       
        $merchant = Merchant::updateOrCreateFromRequest(0, 0, request('password'),$registration_code);
        if ($merchant == null) {
            return _failed('You have already registered', 400);
        }

        RegistrationCode::whereCode($registration_code->code)->update(['status' => 'used', 'used_by' => $merchant->id]);

        $subject = 'Merchant Registration!';
        $body ='<h4>Hello Admin </h4>';
        $body .= '<p>This merchant has just registered</p>';
        $body .= '<p>Company Name ' .$merchant->merchant_name. ' </p>';
        $body .= '<p>Contact Person ' .$merchant->merchant_contact_person . ' </p>';
        $body .= '<p>Phone '.$merchant->merchant_phone .' </p>';

        _email('support@ogaranya.com', $subject, $body);
        return _successful(['phone' => $merchant->merchant_phone], 'Your registration was successful. We will get back to you shortly.');
    }

    public function merchant_country($id){
        $object = Merchant::select('merchant_name', 'merchant_email' ,'id')
        ->where(['country_id'=>$id,'status' => 'enabled'])
        ->where(function($q) {
            $q->where('parent_id', '0')
              ->orWhere('parent_id', '=',  NULL);
        })
        ->orderBy('merchant_name','ASC')
        ->get();

        foreach ($object as $value) {
        $value->value=$value->id;
        $value->text=$value->merchant_name;    
        $value->customers = Order::merchantCustomers($value->id)->get()->count();
            # code...
        }
      
        return _successful([
            'rows' =>   $object,
            'customers'=> '',
        ]);

    }

    public function merchant_send_sms(SendMerchantCustomerSmsRequest $request){

       $customers = Order::merchantCustomers($request->merchant)->get();

        if(!$customers){
        return _failed('Merchant has no customers', 400);
        }


        $username= env('SMS_USERNAME');
        $sender=$request->sender;
        $apiKey = env('SMS_API_KEY');
        $msg =  $request->sms;
        $response='';
        $msisdn_array=[];
        $url="https://api.ebulksms.com:8080/sendsms";

        foreach($customers as $customer) {
            $msisdn_array[]=$customer->msisdn;
        }


        $start=0;
        $end=30;
        while($chunk = array_splice($msisdn_array, $start, $end)) {
            foreach ($chunk as $k) {
               
                $query_str = http_build_query(array('username' => $username, 'apikey' => $apiKey,
                'sender' => $sender, 'messagetext' => $msg, 'flash' => 0, 'recipients' => $k));
               
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "{$url}?{$query_str}");
                curl_setopt($ch,CURLOPT_PORT, 4433);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response .= curl_exec($ch);
                curl_close($ch);
            }
            sleep(4);

        }

        return _successful('', 'Sms sent successfully');
        

    }

    public function merchant_send_email(Request $request){

        $this->validate($request, [
            'merchant' => 'required',
            'body' => 'required',
            'subject' => 'required'
        ], 
        ['merchant.required' => 'Select a merchant',
        'body.required' => 'Message is required',
        'subject.required' => 'Email Subject is required'
       ]);
      
       $all_merchant_email = [];
       $email = request('merchant_email');
       $subject = request('subject');
       $body = request('body');

       if (request('merchant') == 'all') {

        $object = Merchant::select( 'merchant_email')
        ->where(['country_id'=>$id,'status' => 'enabled'])
        ->where(function($q) {
            $q->where('parent_id', '0')
              ->orWhere('parent_id', '=',  NULL);
        })
        ->get();

  
       }

       if (request('merchant') != 'all') {
        _email($email, $subject, $body);
       }

    $message ='';
    if (request('merchant') == 'all') {
        $message = ' Sent an email to All merchants';
    }else{
        $message ='Sent an email to '.$request->merchant_email;
    }
    _saveAuditLogs(Auth::user()->name,Auth::user()->id, Auth::user()->member_role,  $message);      
    return _successful('', 'Email sent successfully');
         
 
    }
 



}
