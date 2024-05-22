<?php

use App\Merchant;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\Merchant\ActivityLogController;



function _cloudinary($file = '', $folder = 'damier-spaces', $is_base_64 = false)
{
    try {
        $public_id = str_random(10);

        if ($is_base_64 == true) {
            $response = \Cloudinary\Uploader::upload(
                "data:image/png;base64,$file",
                [
                    'public_id' => $public_id,
                    'folder' => $folder,
                ]
            );
        } else {
            $response = \Cloudinary\Uploader::upload($file, [
                'public_id' => $public_id,
                'folder' => $folder,
            ]);
        }

        return (object) ['status' => true, 'link' => $response['secure_url']];
    } catch (Exception $exeption) {
        return (object) [
            'status' => false,
            'link' => null,
            'error' => $exeption->getMessage(),
        ];
    }
}

function _currency($amount = 0, $decimal = true)
{
    return '₦' . number_format($amount, $decimal ? 2 : 0);
}

function _currencyNoNaira($amount = 0, $decimal = true)
{
    return number_format($amount, $decimal ? 2 : 0);
}


function _email(
    $to = '',
    $subject = '',
    $body = '',
    $file = '',
    $cc = '',
    $no_body_mail = '',
    $view=''
){
    $payload = [
        'from' => 'Ogaranya <no-reply@ogaranya.com>',
        'to' => $to,
        // 'bcc' => 'woorad7@gmail.com',
        'subject' => $subject,
    ];

    if ($file) {
        $payload['attachment[0]'] = $file;
    }
    if ($cc) {
        $payload['cc'] = $cc;
    }
    if ($no_body_mail) {
        $payload['html'] = $no_body_mail;
    }

    if ($body && !$view) {
        $payload['html'] = $body;
    }
    
    if ($view) {
        $payload['html'] = view($view, compact('body'))->render();
    }


    $url = 'https://api.mailgun.net/v3/mg.ogaranya.com/messages';

    try {

        $client = new \GuzzleHttp\Client(['base_uri' => $url, 'verify' => false]);
        $response = $client->request('POST', $url, [
            'auth' => ['api', env('MAILGUN_API_KEY')],
            'form_params' => $payload
        ]);

        return json_decode($response->getBody()->getContents());

    } catch (\Exception $e) {
        \Log::info($e);
        return false;

    }
}



// function _email(
//     $to = '',
//     $subject = '',
//     $body = '',
//     $file = '',
//     $cc = '',
//     $no_body_mail = '',
//     $view=''
// ) {
//     try {
//         $api_key = env('MAILGUN_API_KEY');

//         $payload = [
//             'from' => 'Ogaranya <no-reply@ogaranya.com>',
//             'to' => $to,
//             // 'bcc' => 'vadeshayo@gmail.com',
//             'subject' => $subject,
//             // 'html' => $body,
//             'html' => view('emails.index', compact('body'))->render(),
//         ];

//         if ($file) {
//             $payload['attachment[0]'] = $file;
//         }
//         if ($cc) {
//             $payload['cc'] = $cc;
//         }
//         if ($no_body_mail) {
//             $payload['html'] = $no_body_mail;
//         }
//         if ($view) {
//             $payload['html'] = view($view, compact('body'))->render();
//         }
       
//         $result = Mailgun\Mailgun::create($api_key)
//             ->messages()
//             ->send('mg.ogaranya.com', $payload);
           
//     } catch (Exception $e) {
//         \Log::info($e->getMessage(),[$api_key]);
//     }
// }



function _date($dateString = '', $time = false)
{
    if (!$dateString) {
        return '--';
    }

    if ($time == false) {
        return date('M d, Y', strtotime($dateString));
    }

    return date('M d, Y g:i A', strtotime($dateString));
}

function _time($dateString = '')
{
    if (!$dateString) {
        return '--';
    }

    return date('g:i A', strtotime($dateString));
}

