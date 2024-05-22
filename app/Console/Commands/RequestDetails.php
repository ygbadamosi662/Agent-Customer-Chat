<?php

namespace App\Console\Commands;


use PDF;
use App\User;
use App\Order;
use App\Merchant;
use Carbon\Carbon;
use App\Withdrawal;
use App\Transaction;
use App\RequestDetail;
use App\DigitalOrderFufilment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class RequestDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requestdetails:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch merchant request and export the details to csv, excel or pdf and send an email';
    public const NOW = 'Y-m-d H:i:s';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
       $merchants = RequestDetail::where('status', 'pending')->get();

       $details = '';


       if ($merchants) {

         foreach ($merchants as $value) {


            if ($value->types == 1)
                $details =  $this->getTransactionExport($value);  //get all transactions
            
            if ($value->types == 2)
                $details = $this->getOrdersExport($value);     //get all orders

            if ($value->types == 3)
                $details = $this->getOrdersStatusExport($value, $status='completed');     //get all completed orders 
            
            if($value->types == 4)
               $details = $this->getWithdrawals($value);
            
            $names =['name', 'Transactions Report', 'Orders Report', 'Completed Orders Report', 'Settlement Report'];
            $res = $this->send_mail($value, $names[$value->types], $details);
                
                if ($res['res'] === 200){
                    RequestDetail::where('status', 'pending')
                    ->where('merchant_id', $value->merchant_id)
                    ->update(['status' => 'sent', 'sent_date'=> date('Y-m-d h:i:s')]);
                }

               unlink($res['file_path']);

           }
       }
         
    }


    private function getTransactionExport($merchant){
        
        $start_date =$merchant->start_at;
        $end_date = $merchant->end_at;
        $where = ['member_id' =>$merchant->merchant_id, 'member_type' => 'merchant'];

       $q = Transaction::select('order_reference', 'closing_balance','created_at', 'amount','transaction_type' ,'description')
        ->leftjoin('orders', 'transactions.order_id', '=', 'orders.order_id')
        ->where($where)
         ->whereBetween('created_at', [$start_date, $end_date])
        ->get();

        $data = [
            'colunms'=> ['Order Reference','Closing Balance','Transaction Date', 'Amount','Type', 'Remarks'],
            'key'=> ['order_reference','closing_balance', 'created_at','amount', 'transaction_type', 'description'],
            'merchant'=> $merchant,
            'data' => $q,
            'type' => $merchant->export_type,
            'query'=> 'Transactions'

        ];
      return   $this->export($data);
    }

    private function getOrdersExport($merchant){
        
        $start_date =$merchant->start_at;
        $end_date = $merchant->end_at;
        $where = ['orders.merchant_id' => $merchant->merchant_id];
        $id_array = [];

        $q =  Order::select('orders.order_id', 'orders.order_reference','orders.msisdn','orders.discount_id', 'order_status',
        'for_another_msisdn', 'shipping_address', 'order_status', 'orders.trans_ref', 'merchant.show_only_real_total', 'orders.total','orders.real_total','orders.date_ordered','orders.payment_date', 'orders.product_type')
        ->with('digital_order_fulfilment')
        ->where('orders.order_status', '!=', 'archived')
         ->where($where)
         ->leftJoin('merchant', 'orders.merchant_id', '=', 'merchant.id')
         ->whereBetween('orders.date_ordered', [$start_date, $end_date])
         ->get();

         foreach ($q as $row) {
            $id_array[] = $row->order_id;
         }
       
    

         $digital_fufilment= DigitalOrderFufilment::select('custom_log', 'order_id')->wherein('order_id', $id_array)->get();

         foreach ($q as $key => $row) {
            $row->custom_log = 'None';
             if (count($digital_fufilment) > 0){
                if ($digital_fufilment[$key]->order_id == $row->order_id)
                    $row->custom_log = $digital_fufilment[$key]->custom_log??'None';
             }
          
            // $row->total = $row->discount_id !== 0 ? $row->real_total : $row->total;
            $row->total = $row->discount_id !== 0 || $row->show_only_real_total > 0 ? $row->real_total : $row->total;
            $row->order_status = strtoupper(str_replace('_', ' ',$row->order_status));

        }

        $data = [
            'colunms'=> ['Reference','Msisdn','Type', 'Total','Status','Username','Date Ordered'],
            'key'=> ['order_reference', 'msisdn', 'product_type','total', 'order_status','custom_log','date_ordered'],
            'merchant'=> $merchant,
            'data' => $q,
            'type' => $merchant->export_type,
            'query'=> 'Transactions'

        ];
      return   $this->export($data);
    }


    private function getWithdrawals($merchant)
    {

        $_merchant=Merchant::where(['id' => $merchant->merchant_id])->first();
        $start_date =$merchant->start_at;
        $end_date = $merchant->end_at;
        $q=[];
        $user= User::where(['email'=>$merchant->user_email])->first();
        $roleNumber = [3,4];

        if (($_merchant->is_parent== 1 &&  $_merchant->show_children_settlement==1) || in_array($user->member_role, $roleNumber)){

            $q = Withdrawal::select('withdrawal_id', 'merchant.wallet', 'payment_reference',
            'amount','withdrawals.status','withdrawals.created_at', 'approved_at','merchant_name')
            ->leftjoin('merchant', 'withdrawals.member_id', '=', 'merchant.id')
            ->where(function($query)  use ($start_date, $end_date,$merchant){
                $where[]= [['member_type'=> 'merchant', 'withdrawals.status'=> 'paid','merchant.parent_id'=> $merchant->merchant_id]];
                 $query->where($where)
                 ->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->orWhere(function($query)  use ($start_date, $end_date,$merchant){
                $orWhere[] = [['member_type'=> 'merchant', 'withdrawals.status'=> 'paid', 'member_id'=> $merchant->merchant_id]];
                $query->orWhere($orWhere)
                ->whereBetween('created_at', [$start_date, $end_date]);
           })
           ->get();
                   
        }else{
            $q = Withdrawal::select('withdrawal_id', 'merchant.wallet', 'payment_reference',
            'amount','withdrawals.status','withdrawals.created_at', 'approved_at','merchant_name')
            ->leftjoin('merchant', 'withdrawals.member_id', '=', 'merchant.id')
            ->where(function($query)  use ($start_date, $end_date,$merchant){
                $where[]= [['member_type'=> 'merchant', 'withdrawals.status'=> 'paid','withdrawals.member_id'=> $merchant->merchant_id]];
                 $query->where($where)
                 ->whereBetween('created_at', [$start_date, $end_date]);
            })
            ->get();

        }

        $new_data= [];
        foreach ($q as $row) {
            $row->type = _badge($row->status == 'paid' ? 'paid' : 'failed');
            $row->created =  date('Y-m-d h:i:s', strtotime($row->created_at));
            $row->approved =  $row->approved_at? date('Y-m-d h:i:s', strtotime($row->approved_at)): $row->approved_at;
            $row->amount = _currencyNoNaira($row->amount, true);
            $new_data[]=$row;
        }
      

        $data = [
            'colunms'=> ['merchant_name','Amount','Payment Reference','Status', 'Settlement Withdrawal Date', 'Settlement Approval Date'],
            'key'=> ['merchant_name','amount', 'payment_reference','status','created_at','approved_at'],
            'merchant'=> $merchant,
            'data' => $new_data,
            'type' => $merchant->export_type,
            'query'=> 'SEttlement'

        ];
        return   $this->export($data);
    }


    private function getOrdersStatusExport($merchant,  $status=''){
        
        $start_date =$merchant->start_at;
        $end_date = $merchant->end_at;
        $where = ['merchant_id' => $merchant->merchant_id];

        $q =  Order::select('orders.order_id', 'order_reference','msisdn','discount_id', 'order_status',
        'for_another_msisdn', 'shipping_address', 'order_status', 'trans_ref', 'total','real_total','merchant.show_only_real_total','date_ordered','payment_date', 'product_type')
        ->where('order_status', '=', $status)
         ->where($where)
         ->leftJoin('merchant', 'orders.merchant_id', '=', 'merchant.id')
         ->whereBetween('date_ordered', [$start_date, $end_date])
         ->get();

         $id_array = [];

         foreach ($q as $row) {
            $id_array[] = $row->order_id;
         }

         $digital_fufilment= DigitalOrderFufilment::select('custom_log', 'order_id')->wherein('order_id', $id_array)->get();

         foreach ($q as $key => $row) {
            if (count($digital_fufilment) > 0){
                if ($digital_fufilment[$key]->order_id == $row->order_id)
                    $row->custom_log = $digital_fufilment[$key]->custom_log??'None';
            }

            // $row->total = $row->discount_id !== 0 ? $row->real_total : $row->total;
            $row->total = $row->discount_id !== 0 || $row->show_only_real_total > 0 ? $row->real_total : $row->total;
            $row->order_status = strtoupper(str_replace('_', ' ',$row->order_status));
        }
     
        $data = [
            'colunms'=> ['Reference','Msisdn','Type', 'Total','Status','Date Ordered', 'Date Paid'],
            'key'=> ['order_reference', 'msisdn', 'product_type','total', 'order_status', 'date_ordered', 'payment_date'],
            'merchant'=> $merchant,
            'data' => $q,
            'type' => $merchant->export_type,
            'query'=> 'Transactions'

        ];
      return   $this->export($data);
    }



    private function export($data)
    {
        $export_type = $data['type'];
        switch ($export_type) {
            case '1'://csv
                return $this->export_csv($data);
                break;
            case '2'://xlsx
                // return $this->export_excel($data);
                return $this->export_csv($data);
                break;
            case '3'://pdf
                return $this->export_pdf($data);
                break;    
            
            default:
                break;
        }

    }

    private function export_csv($data=[])
    {
    
        $fileName = $data['merchant']['merchant_name'].'-'.date('Y-m-d-h:i:s').'.csv';
        $tasks = $data['data'];
        $keys = $data['key'];
        $path='';
        $csv_data = $csv_rows=[];
     

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );
     
        $columns = $data['colunms'];
        $file=fopen(storage_path('app/'.$fileName),'w');
        $path = storage_path('app/'.$fileName);
        $files =['name'=> $fileName, 'type'=>'text/csv', 'path'=>$path];

        fputcsv($file, $columns);
        ob_start();

            foreach ($tasks as $task) {
            $csv_row = [];
                foreach ($keys as $value) {
                $csv_row []= $task[$value] ?? 'None';
                }
                fputcsv($file, $csv_row);
            }

            fclose($file);
            ob_get_clean();

        return $files;    
 
    }

    private function export_excel($data=[])
    { 
        $fileName = str_replace(' ', '-', $data['merchant']['merchant_name'].'-'.date('Y-m-d-h:i:s').'.csv');
        $tasks = $data['data'];
        $keys = $data['key'];
        $path='';
        $csv_data = $csv_rows=[];
       

        $headers = array(
            "Content-type"        => "application/vnd.ms-excel",
            // "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "no-cache",
            "Expires"             => "0"
        );
     
        $columns = $data['colunms'];
        $file=fopen(storage_path('app/'.$fileName),'w');
        $path = storage_path('app/'.$fileName);
        $files =['name'=> $fileName, 'type'=>'application/vnd.ms-excel', 'path'=>$path];

        fputcsv($file, $columns);
        ob_start();

            foreach ($tasks as $task) {
            $csv_row = [];
                foreach ($keys as $value) {
                $csv_row []= $task[$value] ?? 'None';
                }
                fputcsv($file, $csv_row);
            }

            fclose($file);
            ob_get_clean();
    
       return $files;

    }


     
    public function export_pdf($query){
 
        $data = $query['data'];
        $headers =$query['colunms'];
        $keys = $query['key'];
        $fileName = str_replace(' ', '-', $query['merchant']['merchant_name'].'-'.date('Y-m-d-h:i:s').'.pdf');
        $pdf = PDF::loadView('downloads.index', compact('data', 'headers','keys') );
        $path = storage_path('app/');
        $pdf->save($path . '/'.$fileName);

        $files =['name'=> $fileName, 'type'=>'application/pdf', 'path'=>storage_path('app/'.$fileName)];
       return  $files;

       }
    
       
       public function send_mail($merchant, $name='', $files=[]){
        
        $body = 'Hello '.$merchant->user_name. ', <br>';
        $body.= "Kindly find attached " .$merchant->merchant_name.' '.$name.' requested';
        $res = _emailAttachment($merchant->user_email, $name.' Details For- '.date('Y-m-d-h:i:s'), $body, $files);
        return  ['res'=> $res, 'file_path'=>$files['path']];

       }

}
