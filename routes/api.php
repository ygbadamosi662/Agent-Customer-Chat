<?php

use Illuminate\Support\Facades\Route;


Route::group(['namespace' => 'Api'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/login', 'AuthController@index');
        Route::get('/login', 'AuthController@login')->name('login');
        Route::post('/register/merchant', 'MerchantsController@register');
        Route::post('/register/merchant', 'MerchantsController@register');
        Route::post('/populate', 'WhatsappChatController@loadChat');
        Route::get('/logout', 'AuthController@logout');
        Route::get('/profile', 'AuthController@profile')->middleware('auth:api');
        Route::post('/password/forgot', 'AuthController@forgotPassword');
        Route::post('/password/otp', 'AuthController@confirmOTP');
        Route::post('/password/reset', 'AuthController@resetPassword');
        Route::get('/code/verify/{code}', 'AuthController@verifyCode');
        Route::get('/apikey', 'AuthController@api_key');
        Route::post('/join-team/{code}', 'AuthController@joinTeam');
        Route::post('/join-team/update-password/{id?}', 'AuthController@teamUpdatePassword');

        // agent-customer chat
        Route::get('/agent-customer/chats','WhatsappChatController@getChats');
        Route::get('/agent-customer/chats/{chat_id}','WhatsappChatController@getSingleChat');

        // Route::post('/get/chats', 'WhatsappChatController@getChats');
        // Route::get('/get/complaints/{id}/{state}/{count}', 'WhatsappChatController@getComplaints');
        Route::post('/agent-customer/chats/reply', 'WhatsappChatController@agentReply');
        Route::post('/agent-customer/chats/reply/sms', 'WhatsappChatController@agentReplyWithSms');
        Route::get('/agent-customer/chats/close/{id}', 'WhatsappChatController@closeMessage');
        Route::post('/broadcasting/auth', 'WhatsappChatController@authPusher');
        Route::get('/broadcast/reply/{id}', 'WhatsappChatController@broadcastReply');

    });
});

