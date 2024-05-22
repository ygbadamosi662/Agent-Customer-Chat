<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Merchant;
use App\AccountDetail;
use App\RegistrationCode;
use App\Whatsapp;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    private $password_msg;
    public function __construct() {
        $this->password_msg = 'Password should have at least one uppercase letter,';
        $this->password_msg .= ' at least one lower case letter, at least one numeric value,';
        $this->password_msg .= ' at least one special character, and must be more than 6 characters long';
    }

    public function index(Request $request)
    {
        if (User::whereType('admin')->count() == 0) {
            User::create([

                'name' => 'Ogaranya Admin',
                'email' => 'admin@ogaranya.com',
                'password' => bcrypt('password'),
                'type' => 'admin',
                'status' => 'active',
                'api_token' => Str::random(16),
            ]);
        }

        $this->validate($request, [

            'email' => 'required',
            'password' => 'required',
        ]);


        $loginUser = User::where(['email'=>  request('email'), 'status' => 'disabled'])->first();
        if ($loginUser) {
            return _failed('Please contact admin, your account has been disabled');
           }


        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
             $this->checkForImpersonationRequest();
            Auth::user()->update(['api_token' => Str::uuid()]);
            return response()->json([
                'status' => 'Successful',
                'message' => 'Login Successful',
                'data' => Auth::user(),
            ]);
        }


        $user = User::getLegacyUser(request('email'), request('password'));

        if ($user) {
            Auth::loginUsingId($user->id);
            $user->merchant;
            return _successful($user);
        }

        return response()->json([
            'status' => 'Failed',
            'message' => 'Invalid username or password!',
            'data' => null,

        ], 422);
    }

    public function login()
    {
        return _failed('Please provide valid Api Token', 401);
    }

    public function profile()
    {
        $user = Auth::user();
        $user->merchant;
        $account = AccountDetail::where(['member_type' => 'merchant', 'member_id' => $user->merchant->id])->first();
        $user->account = null;
        $whatsapp = Whatsapp::where([
            "merchant_id" => $user->merchant->id,
            "msisdn" => $user->merchant->merchant_phone,
            "status" => 'activated'
        ])->first();

        if($whatsapp) {
            $user->services = ["whatsapp" => true];
        }


        if ($account) {
            $user->account  = [
                'bank_name' => $account->bank_name,
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'bvn' => $account->bvn,
                'cac_url' => $account->cac_url
            ];
        }

        return _successful($user);
    }

    public function logout()
    {
        Auth::logout();

        return _successful('Logout Successful');
    }

    public function forgotPassword(Request $request)
    {
        if (!request('email')) {
            return _failed('Email Address is required', 400);
        }

        $user = User::where('email', '=', request('email'))->first();

        if ($user) {

            if ($user->status == "disabled")
            return _failed('Please contact admin, your account has been disabled');
        }

        if (!$user) {
            $merchant = Merchant::where(['merchant_email' => request('email')])->first();

            if (!$merchant) {
                return _failed('Invalid Email Address');
            }

            if ($merchant){
                    $user = User::updateOrCreate(['merchant_id' => $merchant->id, 'type' => 'merchant'], [
                        'merchant_id' => $merchant->id,
                        'name' => $merchant->merchant_name,
                        'email' => $merchant->merchant_email,
                        'type' => 'merchant',
                        'password' => null,
                        'api_token' => null,
                        'member_role' => 4
                    ]);
                }
            }

        $user->update(['api_token' => mt_rand(111111, 999999)]);

        $subject = 'Password Reset on Ogaranya';
        $body ='<h4>Hello '. $user->name .'</h4>';
        $body .= '<p> You have recently asked for a password reset of your account  <p>
        <p>If you are required to provide an OTP, kindly enter the code below <p><br> <h1>'.$user->api_token;
        _email($user->email, $subject, $body);
        return _successful([], 'An email has been sent to you with an OTP.');
    }

    public function confirmOTP(Request $request)
    {
        $this->validate($request, ['email' => 'required', 'otp' => 'required']);
        $user = User::where(['email' => request('email'), 'api_token' => request('otp')])->first();
        if ($user) {
            return _successful([], 'OTP Verified successfully');
        }
        return _failed('Invalid OTP', 400);
    }

    public function resetPassword(Request $request)
    {


        $this->validate($request, [
        'email' => 'required',
         'otp' => 'required',
         'password'=>['required', 'confirmed',
         'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{6,}$/'],
        ],
        [
          'password.regex'=> $this->password_msg,
          'password.required'=> 'Password is required'
        ]
       );
        $user = User::where(['email' => request('email'), 'api_token' => request('otp')])->first();
        if (!$user) {
            return _failed([], 'Invalid OTP', 400);
        }
        $user->update(['password' => bcrypt(request('password'))]);
        return _successful([], 'Your password has been successfully changed. You can now login');
    }

    public function verifyCode($code = '')
    {
        $where = [
            'code' => $code,
            'status' => 'unused'
        ];
        $registration_code = RegistrationCode::where($where)->first();
        if (!$registration_code) {
            return _failed('Invalid Token', 400);
        }
        return _successful(['msisdn' => $registration_code->msisdn]);
    }

    private function checkForImpersonationRequest()
    {

        $email = request('impersonate_merchant_email', null);
        if (Auth::user()->type === 'admin' && in_array(Auth::user()->role,['Admin']) && $email) {
            $user = User::where(['email' => $email, 'type' => 'merchant'])->first();
            if ($user){
                if ($user) {
                    Auth::logout();
                    Auth::loginUsingId($user->id);
                }
            }

            if(!$user){
                $user =  User::impersonateUser($email, 'password');
                if ($user){
                    if ($user) {
                        Auth::logout();
                        Auth::loginUsingId($user->id);
                    }
                }
            }


        }
    }


    public function joinTeam(Request $request, $code=0)
    {
        $user =  User::select('users.email', 'users.status',
        'users.name', 'roles.name as role')->where('verify_token', request('code'))
        ->leftjoin('roles', 'users.member_role', '=', 'roles.id')
        ->first();
        if (!$user){
            return _failed('Code is invalid!');
        }
        $name = explode(' ',$user->name);
        $user->firstname = $name[0];
        $user->lastname = $name[2];

        return _successful([
            'user' => $user,
        ]);

    }

    public function teamUpdatePassword(Request $request)
    {
        $this->validate($request, [
            'password'=>['required',
            'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{6,}$/'],
           ],
           [
             'password.regex'=> $this->password_msg,
             'password.required'=> 'Password is required'
           ]
        );

        $user = User::updateOrCreate(['email' => request('email')], [
            'password'                  => bcrypt(request('password')),
            'verify_token'              => NULL,
            "status"                    => 'active',
            "name"                 => request('name'),
        ]);
        return _successful('Password Updated successfully');

    }

 public function api_key(){
        _email('woorad7@gmail.com', 'test', 'oh yes');
    return _successful([], 'Okay.');

 }


}



