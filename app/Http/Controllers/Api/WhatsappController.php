<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Whatsapp;
use App\Http\Requests\WhatsappRegistrationAdminRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


class WhatsappController extends Controller
{
    public function index()
    {
        return _successful([

            'columns' => Whatsapp::columns(),
            'rows' => Whatsapp::with('merchant')->get(),
        ]);
    }

    public function single($id = 0)
    {
        $object = Whatsapp::find($id);

        if (!$object) {
            $object = Whatsapp::empty();
        }

        if (request('duplicate')) {
            $object->id = 0;
        }

        if (!$object->service_token) {
            $object->service_token = Str::random(16);
        }

        return _successful([

            'object' => $object,
            'fields' => Whatsapp::fields($object),
        ]);
    }

    public function save(WhatsappRegistrationAdminRequest $request, $id = 0)
    {
        $phone = _to_phone(request('msisdn'));

        $payload = [
            'merchant_id' => request('merchant_id'),
            'msisdn' => $phone,
            'facebook_business_id' => request('facebook_business_id'),
            'logo_url' => request('logo_url'),
            'plan' => request('plan'),
            'whatsapp_url' => request('whatsapp_url'),
            'whatsapp_token' => request('whatsapp_token'),
            'comments' => request('comments'),
            'type' => request('type'),
            'service_endpoint' => request('service_endpoint'),
            'service_token' => request('service_token'),
            'status' => request('status')
        ];

        $whatsapp = Whatsapp::where(['merchant_id' => request('merchant_id'), 'msisdn' => $phone, 'id' => $id])->first();

        if ($whatsapp) {
            if ($whatsapp->status == 'pending' && in_array(request('status'), ['in_progress', 'activated']) && request('whatsapp_url') && request('whatsapp_token')) {
                $whatsapp->update(['activated_at' => date('Y-m-d H:i:s')]);
                //Todo: Send Email
            }
            $whatsapp->update($payload);
        } else {
            if (Whatsapp::where(['msisdn' => $phone])->count() > 0) {
                return _failed('Phone Number already exists', 400);
            }

            $whatsapp = Whatsapp::create($payload);
            //Todo: send Email
        }

        $message = $request->merchant_id.' create a whatsapp plug with ' .$request->whatsapp_url;

        _saveAuditLogs(Auth::user()->name,Auth::user()->id, Auth::user()->member_role,  $message);      

        return _successful($whatsapp);
    }
}
