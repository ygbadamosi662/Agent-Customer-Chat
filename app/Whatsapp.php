<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Whatsapp extends Model
{
    protected $hidden = ['updated_at'];
    protected $guarded = ['updated_at'];
    protected $appends = ['integration_url'];

    public function merchant()
    {
        return $this->belongsTo('App\Merchant')->withDefault();
    }

    public function getIntegrationUrlAttribute()
    {
        if ($this->status == 'activated') {
            return 'https://channels.ogaranya.com/api/whatsapp/'.$this->msisdn;
        }

        return null;
    }

    public static function empty()
    {
        $object = new self;

        foreach (collect(Schema::getColumnListing('whatsapps')) as $row) {
            $object->{$row} = '';
        }

        $object->status = 'pending';
        $object->plan = 'charge_per_contact';

        return $object;
    }

    public static function columns()
    {
        return [

            ['label' => 'Merchant', 'field' => 'merchant.merchant_name'],
            ['label' => 'Phone', 'field' => 'msisdn'],
            ['label' => 'Facebook Business ID', 'field' => 'facebook_business_id'],
            ['label' => 'Plan', 'field' => 'plan'],
            ['label' => 'Status', 'field' => 'status'],
            ['label' => 'Type', 'field' => 'type']
        ];
    }

    public static function fields($whatsapp = null)
    {
        $statuses = [
            ['id' => 'pending', 'name' => 'Pending'],
            ['id' => 'in_progress', 'name' => 'In Progress'],
            ['id' => 'activated', 'name' => 'Activated'],
            ['id' => 'blocked', 'name' => 'Blocked'],
        ];

        $plans = [
            ['id' => 'charge_per_contact', 'name' => 'Charge Per Contact'],
            ['id' => 'charge_per_message', 'name' => 'Charge Per Message']
        ];

        $types = [
            ['id' => 'ogaranya_whatsapp', 'name' => 'Ogaranya'],
            ['id' => 'service', 'name' => 'Custom Service']
        ];

        $fields = [

            ['name' => 'merchant_id', 'label' => 'Merchant', 'type' => 'select', 'data' => Merchant::getAll()],
            ['name' => 'msisdn', 'label' => 'Phone Number', 'type' => 'number'],
            ['name' => 'facebook_business_id', 'label' => 'Facebook Business ID', 'type' => 'number'],
            ['name' => 'plan', 'label' => 'Plan', 'type' => 'select', 'data' => $plans],
            ['name' => 'logo_url', 'label' => 'Logo URL', 'type' => 'url'],
            ['name' => 'comments', 'label' => 'Comments', 'type' => 'text'],
        ];

        if ($whatsapp != null && in_array($whatsapp->status, ['pending', 'in_progress'])) {
            $fields[] = ['name' => 'whatsapp_url', 'label' => '3rd Party Whatsapp API URL', 'type' => 'url'];
            $fields[] = ['name' => 'whatsapp_token', 'label' => '3rd Party Whatsapp API Token', 'type' => 'text'];
        }

        $fields[] = ['name' => 'type', 'label' => 'Route Type', 'type' => 'select', 'data' => $types];
        $fields[] = ['name' => 'service_endpoint', 'label' => 'Merchant Service URL', 'type' => 'url'];
        $fields[] = ['name' => 'service_token', 'label' => 'Merchant Service Token', 'type' => 'text'];
        $fields[] = ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'data' => $statuses];

        return $fields;
    }

    public static function whatsappPhoneNumbers($country,$start_date,$end_date,$startDate,$endDate){
     
        $data = self::selectRaw("max(status) as status,count(id) as count_id");
        // if ($start_date || $end_date) {
        //     $data->whereBetween('created_at', [$start_date,$end_date,$startDate,$endDate]);
        // }

        $data = $data->groupBY('status')->get();
        $total = $data->sum('count_id');

     
        foreach ($data as $row) {
            $row->percentage=round($row->count_id/$total*100, 2).'%';
            $row->status=ucfirst($row->status);
            $row->makeHidden(['integration_url']);
           
        }
        return $data;
    }

    public static function whatsappPhoneNumbersChartLabel($data){
        $gateway_label=[];
        $gateway_label_tooltip=[];
        foreach ($data as $row) {
            $gateway_label[]= ucfirst($row['status']);
            $gateway_label_tooltip[]=ucfirst($row['status']).' '.$row['percentage'];
        }
        return [$gateway_label, $gateway_label_tooltip];
    }

  
}

