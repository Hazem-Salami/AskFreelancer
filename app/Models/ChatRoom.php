<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sender_id',
        'receiver_id',
    ];
}
