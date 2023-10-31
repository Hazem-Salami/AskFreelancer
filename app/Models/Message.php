<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'body',
        'room_id',
        'user_id'
    ];
}
