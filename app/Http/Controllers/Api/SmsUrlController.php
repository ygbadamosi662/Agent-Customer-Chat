<?php

namespace App\Http\Controllers\Api;

use App\SmsModels\SmsUrl;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SmsUrlController extends Controller
{
    public function index(Request $request)
    {
    
        $q = SmsUrl::selectSmsUrl();
        if(isset($this->params()->searchTerm) || $this->params()->sorts || $this->params()->filters){
            $qs = SmsUrl::filterN(['id','name', 'url', 'status',"testing"], 'id', [], [],
            true, $q);
            $this->params()->filter = true;
           }

        $data =  $this->params()->filter?  $qs->query: $q->orderBy('id', 'desc')->paginate($this->params()->perPage);
        $count = $this->params()->filter?  $qs->query->total() : $data->total() ;
        $new_data= [];

        foreach ($data as $row) {
            $status=['enabled'=>'btn-primary', 'disabled'=>'btn-danger','manual'=>'btn-primary', 'automatic'=>'btn-success'];
            $row->testing= "<span class='btn {$status[$row->testing]} btn-sm' id='{$row->id}_url' style='text-transform:capitalize'> {$row->testing}</span>";
            $checked = $row->status === 'enabled' ? 'checked' : '';
            $status = $checked ? 'Enabled' : 'Disabled';
            
            $row->status = "
                <div class='custom-control custom-switch'>
                    <span style='display:flex; align-items:center'>
                    <input type='checkbox' class='custom-control-input' id='customSwitch{$row->id}'{$checked} data-id='{$row->id}' @click='toggleStatus'>
                    <label class='custom-control-label' for='customSwitch{$row->id}'>{$status}</label>
                    </span>
                </div>
                ";
            $new_data[]=$row;
        }
         


        return _successful([
            'columns' => SmsUrl::columns(),
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
        $object = SmsUrl::find($id);

        if (!$object) {
            $object = SmsUrl::empty();
        }

        if (request('duplicate') == true) {
            $object->id = 0;
        }

        return _successful([
            'object' => $object,
            'fields' => SmsUrl::fields(),
        ]);
    }

    public function save(Request $request, $id = 0)
    {
       if (isset($request->name))  {
            $this->validate($request, [
                'name' => 'required',
                'url' => 'required',
            ]);
      }
      
        if ($request->status === 'disabled' && SmsUrl::where('status', 'enabled')->count() == 1 && !isset($request->name))
             return _failed('One of the sms url need to be enabled');

        $smsurl_data = SmsUrl::where('id',  $id)->first();   
        $smsUrl = SmsUrl::updateOrCreate(['id' => request('id')], [
            'name' => request('name')? request('name'): $smsurl_data->name,
            'url' => request('url')? request('url'): $smsurl_data->url,
            'status' =>  $id < 1 ?'disabled': (in_array(request('status'), ['enabled','disabled']) ? request('status'):$smsurl_data->status) ,
        ]);

        if ($request->status === 'enabled' && $id > 0){
            SmsUrl::where('id', '<>', $id)
            ->update(['status' => 'disabled']);
        }

        if (in_array($request->status, ['manual','automatic'])){
            SmsUrl::query()->update(['testing' => $request->status]);
        }
      
        return _successful($smsUrl);
    }



}
