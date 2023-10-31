<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends BaseModel
{
    use HasFactory;

    protected $fillable = ['title', 'body'];
}