function _badge($string = '')
{
    $class = 'primary';
    $string = strtolower($string);

    if (
        in_array($string, [
            'inactive',
            'awaiting_payment',
            'pending',
            'debit',
            'failed',
        ])
    ) {
        $class = 'danger';
    }

    if (
        in_array($string, [
            'online',
            'completed',
            'active',
            'success',
            'credit',
            'paid',
        ])
    ) {
        $class = 'success';
    }

    if (
        in_array($string, ['assigned', 'percentage', 'archived', 're_initiate'])
    ) {
        $class = 'warning';
    }

    $string = strtoupper(implode(' ', explode('_', $string)));
    return "<span class='badge badge-{$class}'>{$string}</span>";
}

function _tooltip($text = '', $is_config = false)
{
    if ($is_config) {
        $text = config($text);
    }

    echo "data-toggle='tooltip-primary' data-placement='bottom' data-original-title='$text'";
}

function _log($log = '', $performedOn = null, $delivery_id = 0)
{
    $log .= ' :: ' . request()->ip();

    if (Auth::check()) {
        $user = Auth::user();

        if ($performedOn != null) {
            return activity()
                ->performedOn($performedOn)
                ->causedBy($user)
                ->log((string) $log);
        }

        return activity()
            ->causedBy($user)
            ->log((string) $log);
    }

    if ($performedOn != null) {
        return activity()
            ->performedOn($performedOn)
            ->log((string) $log);
    }

    return activity()->log((string) $log);
}

function _package_benefits()
{
    return ['Unlimited activity', 'Direct messaging', 'Members', 'Admins'];
}

function _days()
{
    return [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];
}

function _duration_description($duration = '')
{
    if ($duration == 'daily') {
        return 'Day';
    }

    if ($duration == 'weekly') {
        return 'Week';
    }

    if ($duration == 'monthly') {
        return 'Month';
    }

    return '';
}

function _statuses()
{
    return [
        ['id' => 'enabled', 'name' => 'Enabled'],
        ['id' => 'disabled', 'name' => 'Disabled'],
    ];
}

function _yes_no()
{
    return [['id' => 1, 'name' => 'Yup'], ['id' => 0, 'name' => 'Nope']];
}

function _successful($data, $message = 'Request Successful')
{
    return response()->json([
        'status' => 'Successful',
        'message' => $message,
        'data' => $data,
    ]);
}

function _failed($message, $status = 400)
{
    return response()->json(
        [
            'status' => 'Failed',
            'message' => $message,
            'data' => null,
        ],
        $status
    );
}

function _to_phone($string = '', $remove234 = false)
{
    if ($remove234) {
        return '0' . substr($string, -10);
    }

    return '234' . substr($string, -10);
}

function _send_notification($notifications = [])
{
    $endpoint = 'https://sms.ogaranya.com/api/notification';

    try {
        $payload = [
            'messages' => $notifications,
        ];

        $response = (new Client())->post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ogranya',
                'Accept' => 'application/json',
            ],

            RequestOptions::JSON => $payload,
        ]);

        return json_decode($response->getBody()->getContents());
    } catch (Exception $e) {
        return [
            'status' => 'Failed',
            'message' => $e->getMessage(),
            'data' => null,
        ];
    } catch (ClientException $e) {
        return [
            'status' => 'Failed',
            'message' => $e->getMessage(),
            'data' => null,
        ];
    } catch (ConnectException $e) {
        return [
            'status' => 'Failed',
            'message' => $e->getMessage(),
            'data' => null,
        ];
    } catch (ServerException $e) {
        return [
            'status' => 'Failed',
            'message' => $e->getMessage(),
            'data' => null,
        ];
    }
}

function _registration_code()
{
    $code = mt_rand(111111, 999999);

    if (\App\RegistrationCode::whereCode($code)->count() > 0) {
        return _registration_code();
    }

    return $code;
}

