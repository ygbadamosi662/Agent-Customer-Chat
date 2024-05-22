<?php

return [

    'application' => [
    ],
    
    'OGARANYA_PAYMENT_API_URL' => 'https://api.staging.ogaranya.com/v1',
    'OGARANYA_PAYMENT_API_TOKEN' => env('OGARANYA_API_STAGING_TOKEN'),
    'OGARANYA_PAYMENT_API_KEY' => env('OGARANYA_API_STAGING_PRIVATE_KEY'),
    
    'OGARANYA_API_URL' => 'https://api.staging.ogaranya.com/v1',
    'OGARANYA_API_TOKEN' => env('OGARANYA_API_STAGING_TOKEN'),
    'OGARANYA_API_KEY' => env('OGARANYA_API_STAGING_PRIVATE_KEY'),
    'SMS_SEND_CHAT_URL' => 'https://channels.ogaranya.com/api/whatsapp/chat/reply/'

];