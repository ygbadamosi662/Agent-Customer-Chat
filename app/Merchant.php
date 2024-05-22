<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Scopes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\AccountDetail;
use App\Traits\ModelFilter;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class Merchant extends Model
{
    use Scopes,ModelFilter;

    protected $table 	= 'merchant';
    protected $guarded  = ['updated_at'];
    public $timestamps 	= false;

    public function getInventorySettingsAttribute($value)
    {
        if ($value == '[]') {
            return (object)[];
        }

        return json_decode($value) ?? (object)[];
    }

    public function setInventorySettingsAttribute($value)
    {
        $this->attributes['inventory_settings'] = json_encode($value);
    }

    public function getPaymentSettingsAttribute($value)
    {
        if ($value == '[]') {
            return (object)[];
        }

        return json_decode($value) ?? (object)[];
    }

    public function setPaymentSettingsAttribute($value)
    {
        $this->attributes['payment_settings'] = json_encode($value);
    }

    public function premium()
    {
        return $this->hasOne('App\Premium')->withDefault();
    }

    public function user()
    {
        return $this->hasOne('App\User')->withDefault();
    }

    public function country()
    {
        return $this->belongsTo('App\Country')->withDefault();
    }

    public function payment_gateway_group()
    {
        return $this->belongsTo('App\PaymentGatewayGroup', 'payment_group_id')->withDefault();
    }

    public function account_details()
    {
        return $this->hasOne('App\AccountDetail', 'account_id');
    }


    public function getPaymentOptions($order)
    {
        $options = [];

        if ($this->use_custom_payment == 1) {
            foreach ($this->payment_gateway_group->payment_gateways()->whereStatus('enabled')->get() as $row) {
                $options[] = $row->formatted($order);
            }

            return $options;
        }

        return $this->country->getPaymentOptions($order);
    }

    public static function empty()
    {
        $object = new self;

        foreach (collect(Schema::getColumnListing('merchant')) as $row) {
            $object->{$row} = '';
        }

        $object->payment_settings = (object)[];

        return $object;
    }

    public static function getAll()
    {
        $countries = [];

        foreach (self::get() as $row) {
            $countries[] = ['id' => $row->id, 'name' => $row->merchant_name ];
        }

        return $countries;
    }

    public function bulk_airtimes()
    {
        return $this->belongsTo('App\BulkAirtime', 'merchant_id')->withDefault();
    }
    
    public static function columns()
    {
        return [

            ['label' => 'Name',     'field' => 'merchant_name'],
            ['label' => 'Phone',    'field' => 'merchant_phone'],
            ['label' => 'Email',    'field' => 'merchant_email'],
            ['label' => 'Wallet',   'field' => 'wallet'],
            ['label' => 'Code',   'field' => 'merchant_code'],
            ['label' => 'Other Wallet',   'field' => 'other_wallet'],
            ['label' => 'Status',   'field' => 'status'],
        ];
    }

    public static function fields($merchant = null)
    {
        $statuses = [

            ['id' => 'pending', 'name' => 'Pending'],
            ['id' => 'enabled', 'name' => 'Enabled'],
            ['id' => 'disabled', 'name' => 'Disabled'],
            ['id' => 'deleted', 'name' => 'Deleted'],
        ];

        $status = '';
        if ($merchant) {
            $status = '(Current Status: '.strtoupper($merchant->status).')';
        }


        $fieldList = [

            ['name' => 'merchant_name', 'label' => 'Name', 'type' => 'text'],
            ['name' => 'merchant_phone', 'label' => 'Phone', 'type' => 'number'],
            ['name' => 'merchant_email', 'label' => 'Email', 'type' => 'email'],
            ['name' => 'merchant_address', 'label' => 'Address', 'type' => 'textarea'],
            ['name' => 'merchant_contact_person', 'label' => 'Merchant Contact Person',  'type' => 'text'],
            ['name' => 'country_id', 'label' => 'Country', 'type' => 'select', 'data' => Country::getAll()],
            ['name' => 'commission', 'label' => 'Commission', 'type' => 'number'],
            ['name' => 'commission_cap', 'label' => 'Commission Cap', 'type' => 'number'],
            ['name' => 'commission_lower_bound','label' => 'Commission Lower Bound','type' => 'number'],
            ['name' => 'agent_commission_cap','label' => 'Agent Commission Cap','type' => 'number'],
            ['name' => 'agent_commission_lower_bound','label' => 'Agent Commission Lower Bound','type' => 'number'],
            ['name' => 'parent_child_split','label' => 'Parent Child Split','type' => 'number'],
            ['name' => 'charge_merchant_comission','label' => 'Charge Merchant Commissoin','type' => 'select', 'data' => _yes_no()],
            ['name' => 'language','label' => 'Laguague','type' => 'text'],
            ['name' => 'show_service_charge_desc','label' => 'Show Service Charge Desc','type' => 'text'],
            ['name' => 'is_parent','label' => 'Is Parent','type' => 'select', 'data' => _yes_no()],
            ['name' => 'payment_remittance_type','label' => 'Payment Remittance Type','type' => 'text'],
            ['name' => 'notify_via_email','label' => 'Notify via Email','type' => 'select', 'data' => _yes_no()],
            ['name' => 'manage_stock','label' => 'Manage Stock','type' => 'select', 'data' => _yes_no()],
            ['name' => 'show_merchant_support_line','label' => 'Show Merchant Support Line','type' => 'select', 'data' => _yes_no()],
            ['name' => 'show_merchant_name_on_pay_gateway','label' => 'Show Merchant Name on PayGateway','type' => 'select', 'data' => _yes_no()],
            ['name' => 'use_custom_payment', 'label' => 'Use Custom Payment','type' => 'select', 'data' => _yes_no()],
            ['name' => 'inventory_settings', 'label' => 'Inventory Settings','type' => 'textarea'],
            ['name' => 'payment_settings', 'label' => 'Payment Settings','type' => 'textarea'],
            ['name' => 'payment_group_id', 'label' => 'Payment Group','type' => 'select', 'data' => PaymentGatewayGroup::getAll()],
            ['name' => 'webhook_url', 'label' => 'Webhook URL','type' => 'url'],
            ['name' => 'status', 'label' => 'Status '.$status, 'type' => 'select', 'data' => $statuses],
        ];

        $accoun_fields = [
           ['name' => 'account_number', 'label' => 'Account Number', 'type' => 'number'],
           ['name' => 'account_name', 'label' => 'Account Name', 'type' => 'text'],
           ['name' => 'bank_name', 'label' => 'Bank Name', 'type' => 'text'],
           ['name' => 'bvn', 'label' => 'BVN', 'type' => 'text'],
           ['name' => 'cac_url', 'label' => 'Cac url', 'type' => 'text']
        ];

        if ($merchant->account_number){
            $fieldList =   array_merge($fieldList, $accoun_fields);
        }
     
        return  $fieldList;
    }

    public static function premiumChildFields()
    {
        return ['id', 'merchant_name', 'merchant_phone', 'merchant_email', 'merchant_address', 'merchant_contact_person', 'status'];
    }

    public static function updateOrCreateFromRequest($id = 0, $parent_id = 0, $password = null, $registration_code=null)
    {
        $phone = _to_phone(request('merchant_phone'));
        $merchant = self::where(['merchant_phone' => $phone])->first();
        $account = AccountDetail::where(['member_id' => $id])->first();
        $countryid=1;

        if ($merchant && $merchant->id != $id) {
            return null;
        }
        if(!is_null($registration_code))
           $countryid = $registration_code->country_id;

        $payload = [
            'merchant_name' => request('merchant_name'),
            'merchant_phone' => $phone,
            'merchant_email' => request('merchant_email')?? $merchant->merchant_email,
            'merchant_address' => request('merchant_address') ?? $merchant->merchant_address,
            'merchant_contact_person' => request('merchant_contact_person') ?? $merchant->merchant_contact_person,
            'wallet' => request('wallet')? request('wallet') : $merchant->wallet??0,
            'country_id' => $countryid ??$merchant->country_id,
            'commission' => request('commission')?  request('commission'): ($merchant->commission??0),
            'commission_cap' => request('commission_cap')? request('commission_cap'): ($merchant->commission_cap??0),
            'commission_lower_bound' => request('commission_lower_bound')?request('commission_lower_bound'): ($merchant->commission_lower_bound??0),
            'agent_commission' => request('agent_commission')? request('agent_commission'):($merchant->agent_commission??0),
            'agent_commission_cap' => request('agent_commission_cap')? request('agent_commission_cap'):$merchant->agent_commission_cap??0,
            'agent_commission_lower_bound' => request('agent_commission_lower_bound')? request('agent_commission_lower_bound'): $merchant->agent_commission_lower_bound??0,
            'parent_child_split' => request('parent_child_split')? request('parent_child_split'):$merchant->parent_child_split??0,
            'charge_merchant_comission' => request('charge_merchant_comission')? request('charge_merchant_comission'):$merchant->charge_merchant_comission??0,
            'show_service_charge_desc' => request('show_service_charge_desc')? request('show_service_charge_desc'):$merchant->show_service_charge_desc??1,
            'is_parent' => request('is_parent')? request('is_parent'):$merchant->is_parent??0,
            'payment_remittance_type' => request('payment_remittance_type')? request('payment_remittance_type'):$merchant->payment_remittance_type??'normal',
            'email_verified' => request('email_verified')? request('email_verified'):$merchant->email_verified??0,
            'notify_via_email' => request('notify_via_email')? request('notify_via_email'): $merchant->notify_via_email??0,
            'manage_stock' => request('manage_stock')? request('manage_stock'): $merchant->manage_stock??0,
            'inventory_settings' => json_decode(request('inventory_settings'))? json_decode(request('inventory_settings')): $merchant->inventory_settings??[],
            'show_merchant_support_line' => request('show_merchant_support_line')? request('show_merchant_support_line'):  $merchant->show_merchant_support_line??0,
            'show_merchant_name_on_pay_gateway' => request('show_merchant_name_on_pay_gateway')? request('show_merchant_name_on_pay_gateway'): $merchant->show_merchant_name_on_pay_gateway??1,
            'use_custom_payment' => request('use_custom_payment')? request('use_custom_payment'): $merchant->use_custom_payment??1,
            'payment_group_id' => request('payment_group_id')? request('payment_group_id') : $merchant->payment_group_id??5,
            'payment_settings' => json_decode(request('payment_settings'))? json_decode(request('payment_settings')): $merchant->payment_settings??[],
            'language' => request('language')?request('language'):$merchant->language??'english',
            'webhook_url' => request('webhook_url')? request('webhook_url'): $merchant->webhook_url??'',
            'parent_id' => $parent_id,
            'status' => 'pending',
            'account_number' => request('account_number')? request('account_number') :$account->account_number??'',
            'account_name' => request('account_name')? request('account_name'): $account->account_name??'',
            'bank_name' => request('bank_name')?request('bank_name'): $account->bank_name??'',
            'bvn' => request('bvn')? request('bvn'):$account->bvn??'',
            'cac_url' => request('cac_url')? request('cac_url'):$account->cac_url??'',
            'date_added' => $id == 0 ?date('Y-m-d H:i:s'):$merchant->date_added

        ];

        if (request('account_number')) {
            $account_details_payload = [
            'account_number' => request('account_number')?request('account_number'):$account->account_number??'',
            'account_name' => request('account_name')? request('account_name'):$account->account_name??'',
            'bank_name' => request('bank_name')? request('bank_name'):$account->bank_name??'',
            'bvn' => request('bvn')? request('bvn'):$account->bvn??'',
            'cac_url' => request('cac_url')? request('cac_url'):$account->cac_url??'',
         ];
            AccountDetail::where(['member_id' => $id])->update($account_details_payload);
        }


        if ($id == 0 && strlen($password) > 0) {
            $payload['password'] = hash('sha512', $password);
        } else {
            $payload['status'] = request('status');
        }


        $merchant =  self::updateOrCreate(['id' => $id], $payload);
        $user = User::where('email', $merchant->merchant_email)->first();

        if (!$merchant->merchant_code) {
            $merchant->update(['merchant_code' => _next_merchant_code()]);
        }

        if (request('status') == 'enabled' && $merchant->wasChanged('status')) {
             $subject = "Subject: Welcome Aboard to Ogaranya - Let's Get Started!";
             _email(
                $merchant->merchant_email,
                $subject,
                $merchant,
                '',
                '',
                '',
                'emails.welcome_merchant'
             );

        }

        if ($id == 0) {
            User::updateOrCreate(['merchant_id' => $merchant->id, 'type' => 'merchant'], [
                'merchant_id' => $merchant->id,
                'name' => $merchant->merchant_name,
                'email' => $merchant->merchant_email,
                'type' => 'merchant',
                'password' => bcrypt($password),
                'api_token' => Str::random(16),
                'status' => 'inactive',
                'member_role' => 4
            ]);

            $subject = 'Welcome to Ogaranya!';
            $body = '<h2>Hello ' . $merchant->merchant_name . '</h2>';
            $body .= '<p style="font-size:16px">Welcome to Ogaranya !. We are currently reviewing your details and will get back to you shortly.</p>';
             _email($merchant->merchant_email, $subject, $body);

        }

        return $merchant;
    }


    public static function merchantSelect(){
        return self::query()->select('id','merchant_name','merchant_phone', 'merchant_email','wallet',
        'merchant_code', 'other_wallet', 'status');
    }

    public static function activeMerchantCount($country,$start_date,$end_date,$startDate,$endDate){

        $merchant = self::selectRaw('max(merchant_id) as merchant_id')
        ->join('country', 'country.id', '=', 'merchant.country_id')
        ->join('orders', 'orders.merchant_id', '=', 'merchant.id')
        ->where('country.country_code', $country)
        ->where(function($query)  use ($start_date,$end_date,$startDate,$endDate){
            $query->where('merchant.status', 'enabled')->where('parent_id', 0)
            ->whereMonth('date_ordered', Carbon::now()->month)
            ->whereYear('date_ordered', Carbon::now()->year);
            if ($start_date || $end_date) {
                $query->whereBetween('merchant.date_added', [$startDate, $endDate]);
            }
        });
        
        $merchant= $merchant->orwhere(function($query) use ($start_date,$end_date,$startDate,$endDate) {
          
            if ($start_date || $end_date) {
                $query->whereBetween('merchant.date_added', [$startDate, $endDate]);
            }else{
                $query->orWhereNull('parent_id')
                ->whereMonth('date_ordered', Carbon::now()->month)
                ->whereYear('date_ordered', Carbon::now()->year);
            }
        });
        
        $merchant= $merchant->groupBY('merchant_id')->get()->count();

        $merchant_parent = self::selectRaw('count(parent_id) as parent')
        ->join('country', 'country.id', '=', 'merchant.country_id')
        ->join('orders', 'orders.merchant_id', '=', 'merchant.id')
        ->where('country.country_code', $country)
        ->where(['merchant.status'=>'enabled']);
        if ($start_date || $end_date) {
            $merchant_parent->whereBetween('merchant.date_added', [$start_date,$end_date,$startDate,$endDate]);
        }else{
            // $merchant_parent->whereMonth('merchant.date_added', Carbon::now()->month)
            // ->whereYear('merchant.date_added', Carbon::now()->year)
            $merchant_parent->whereMonth('date_ordered', Carbon::now()->month)
            ->whereYear('date_ordered', Carbon::now()->year)
            ->where('parent_id', '>', 1);
        }
        $merchant += $merchant_parent->groupBY('parent_id')->get()->count();
        return  $merchant ;
    }

    public static function totalMerchantCount($country,$start_date,$end_date,$startDate,$endDate){
        $query = self::select('merchant.id as id')
        ->join('country', 'country.id', '=', 'merchant.country_id')
        ->where('country.country_code', $country)
        ->where(['merchant.status'=>'enabled']);
        if ($start_date || $end_date) {
            $query->whereBetween('merchant.date_added', [$startDate, $endDate]);
        }
        return $query->get()->count();
    }

    public static function sumMerchantWallet($country,$start_date,$end_date,$startDate,$endDate){
        $query = self::select('wallet')
        ->join('country', 'country.id', '=', 'merchant.country_id')
        ->where('country.country_code', $country)
        ->where(['merchant.status'=>'enabled']);
        if ($start_date || $end_date) {
            $query->whereBetween('merchant.date_added', [$startDate, $endDate]);
        }
        return $query->sum('wallet');
    }

    public static function sumMerchantOtherWallet($country,$start_date,$end_date,$startDate,$endDate){
        $query = self::select('other_wallet')
        ->join('country', 'country.id', '=', 'merchant.country_id')
        ->where('country.country_code', $country)
        ->where(['merchant.status'=>'enabled']);
        if ($start_date || $end_date) {
            $query->whereBetween('merchant.date_added', [$startDate, $endDate]);
        }
        return $query->sum('other_wallet');
    }

    
}