function _next_merchant_code()
{
    $earliest_merchant_by_code = Merchant::select('merchant_code')
        ->orderBy('merchant_code', 'desc')
        ->take(1)
        ->first();
    $merchant_code = 10001;
    if ($earliest_merchant_by_code) {
        $merchant_code = $earliest_merchant_by_code->merchant_code;
    }
    return $merchant_code += 1;
}

function _get_product_code()
{
    $code = mt_rand(111111, 999999);
    if (\App\Product::where(['product_code' => $code])->count() > 0) {
        return _get_product_code();
    }

    return $code;
}

function _get_telco_from_phone($phone = '')
{
    $phone = _to_phone($phone);

    $airtel = 'Airtel';
    $mtn = 'MTN';
    $glo = 'GLO';
    $etisalat = '9mobile';

    $codes = [
        '234701' => $airtel,
        '2347025' => $mtn,
        '2347026' => $mtn,
        '234703' => $mtn,
        '234704' => $mtn,
        '234705' => $glo,
        '234706' => $mtn,
        '234708' => $airtel,
        '234802' => $airtel,
        '234803' => $mtn,
        '234805' => $glo,
        '234806' => $mtn,
        '234807' => $glo,
        '234808' => $airtel,
        '234809' => $etisalat,
        '234810' => $mtn,
        '234811' => $glo,
        '234812' => $airtel,
        '234813' => $mtn,
        '234814' => $mtn,
        '234815' => $glo,
        '234816' => $mtn,
        '234817' => $etisalat,
        '234818' => $etisalat,
        '234909' => $etisalat,
        '234908' => $etisalat,
        '234901' => $airtel,
        '234902' => $airtel,
        '234903' => $mtn,
        '234904' => $airtel,
        '234905' => $glo,
        '234906' => $mtn,
        '234907' => $airtel,
        '234915' => $glo,
        '234913' => $mtn,
        '234912' => $airtel,
        '234916' => $mtn,
    ];

    foreach ($codes as $key => $value) {
        if (Str::startsWith($phone, $key)) {
            return $value;
        }
    }

    return $mtn;
}

function _order_reference()
{
    $reference = date('ymd') . mt_rand(2000, 9999);

    if (\App\Order::where(['order_reference' => $reference])->count() > 0) {
        return _order_reference();
    }

    return $reference;
}

function _emailAttachment($to = '', $subject = '', $body = '', $file = [])
{
    $api_key = env('MAILGUN_API_KEY');
    $filename = $file['name'];
    $path = storage_path('app/');

    $ch = curl_init();
    curl_setopt(
        $ch,
        CURLOPT_URL,
        'https://api.mailgun.net/v3/mg.ogaranya.com/messages'
    );
    curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $api_key);
    $parameters = [
        'from' => 'Ogaranya <no-reply@ogaranya.com>',
        'to' => $to,
        'bcc' => 'woorad7@gmail.com',
        'subject' => $subject,
        'html' => view('emails.index', compact('body'))->render(),
        'attachment[1]' => curl_file_create(
            $file['path'],
            $file['type'],
            $filename
        ),
    ];
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return $response;
}

function _exportCsvArray($data = [])
{
    $tasks = $data['data'];
    $keys = $data['key'];
    $path = '';
    $csv_data = [];
    $csv_rows = [];
    $columns = $data['colunms'];
    $csv_data[] = $data['colunms'];
    foreach ($tasks as $task) {
        $csv_row = [];
        foreach ($keys as $value) {
            $csv_row[] = $task[$value] ?? 'None';
        }
        $csv_data[] = $csv_row;
    }

    return $csv_data;
}

