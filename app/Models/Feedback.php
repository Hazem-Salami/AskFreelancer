<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'status',
        'feedback',
        'username'
    ];
}
