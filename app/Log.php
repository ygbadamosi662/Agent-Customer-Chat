<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Log extends Model
{
    protected $table    = 'logs';
    protected $guarded  = ['updated_at'];
    public $timestamps  = false;


    public static function yearDate(){
        return '2023-m-d H:i';
    }

public static function getTextChannelUsedAnalysis($id=0){
    $start = \Carbon\Carbon::now()->startOfYear(Carbon::JANUARY)->format(self::yearDate());
    $end = \Carbon\Carbon::now()->endOfYear(Carbon::DECEMBER)->format(self::yearDate());
    $data =  self::selectRaw('count(*) as channel_count, max(channel) as channel, max(logs.msisdn) as msisdn')
    ->whereBetween('date_logged', [$start, $end]);

    if ($id>0){
        $data= $data->leftjoin('orders', 'orders.msisdn', '=', 'logs.msisdn');
        $data= Order::getMerchantData($id,$data);
    }
    $logs = $data->orderByRaw('(channel_count) desc')
    ->groupBY('channel')
    ->get();
    $total = $logs->sum('channel_count');

     $new_data=[];
     $others=[];
     $others_sum =0;
     foreach ($logs as $row) {
          $row->percentage=round($row->channel_count/$total*100, 2).'%';
          $new_data[]=$row;
     }
     return array_values($new_data);
 }


 public static function getTextChannelUsedAnalysisLabel($data){
    $gateway_label=[];
    $gateway_label_tooltip=[];
    foreach ($data as $row) {
        $gateway_label[]= Str::limit(ucfirst($row['channel']), 20).' '.$row['percentage'];
        $gateway_label_tooltip[]=ucfirst($row['channel']).' '.$row['percentage'];

    }
    return [$gateway_label, $gateway_label_tooltip];
}

}


     




