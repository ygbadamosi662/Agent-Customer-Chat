<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat{merchant_id}', function ($user, int $merchant_id) {
    if ($user) {
        return $user->merchant_id === $merchant_id;
    }
    return false;
});

Broadcast::channel('new-complaint{merchant_id}', function ($user, int $merchant_id) {
    if ($user) {
        return $user->merchant_id === $merchant_id;
    }
    return false;
});