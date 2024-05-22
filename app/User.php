<?php

namespace App;

use App\Traits\Scopes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    use Notifiable,Scopes, SoftDeletes;


    protected $hidden = ['updated_at'];
    protected $guarded = ['updated_at', 'secret_key'];
    protected $dates = ['created_at', 'updated_at'];

    public function merchant()
    {
        return $this->belongsTo('App\Merchant')->withDefault(function () {
            return new Merchant();
        });
    }

    public function premium()
    {
        return $this->belongsTo('App\Premium')->withDefault(function () {
            return new Premium();
        });
    }

    public static function getLegacyUser($email = '', $password = '')
    {
        //check for members created by owners
        $members = self::select('*')->where(['email'=> $email, 'password'=> bcrypt($password)])->first();

        if($members) {
            if ($members->type == 'merchant_memeber')
                return $members;
        }

        $merchant = Merchant::where([
            'merchant_email' => $email,
            'password' => hash('sha512', $password),
            'status' => 'enabled'

        ])->first();
      
   
        if ($merchant) {
            return User::updateOrCreate(['merchant_id' => $merchant->id], [

                'merchant_id' => $merchant->id,
                'name' => $merchant->merchant_name,
                'email' => $merchant->merchant_email,
                'password' => bcrypt($password),
                'type' => 'merchant',
                'status' => 'active',
                'member_role'=> 4,
                'api_token' => Str::random(16),
            ]);
        }

        return null;
    }


    public static function impersonateUser($email = '', $password = '')
    {
        $merchant = Merchant::where([
            'merchant_email' => $email,
            'status' => 'enabled'

        ])->first();

        if ($merchant) {
            return User::updateOrCreate(['merchant_id' => $merchant->id], [

                'merchant_id' => $merchant->id,
                'name' => $merchant->merchant_name,
                'email' => $merchant->merchant_email,
                'password' => bcrypt($password),
                'type' => 'merchant',
                'status' => 'active',
                'api_token' => Str::random(16),
                'member_role'=> 4,

            ]);
        }

        return null;
    }
}
