<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatReadState extends Model
{
    protected $fillable = [
        'user_id',
        'channel_id',
        'direct_thread_id',
        'last_read_message_id',
    ];
}