function uploadImage($file_extension, $file)
{
    try {
        $client = new \Aws\S3\S3Client([
            'credentials' => [
                'key' => env('AWS_KEY'),
                'secret' => env('AWS_SECRET'),
            ],
            'http' => [
                'verify' => false,
            ],
            'region' => 'fra1', // Region you selected on time of space creation
            'endpoint' => 'https://fra1.digitaloceanspaces.com',
            'version' => 'latest',
        ]);

        $name = Str::uuid() . '.' . $file_extension;
        $upload = $client->upload(
            'ogaranya',
            'kyc/' . $name,
            fopen($file, 'rb'),
            'public-read'
        );
        $link = $upload->get('ObjectURL');
        $scheme = parse_url($link, PHP_URL_SCHEME);

        if (empty($scheme)) {
            $link = 'https://' . ltrim($link, '/');
        }

        return $link;
    } catch (\Exception $e) {
        return null;
    }
}

function _saveAuditLogs($name, $id, $roleid, $description)
{
    $auditlog = new AuditLogController();
    $auditlog->saveLogs($name, $id, $roleid, $description);
}

function _termiiTelco($phone)
{
    $api_key = env('TERMII_API_KEY');
    $data = ['api_key' => $api_key, 'phone_number' => $phone];
    $parameters = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.ng.termii.com/api/check/dnd');

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function _mobileAirtimeCheckTelco($phone)
{
    $trans_ref = $phone . '-' . date('ymdhis');

    $url = 'https://mobileairtimeng.com/httpapi/hlrlook';
    $query_str = http_build_query([
        'userid' => env('MOBILE_AIRTIME_USERNAME'),
        'pass' => env('MOBILE_AIRTIME_PASS'),
        'phone' => $phone,
        'user_ref' => $trans_ref,
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$url}?{$query_str}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    return json_decode($response);
}

function _isJson($string)
{
    json_decode($string, true);
    return json_last_error() === JSON_ERROR_NONE;
}

function _generateUniqueId($token, $seed)
{
    //hash the requestId with md5
    $md5Hash = md5($token);

    //convert hex string to decimal
    $dec = strval(substr(number_format(hexdec($md5Hash), 2, '.', ''), 0, 32));
    $unique_reference = 0;

    $indices = uniqueRandomNumbersWithinRange(0, 31, $seed);
    $unique_reference_exist = false;

    foreach ($indices as $index) {
        $unique_reference .= $dec[$index];
    }

    return $unique_reference;
}

function uniqueRandomNumbersWithinRange($min, $max, $seed)
{
    $numbers = range($min, $max);
    shuffle($numbers);

    return array_slice($numbers, 0, $seed);
}

function _randomStrings($length_of_string = 10, $salt = '0000')
{
    $str_result =
        '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz)(*&^%$#@!~`><?;:"" =+]{\|' .
        $salt;
    return substr(str_shuffle($str_result), 0, $length_of_string);
}

function validUrl($str)
{
    if (empty($str)) {
        return false;
    } elseif (preg_match('/^(?:([^:]*)\:)?\/\/(.+)$/', $str, $matches)) {
        if (empty($matches[2])) {
            return false;
        } elseif (!in_array(strtolower($matches[1]), ['http', 'https'], true)) {
            return false;
        }

        $str = $matches[2];
    }

    // PHP 7 accepts IPv6 addresses within square brackets as hostnames,
    // but it appears that the PR that came in with https://bugs.php.net/bug.php?id=68039
    // was never merged into a PHP 5 branch ... https://3v4l.org/8PsSN
    if (
        preg_match('/^\[([^\]]+)\]/', $str, $matches) &&
        !is_php('7') &&
        filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
    ) {
        $str = 'ipv6.host' . substr($str, strlen($matches[1]) + 2);
    }

    return filter_var('http://' . $str, FILTER_VALIDATE_URL) !== false;
}



function generateUniqueCode($code_length=8)
{

    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersNumber = strlen($characters);
    $codeLength = $code_length;

    $code = '';

    while (strlen($code) < 6) {
        $position = rand(0, $charactersNumber - 1);
        $character = $characters[$position];
        $code = $code.$character;
    }
    return $code;

}