<?php

namespace App\ChatModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappChat extends Model
{
    use HasFactory;

    protected $connection = 'chats';
    protected $table = 'whatsapp_chat';
    protected $hidden = ['updated_at'];
    protected $guarded = ['updated_at'];
}
